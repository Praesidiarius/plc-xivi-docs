# Hostnames

A hostname is how a request finds its customer
([Tenancy](../architecture/tenancy.md)), so which names an installation answers
to is a deployment decision rather than a detail. There are three settings, and
only the first is likely to bite you.

## Which hostnames this installation answers to

`XIVI_TRUSTED_DOMAINS` is the list. **Empty is the default and it means the
`Host` header is not checked**, which is what a fresh checkout and the test
suite need. On a real deployment, set it:

```ini
XIVI_TRUSTED_DOMAINS=xivi.app,1plc.ch
```

Each entry admits the domain **and every hostname under it**, so `xivi.app`
covers `xivi.app`, `acme.xivi.app` and `acme.eu.xivi.app`. Names, not patterns.
This is deliberately not a regular expression, because every dot in a
hand-written one matches any character, and `control.example.com` would then
also accept `controlXexample.com`, a name somebody else can own. The
application writes the expressions and anchors them.

**You do not list the control plane, the signup host, the loopback or the
container name.** Every host this installation serves *without* a customer is
added for you, so setting this variable cannot lock an operator out of their
own console. What you have to get right is the domain your **customers** are
on, including, if self-service signup is switched on, the parent domain of
`SIGNUP_HOST`, since that is where a provisioned signup is routed:
`signup.xivi.app` puts customers at `acme.xivi.app`, so `xivi.app` is the entry
you want.

### What happens if it is wrong

The framework answers a hostname outside the list with an empty **400 Bad
Request** before any of the application runs: no page, no header named, nothing
in the body. That is the correct response to send a stranger and a terrible one
to debug, so three things say it out loud instead:

- `tenant:provision` **refuses** to create a customer on a hostname this
  installation would refuse, naming the variable. The mistake normally fails at
  a console rather than at a customer's browser.
- `deploy:check-hosts` prints what the installation answers to and names every
  customer that would get a 400. `bin/deploy` runs it before the serving
  containers are replaced and stops the deploy (exit 3) if a customer that is
  serving today would be refused. The container entrypoint runs it on every
  start and only prints, because one customer's hostname must not stop every
  container from coming up.
- A refused request writes one `error` line naming the host that was sent, what
  the pattern admits, and what to change.

## If there is a load balancer in front

`TRUSTED_PROXIES` is empty by default, and that is correct for the shipped
topology: the application terminates TLS itself, so nothing is in front of it
and `X-Forwarded-*` headers are ignored. That is the safe default, and it is
also why a deployment that *does* have a proxy sees the proxy's address
everywhere instead of the client's.

```ini
TRUSTED_PROXIES=10.0.0.0/8          # or private_ranges, or REMOTE_ADDR
```

Which forwarded headers are then believed is decided in the application's
framework configuration and is not a deployment setting. `X-Forwarded-For`,
`-Proto` and `-Port` are trusted; `X-Forwarded-Host` and `X-Forwarded-Prefix`
deliberately are not. Tenant routing *is* the `Host` header, and most proxies
append forwarded headers rather than replacing them, so believing
`X-Forwarded-Host` would let a caller pick which customer they are and which
host appears in a mailed invitation link.

**Set this if you have a proxy.** Without it, absolute URLs generated while
serving, the invitation links in the mails Xivi sends above all, come out as
`http://` behind a TLS-terminating balancer.

## The hostname the control plane is served on

`CONTROL_PLANE_HOST` is a deployment step of its own, and skipping it leaves
the control plane on `control.localhost`, the development default and not a
hostname anybody will reach in production. Setting it, choosing a name, and
what follows from the choice is
[The control plane](../getting-started/control-plane.md) in Getting Started.

It does not have to appear in `XIVI_TRUSTED_DOMAINS`; it is added for you,
along with every other host served without a customer.

**The hostname is not a boundary, and there is a setting that is.** Anybody who
can set the `Host` header reaches the control-plane sign-in page from any
address that terminates the connection, and no hostname setting changes that.
`CONTROL_PLANE_ALLOWED_IPS` restricts that host to a list of addresses and CIDR
ranges, which is the one thing that can refuse a request for *where it came
from*. It is empty by default, it never affects a customer, and it is described
with its check command, and its lock-yourself-out warning, under
[The control plane](../getting-started/control-plane.md#restricting-it-to-your-own-addresses).

If you set it **and** you have a proxy in front, set `TRUSTED_PROXIES` too. The
allow-list matches against the same resolved client address as everything else
on this page, so without it every request looks as though it came from your
balancer.

!!! note "Why it was built this way"

    The reasoning, why the application composes the pattern rather than reading
    a configured regex, and why the control-plane hostname is not a security
    boundary, is in `docs/architecture.md` §4.3 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi); the address
    allow-list, and why it reads no header of its own, is §8.9.
