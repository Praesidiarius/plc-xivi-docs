# Hostnames

A hostname is how a request finds its customer ([Tenancy](../architecture/tenancy.md)),
so which names an installation answers to is a deployment decision rather than a
detail. There are three settings, and only the first is likely to bite you.

## Which hostnames this installation answers to

`XIVI_TRUSTED_DOMAINS` is the list. **Empty is the default and it means the
`Host` header is not checked**, which is what a fresh checkout and the test suite
need. On a real deployment, set it:

```ini
XIVI_TRUSTED_DOMAINS=xivi.app,1plc.ch
```

Each entry admits the domain **and every hostname under it**, so `xivi.app`
covers `xivi.app`, `acme.xivi.app` and `acme.eu.xivi.app`. Names, not patterns —
this is deliberately not a regular expression, because every dot in a
hand-written one matches any character and `control.example.com` would then also
accept `controlXexample.com`, a name somebody else can own. The application
writes the expressions and anchors them.

**You do not list the control plane, the signup host, the loopback or the
container name.** Every host this installation serves *without* a customer is
added for you, so setting this variable cannot lock an operator out of their own
console. What you have to get right is the domain your **customers** are on —
including, if self-service signup is switched on, the parent domain of
`SIGNUP_HOST`, since that is where a provisioned signup is routed
(`signup.xivi.app` puts customers at `acme.xivi.app`, so `xivi.app` is the entry
you want).

### What happens if it is wrong

A hostname outside the list is answered with an empty **400 Bad Request** by the
framework, before any of the application runs: no page, no header named, nothing
in the body. That is the correct response to send a stranger and a terrible one
to debug, so three things say it out loud instead:

- `tenant:provision` **refuses** to create a customer on a hostname this
  installation would refuse, naming the variable. So the mistake normally fails
  at a console rather than at a customer's browser.
- `deploy:check-hosts` prints what the installation answers to and names every
  customer that would get a 400. `bin/deploy` runs it before the serving
  containers are replaced and stops the deploy (exit 3) if a customer that is
  serving today would be refused; the container entrypoint runs it on every
  start and only prints, because one customer's hostname must not stop every
  container from coming up.
- A refused request writes one `error` line naming the host that was sent, what
  the pattern admits and what to change.

## If there is a load balancer in front

`TRUSTED_PROXIES` is empty by default and that is correct for the shipped
topology: the application terminates TLS itself, so nothing is in front of it and
`X-Forwarded-*` headers are ignored — which is the safe default, and also the
reason a deployment that *does* have a proxy sees the proxy's address everywhere
instead of the client's.

```ini
TRUSTED_PROXIES=10.0.0.0/8          # or private_ranges, or REMOTE_ADDR
```

Which forwarded headers are then believed is decided in the application's
framework configuration and is not a deployment setting: `X-Forwarded-For`,
`-Proto` and `-Port` are trusted, `X-Forwarded-Host` and `X-Forwarded-Prefix`
deliberately are not. Tenant routing *is* the `Host` header, and most proxies
append forwarded headers rather than replacing them, so believing
`X-Forwarded-Host` would let a caller pick which customer they are and which host
appears in a mailed invitation link.

**Set this if you have a proxy.** Without it, absolute URLs generated while
serving — the invitation links in the mails Xivi sends above all — come out as
`http://` behind a TLS-terminating balancer.

## The hostname the control plane is served on

`CONTROL_PLANE_HOST` is a deployment step of its own, and skipping it leaves the
control plane on `control.localhost` — the development default, and not a
hostname anybody will reach in production. Setting it, choosing a name, and what
follows from the choice is
[The control plane](../getting-started/control-plane.md) in Getting Started.

It does not have to appear in `XIVI_TRUSTED_DOMAINS`; it is added for you, along
with every other host served without a customer.

!!! note "Why it was built this way"

    The reasoning — why the pattern is composed by the application rather than
    configured, and why the control-plane hostname is not a security boundary —
    is in `docs/architecture.md` §4.3 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
