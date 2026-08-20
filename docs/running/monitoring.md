# Monitoring

**What to watch, and how.** This page is about the one thing an installation
cannot tell you about itself: whether its [scheduled jobs](scheduled-jobs.md)
are still running.

## The problem, in one paragraph

Every screen built on a scheduled job is honest about staleness. A usage figure
shows the moment it was taken. A customer nobody has collected yet reads *not
collected yet* rather than a misleading zero. A purchase request is never drawn
as current when it is not.

All of that tells whoever looks. **Nothing makes anybody look.** A customer who
confirmed their signup and heard nothing is exactly what a stopped
`signup:provision` produces: no error anywhere, no banner, no log line, and the
first report arrives from them.

## What to watch

Three things, in the order they hurt.

1. **The scheduled jobs**, below. Nothing else in Xivi notices these stopping.
2. **That the site is up.** An HTTP check against a customer hostname and
   against `CONTROL_PLANE_HOST`. Xivi needs to know nothing about this;
   configure it at whatever service you use.
3. **Disk and database.** Ordinary server monitoring; nothing here is special.
   Provisioning a customer creates a database, so free space is a business
   constraint rather than a housekeeping one.

The rest of this page is the first.

## How: the job pings, the service alerts

Xivi does **not** ship a checker that looks at whether jobs have run. Such a
checker would itself be a scheduled job, so the failure it exists to catch,
cron stopped or the machine off, is the failure that stops it, and a dead man's
switch that dies with the patient reports nothing at all.

Instead the jobs ping a URL when they run, and an **external** service raises
the alarm when a ping does not arrive. The thing watching is not the thing
being watched, so silence is the alert.

### 1. Pick a service

| | Self-hostable | Licence | Works with Xivi |
| --- | --- | --- | --- |
| [Healthchecks](https://healthchecks.io) | **Yes** | BSD-3-Clause | **Fully**, and the recommendation |
| [Better Stack](https://betterstack.com) | No | Proprietary | Fully, same ping protocol |
| [Oh Dear](https://ohdear.app) | No | Proprietary | Partly, success pings only |
| [Cronitor](https://cronitor.io) | No | Proprietary | Partly, success pings only |

**Healthchecks is recommended because you can run it yourself.** Xivi does not
require a paid service to know whether its own cron is alive; a container
beside this one is a perfectly good deployment of it, and there is a free
hosted tier for whoever would rather not.

"Partly" for the last two means the *ran* signal arrives and the exit code does
not, because they spell that part of the protocol differently. You will be told
a job stopped; you will not be told which way it failed.

### 2. Create one check per job

At the service, create one check per line that `deploy:crontab` prints, with a
period matching your cron entry and a grace time comfortably longer than the
job takes. Copy each check's ping URL.

### 3. Set `XIVI_MONITOR_PINGS`

Comma-separated `command=url` pairs, one per watched job:

```ini
XIVI_MONITOR_PINGS=signup:provision=https://hc-ping.com/aaaa,tenant:purchase:collect=https://hc-ping.com/bbbb,tenant:support:collect=https://hc-ping.com/cccc,tenant:usage:collect=https://hc-ping.com/dddd
```

**Empty, the default, means no pings and no other change whatsoever.** Nothing
leaves your installation until you set this.

A malformed entry is refused loudly on the next console command rather than
skipped, because a skipped entry is a job nobody is watching on an installation
whose operator believes they configured watching.

### 4. Check your work

```console
bin/console deploy:crontab
```

It says how many jobs are watched, names the ones that are not, and **exits 3**
when some are watched and others are not. That is the state you land in by
setting this up once and later upgrading to a release with a new job, and the
state that otherwise looks exactly like being covered.

## What is sent

The fact that the job ran, and its exit code. That is all.

- A `GET`, with no request body and no query string.
- **No tenant slug, no customer name, no email address, no record counts, no
  hostname, and no version number.** A ping URL goes to a third party, so
  *"the job ran"* is the entire payload.
- `<url>/start` when a job begins, `<url>/<exit code>` when it ends.

The exit code is sent as a number rather than as a plain "failed" because
Xivi's codes mean different things: `0` is fine, `1` is *the run could not
happen at all*, and `3` from `tenant:migrate` is *the run happened and some
customers are behind while the rest are fine*. Those need different responses,
and a monitor that shows the number tells you which one you have before you
open a terminal.

A ping that cannot be sent is written to the log and **never fails the job**.
The consequence of a lost ping is a monitor reporting a missing ping, which is
what it is for.

!!! warning "A ping URL is a password"

    Anybody holding one can report that the job succeeded, which is how
    somebody would silence this. Keep them out of world-readable files;
    `deploy:crontab` prints *watched* and deliberately never prints the
    address.

## What this still does not cover

**A job can still stop without anybody finding out if nobody created a check
for it.** Leaving `XIVI_MONITOR_PINGS` empty is supported and is the default,
and an installation left that way is exactly as blind as before. What changed
is that the gap is now visible: run `deploy:crontab` and it tells you.

Xivi also has no way to tell your customers it is down. Notices are authored in
advance for a working installation and are not an incident channel; a status
page is a separate decision that has not been taken.

!!! note "Why it was built this way"

    The comparison of the four services, why an in-house checker is rejected
    for good, and what a ping deliberately does not contain are
    `docs/architecture.md` §4.5 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
