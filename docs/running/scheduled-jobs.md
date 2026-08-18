# Scheduled jobs

Two things an installation needs are commands on a schedule rather than
background workers. There is no worker process here and no message consumer, so
these are cron entries — the same constraint that makes mail synchronous.

Both carry on past a customer that failed, record the failure against that
customer, and **exit non-zero so that cron mails somebody**.

## Keeping the usage figures fresh

The tenant list shows what each customer uses — users, last sign-in, records —
and **nothing collects those figures for you**. Until the command has run, every
row reads *not collected yet*, which is honest and is not useful:

```cron
17 3 * * *  cd /srv/xivi && bin/console tenant:usage:collect
```

The cadence is yours: the page states how old what it shows is, so hourly and
weekly both tell the truth about themselves. A customer whose database cannot be
reached is recorded as *could not be read* and the run carries on with the rest.

## Turning self-service signups into customers

Only needed on a deployment that has set `SIGNUP_HOST`. Signup records a request
and **provisions nothing** — the endpoint is anonymous and the thing that creates
a customer holds `TENANT_ADMIN_DSN`, so the two are deliberately kept apart.
Nothing happens to a confirmed signup until this runs:

```cron
*/5 * * * *  cd /srv/xivi && bin/console signup:provision
```

The cadence is yours and is a customer-facing latency rather than a housekeeping
one: somebody who has just confirmed their address is waiting for the mail this
sends, so every five minutes is a better default here than the nightly one above.

Each run creates a role, a database, a schema and a first administrator, then
mails that person an invitation link; **no password is generated or printed**.
Running it again is safe: a customer left half-made by a run that died is cleared
and rebuilt, and one that is already standing is finished rather than duplicated.
A half-provisioned customer also appears at the top of the tenant list, named in
its banner, so the failure is visible to somebody who never reads a cron mail.

**It is the privileged half of the feature.** When the public and internal
deployments are separated it belongs on the internal one; today it needs
`TENANT_ADMIN_DSN` in whatever environment the cron runs in.

!!! note "Why it was built this way"

    Why the tenant list does not fetch its own figures is `docs/architecture.md`
    §8.11 of the [main repository](https://github.com/Praesidiarius/plc-xivi);
    the provisioning design is §8.14, and why a public surface never provisions
    directly is §8.12.
