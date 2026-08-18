# Running an installation

For whoever **deploys Xivi and keeps it running**. Getting Started takes an empty
machine to an installation with one customer in it; these pages are what changes
when that installation is not on your laptop.

Everything here is a deployment decision — a hostname, a secret, a cron entry, a
build target — and each one is a thing somebody has to get right once rather than
a thing the application can work out for itself.

In the order you would meet them:

1. **[Configuration](configuration.md)** — what is set by environment variable,
   the placeholder secrets that must be replaced, what a module costs, and how
   the encryption key is rotated.
2. **[Hostnames](hostnames.md)** — which names this installation answers to, what
   happens when that is wrong, and what a load balancer in front changes.
3. **[Deploying](deploying.md)** — the step a deploy has to run, the two images
   and which one goes where.
4. **[Scheduled jobs](scheduled-jobs.md)** — the two cron entries an installation
   needs, and what is stale without them.
5. **[Self-service signup](signup.md)** — off unless you switch it on, and what
   switching it on involves.
6. **[Commands](commands.md)** — the console commands an installation is
   administered with.

!!! note "Working on Xivi rather than running it?"

    Tests, `bin/ci`, the package layout and the development tooling are in
    `DEVELOPING.md` of the
    [main repository](https://github.com/Praesidiarius/plc-xivi/blob/main/DEVELOPING.md),
    because they have to travel with the commit that changes them.
