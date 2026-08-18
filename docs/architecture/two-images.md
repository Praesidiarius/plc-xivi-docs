# Two images

An installation is built as **two** container images from one repository:

| Target | Serves | Contains the administration surface |
| --- | --- | --- |
| `frankenphp_prod` | the control plane | **yes** |
| `frankenphp_public` | customers' hostnames | **no** |

They are the same application at the same version, from the same commit. What
differs is what is inside them.

## Why not one image with the admin routes switched off

Because **"not routed" and "not present" are different guarantees, and only the
second survives a mistake.**

A single image with a flag means the administration code is sitting on the
machine that faces the internet, one misconfiguration away from being served. An
image that does not contain it cannot serve it however it is configured, because
the files are not there.

The customer-facing build removes the administration package outright, and **the
build refuses to finish if any of it is still there** — checked by looking inside
the image rather than by checking that a route answers 404.

## What the customer-facing image can reach

Both images talk to the same control-plane database, because serving a customer
requires reading which customer a hostname belongs to and what the credentials for
their database are. That is a **read**.

So the boundary is not only which code is present — it is what the database will
allow:

- the customer-facing instance holds **`SELECT` and nothing else** on the tables
  it needs;
- it has **no privilege at all**, of any kind, on the operator accounts, the
  usage figures, or the purchase requests.

An `INSERT INTO tenant` from the process facing the internet is refused by
PostgreSQL. Not by a check somebody wrote — by the grant.

That distinction matters more than the image split, and it is the part worth
getting right first: absence of code is a property of a build, and a build can be
got wrong. A grant is enforced by something that does not care how the
application was assembled.

## What follows for deploying

**The control-plane migrations run from the internal image.** The customer-facing
one cannot run them — it has no privilege to — so on start it checks the schema
is current and refuses to serve if it is behind, rather than trying to fix it.

**Both images come from one build.** There is one repository, one version, one
set of migrations. A second repository would mean two owners of one schema and no
single history of it, which is a worse problem than the one it would solve.

**The deploy step runs from the internal image**, and refuses if it is asked to
run from the other — checked by looking for the package on disk, not by reading a
flag, because a directory is what is in the image and a flag is what somebody
configured.

!!! note "Why it was built this way"

    The reasoning — including what had to move before the application could
    compile without the administration package at all — is in
    `docs/architecture.md` §4.4 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
