# Scheduled jobs

Three things an installation needs are commands on a schedule rather than
background workers. There is no worker process here and no message consumer, so
these are cron entries — the same constraint that makes mail synchronous.

They all carry on past a customer that failed, record the failure against that
customer, and **exit non-zero so that cron mails somebody**.

!!! warning "Cron mail is not monitoring"

    A non-zero exit only reaches you while the job is still running. Nothing in
    Xivi notices when one **stops** — a crontab that was never updated, a machine
    that is off, a deploy that replaced a container without its schedule. That is
    what [Monitoring](monitoring.md) is for, and it takes about five minutes to
    set up.

## Ask the installation what it needs

Rather than copying the lines below, ask the version you are actually running:

```console
bin/console deploy:crontab
```

It prints the cron entries **this build** needs, what goes stale without each
one, and whether anything is watching it. The output is a crontab, comments
included, so it can be redirected rather than retyped:

```console
bin/console deploy:crontab --directory=/srv/xivi > /etc/cron.d/xivi
```

Run it again after every upgrade. A release that adds a scheduled job adds it
there, which is the only place that cannot fall behind the code.

## The three jobs

### Turning self-service signups into customers

Only needed on a deployment that has set `SIGNUP_HOST`. Signup records a request
and **provisions nothing** — the endpoint is anonymous and the thing that creates
a customer holds `TENANT_ADMIN_DSN`, so the two are deliberately kept apart.
Nothing happens to a confirmed signup until this runs:

```cron
*/5 * * * *  cd /srv/xivi && bin/console signup:provision
```

The cadence is yours and is a customer-facing latency rather than a housekeeping
one: somebody who has just confirmed their address is waiting for the mail this
sends, so every five minutes is a better default here than a nightly one.

Each run creates a role, a database, a schema and a first administrator, then
mails that person an invitation link; **no password is generated or printed**.
Running it again is safe: a customer left half-made by a run that died is cleared
and rebuilt, and one that is already standing is finished rather than duplicated.
A half-provisioned customer also appears at the top of the tenant list, named in
its banner, so the failure is visible to somebody who never reads a cron mail.

**It is the privileged half of the feature.** When the public and internal
deployments are separated it belongs on the internal one; today it needs
`TENANT_ADMIN_DSN` in whatever environment the cron runs in.

### Collecting what customers have asked to buy

A customer pressing *ask about this module* inside their own installation writes
that request into **their own database**, because that is the only database their
request is allowed to write to. The operator screen reads the control plane, and
this is the only thing that joins the two:

```cron
*/10 * * * *  cd /srv/xivi && bin/console tenant:purchase:collect
```

Without it, a customer's request never reaches anybody and nothing anywhere looks
wrong. There is no error, no banner and no empty state that differs from an
installation nobody has asked to buy anything from.

A customer whose database cannot be reached keeps whatever was collected from
them last time — a network hiccup does not blank their requests — and the run
carries on with the rest.

### Keeping the usage figures fresh

The tenant list shows what each customer uses — users, last sign-in, records —
and **nothing collects those figures for you**. Until the command has run, every
row reads *not collected yet*, which is honest and is not useful:

```cron
17 3 * * *  cd /srv/xivi && bin/console tenant:usage:collect
```

The cadence is yours: the page states how old what it shows is, so hourly and
weekly both tell the truth about themselves. A customer whose database cannot be
reached is recorded as *could not be read* and the run carries on with the rest.

!!! note "Why it was built this way"

    Why the tenant list does not fetch its own figures is `docs/architecture.md`
    §8.11 of the [main repository](https://github.com/Praesidiarius/plc-xivi);
    the provisioning design is §8.14, why a purchase request is collected rather
    than posted is §8.15, and why a public surface never provisions directly is
    §8.12.
