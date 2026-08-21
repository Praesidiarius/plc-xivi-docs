# Deploying

An installation is deployed with **Deployer**, driving `docker compose` on the
target over SSH. One command builds the image, pushes it to a registry, migrates
the control plane and every customer, and only then replaces the running
containers.

```console
bin/release <target>
```

The target host needs **Docker, Compose, and nothing else**: no PHP, no Postgres
client, no rsync. Everything Xivi runs, it runs in containers.

!!! tip "Set Docker's log rotation before anything else"

    The default is unbounded, and the disk is usually the smallest thing on a
    box. In `/etc/docker/daemon.json`:

    ```json
    { "log-driver": "json-file", "log-opts": { "max-size": "10m", "max-file": "3" } }
    ```

## Once per installation

Two files, both kept out of version control, describe *your* deployment:

```console
cp .hosts.yaml.dist .hosts.yaml            # which boxes, which image each runs
cp .env.deploy.dist .env.deploy.<target>   # secrets, hostnames, sizing
bin/compose exec php vendor/bin/dep secrets:install <target>
```

`secrets:install` writes the environment file to the target once, at mode 600.
**A deploy never writes it again.** Rotating a secret means editing it there and
deploying again, which is deliberate: a deploy that shipped your production
secrets every time would leave them in the shell history of every machine that
ever deployed, and would silently overwrite a value somebody had just rotated.

Generate the two secrets the guard checks:

```console
# APP_SECRET
openssl rand -hex 32
# TENANT_SECRET_KEYS is a keyring, not a key
php -r 'echo json_encode(["1" => base64_encode(random_bytes(32))]);'
```

An instance that starts on the values committed in `.env` **refuses to boot**
rather than encrypting customer data with a key that is published in the
repository.

The provisioning database role needs `CREATEDB`, `CREATEROLE` and
**`pg_signal_backend`**. Without the last one it cannot drop a customer database
that somebody is still connected to, and you find out at the worst moment.

### Nothing is in the store until you put it there

A fresh installation has **every module in `development`**, which means none of
them is offered in the store. That is the default rather than an oversight: a
module with no row is in development, and whether a module is finished is a
decision this installation makes rather than something the build ships with.

```console
bin/console module:list                       # what exists, and where each one stands
bin/console module:state <key> published      # offer it to every tenant
```

An empty store on a new installation looks broken and is not, so this is easy to
lose an afternoon to. The state lives in the control-plane database, so **moving
to a new box starts everything at `development` again** and needs the same
commands.

!!! warning "Publishing is for every tenant, or none"

    There is no per-tenant override, deliberately: whether a module is finished
    is a fact about the module, and showing one customer a half-built one is the
    drift the design rejects.

    What the state gates is **the store listing and nothing else.** Installing is
    not gated by it, because a module is developed by installing it somewhere. So
    `tenant:module:install <tenant> <key>` gives one customer a module without
    offering it to anybody else, which is the mechanism when that is what you
    want.

Pricing is a separate decision from publishing, and a separate command:

```console
bin/console module:price <key> <unpriced|free|priced|not_for_sale> [amount]
```

Leave modules **free** until you have somewhere for the money to go. A `priced`
module is visible to every customer and its install stops at a placeholder, so
pricing early produces something people can see and cannot finish.

## Every release

```console
export GHCR_USER=<your registry login>
export GHCR_TOKEN_FILE=~/somewhere/outside/the/checkout
bin/release <target>
```

In order, that:

1. builds the image the target is configured for, and pushes it;
2. pulls it on the target **by digest**, not by tag;
3. runs `bin/deploy` out of the new image: secrets, control plane, then every
   customer;
4. replaces the serving containers;
5. waits for the instance to report healthy;
6. writes the cron entries this release needs.

**Three before four is the point.** Tenant migrations are additive only, so the
old code keeps serving correctly against the new schema while the walk runs and
the instance stays up. A customer that fails to migrate fails the deploy, before
anything is replaced.

**By digest and not by tag**, so a container that restarts three weeks later
comes back as the same code rather than as whatever the tag points at by then.

## Rolling back

Deploy the previous digest. It builds nothing:

```console
bin/release <target> --tag=sha256:<previous digest>
```

**It does not roll the databases back**, and does not need to. A release that
added a column added it everywhere, and stepping the code back leaves it there;
old code meets a newer schema, finds every column it knew about, and ignores the
rest. That holds because tenant migrations may not drop, rename, or narrow
anything. A change that genuinely removes something is **two releases**, and the
second one is not safe to roll back until the first is everywhere.

A failed deploy needs no rollback at all: nothing is replaced until after the
migration, so the previous image is still serving.

## Certificates, without listing your customers

Customers are served on their own hostnames, so there is no fixed list of names
to certify and no wildcard to buy. Caddy gets a certificate the first time a
hostname is asked for, and **asks the application first** whether it serves that
name.

That question is not optional. Without it, anybody who points a DNS record at
your address causes a certificate request, and the rate limit those spend is
counted per registered domain: it is your customers' budget being burned.

The endpoint answers from the registry plus your platform hostnames, returns a
status code and no body, and **refuses any request that did not come from inside
the container**, so it cannot be used from outside to ask whether a given
customer exists.

Nothing to configure; it is on. What you do have to get right is that the
hostname really resolves to your box, because no certificate authority can issue
for a name it cannot reach.

!!! warning "Before public DNS exists"

    A hosts file on your own machine is not enough, and the staging endpoint of
    a certificate authority is no different: validation is an inbound request
    from the authority, over public DNS. To rehearse anyway, set
    `CADDY_TLS_ISSUER=issuer internal`. Caddy then signs locally, nothing trusts
    the certificate, and the ask endpoint is still consulted, which is the half
    worth rehearsing. Remove the line once DNS resolves.

## Scheduled jobs

The deploy writes `/etc/cron.d/xivi` from the job list in the release being
deployed, so the schedule cannot be a version behind the commands it names. To
see what they are and whether anything is watching them:

```console
bin/console deploy:crontab
```

**Nothing watches them by default**, and a scheduled job that stops is invisible:
the screens built on it go stale quietly and an empty mailbox looks exactly like
a healthy one. `XIVI_MONITOR_PINGS` maps each job to a URL that an outside
service alarms on when a ping does not arrive. See
[Monitoring](monitoring.md).

## Things that will catch you once

**An installation with no customers yet stops the deploy.** That is deliberate:
a registry that has *lost* its customers looks identical from the inside to one
that never had any, and the first should stop a release. If yours is empty on
purpose, such as one waiting for its first self-service signup, say so with
`XIVI_ALLOW_EMPTY_REGISTRY=1`. It changes nothing else.

**Check which SMTP port your host can actually reach.** Providers block outbound
mail ports to keep their address ranges off blocklists, and the block is a
timeout rather than a refusal, so the wrong choice looks like mail hanging rather
than mail failing. 587 is open more often than 465.

```console
docker compose ... run --rm --entrypoint php php -r \
  'var_dump((bool) @fsockopen("mail.example.com", 587, $e, $m, 8));'
```

**Percent-encode the credentials in `MAILER_DSN`.** A username that is an email
address puts an `@` inside the userinfo, and a password may hold `[`, `]`, `/`,
`:` or `#`. All of them break URL parsing, and `[` in particular makes the parser
read what follows as an IPv6 address and reject the whole string. `@` is `%40`,
`[` is `%5B`, `]` is `%5D`.

## The step a deploy runs

`bin/release` runs this for you. It is documented separately because it is the
part that must happen whichever tool eventually runs it:

```console
# in a one-shot container, from the image being released,
# with the deployment's environment, before the serving containers are replaced
bin/deploy
```

It checks the secrets, migrates the control-plane database, verifies the
customer-facing image's database grants match this release, checks that every
customer is on a hostname this installation answers to, then migrates every
tenant database. It stops on the first failure.

**Nothing else runs the tenant migrations.** The container entrypoint
deliberately does not, because it runs on every container start rather than
once per deploy, and at fifty customers that turns a restart into an outage.

A non-zero exit means do not switch traffic. **3** in particular means some
customers migrated and some did not; the output names them and prints the
`--slug` line to retry each one. `deploy:check-hosts` and
`deploy:check-grants` use the same 3 for the same shape of finding, and name
what they found.

## Two kinds of migration

Migrations are split, and the split is the reason `bin/deploy` exists rather
than a single `doctrine:migrations:migrate`:

| | Runs |
| --- | --- |
| Control-plane migrations | once per deploy |
| Tenant migrations | once **per customer** |

`bin/deploy` runs both, in that order.

## Two images, and which one goes where

`docker build` has two production targets, and they differ in one thing:
whether the administration surface is in the image.

```console
docker build --target frankenphp_prod   -t xivi-internal .   # everything
docker build --target frankenphp_public -t xivi-public   .   # no admin surface
```

`frankenphp_public` is what a **customer's hostname** is served from. It
contains no operator console, no signup intake, no provisioning and no
`control:*` commands. Not switched off, *absent*: from the filesystem, from the
autoloader and from the compiled container. Its firewalls are `dev` and `main`
only, and its router has no route under `/control`. The build refuses to finish
if any of that is untrue, so you do not have to take this paragraph's word for
it:

```console
docker run --rm --user root --entrypoint sh xivi-public -c 'ls /app/packages'
```

`frankenphp_prod` is what the **control-plane hostname** is served from, and
what `bin/deploy` runs out of.

### Give the public image a database user of its own

Both talk to the same control-plane database, so the boundary worth having is
not the network. It is the database user:

```console
bin/console deploy:registry-grants xivi_public   # prints the SQL; run it as a DBA
```

That role ends up with `SELECT` on the tenant registry and nothing else: no
writes, no DDL, and no access at all to the operator table.

**A release can grow the registry**, and a grant run last release does not
cover a table added in this one. Set `XIVI_PUBLIC_ROLE` to the role's name and
`bin/deploy` verifies the grants during every deploy (`deploy:check-grants`),
stopping while the old containers are still serving instead of letting a
customer's dashboard discover the missing table. It checks and does not repair;
the fix is re-running the printed SQL, which is idempotent.

**The customer-facing image therefore does not run the control-plane
migrations.** It checks that somebody else has and refuses to start if not, so
`bin/deploy` has to run before the public containers are replaced. That was
already the right order; it is now enforced.

### One image is still a supported deployment

A single-instance deployment, one image and one database user, keeps working
exactly as it did. All of the above is opt-in by building the second target,
and with `XIVI_PUBLIC_ROLE` empty the grant check stands down.

What the split buys, and the complete list of what the public image *does*
still contain, is [Two images](../architecture/two-images.md).

!!! note "Why it was built this way"

    What a deploy has to do and where each part of it runs is
    `docs/architecture.md` §4.2 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi); the image
    split and the grants are §4.4.
