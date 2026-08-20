# Getting Started

From an empty machine to an installation with a customer in it, in the order
the steps actually have to happen.

Docker serves Xivi, and the host needs nothing else: no PHP, no Composer, no
Node. Every command below runs in a container, which is why they all start with
`bin/`.

1. **[Installation](installation.md)**: clone it, start it, and confirm it is
   up.
2. **[The first operator](first-operator.md)**: an account that can administer
   the installation. There is no sign-up; this is a command.
3. **[The control plane](control-plane.md)**: where that operator signs in, and
   the hostname it needs.
4. **[The first customer](first-tenant.md)**: a tenant, its database, and
   somebody who can sign in to it.

## Where to go next

Once a customer exists and somebody can sign in to it,
[The Basics](../the-basics/index.md) explains what they are looking at:
records, the modules that shape them, and how a customer changes that shape
without anybody deploying anything.

If what you have just built is going to serve real customers, everything that
changes when it leaves your laptop, hostnames, secrets, the deploy step and the
cron entries, is [Running an installation](../running/index.md).

If you are deciding how to *run* this rather than how to use it,
[Architecture](../architecture/index.md) is the shorter road, particularly
[Two images](../architecture/two-images.md), which is the decision that most
affects how an installation is deployed.
