# Deploying

There is no deploy tool here yet. Which host, which registry and how a rollback
works are still open. What *is* here is the step a deploy has to run, whichever
tool eventually does it.

## The step

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
