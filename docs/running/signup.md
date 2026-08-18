# Self-service signup

**Leaving `SIGNUP_HOST` empty means there is no signup endpoint** — not a route
that refuses, but a routing table with nothing in it. That is the default, and it
is the right one for an installation whose customers are provisioned by hand.

## Switching it on

Two variables:

```ini
SIGNUP_HOST=signup.example.com
XIVI_SIGNUP_SECRET=…   # php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

- **It must not be `CONTROL_PLANE_HOST`.** That host answers to people who can
  see every customer; this one is configured into your marketing site. The
  application refuses to build a routing table when the two are equal.
- **The secret is not optional.** An empty one refuses everybody, and the
  application will not start with `SIGNUP_HOST` set and no secret.
- **`MAILER_SENDER` is optional here as everywhere else.** An empty one sends the
  confirmation from `no-reply@` at `SIGNUP_HOST`, which is the usual fallback
  with the customer's hostname replaced by this one — honest for the same reason,
  since that is the name the visitor's site just posted to.

Remember that `XIVI_TRUSTED_DOMAINS` has to admit the domain provisioned
customers will be served on, which is the parent of `SIGNUP_HOST` —
see [Hostnames](hostnames.md).

## What the endpoint does

Deliberately little: it records a signup and mails the address a confirmation
link. It creates **no tenant, no database and no role**. That happens later,
from a cron entry an operator can see — [Scheduled jobs](scheduled-jobs.md).

The request and response shapes are documented on the signup controller in the
main repository, and `XIVI_SIGNUP_PLANS` decides which plans a caller may ask
for, most ordinary first.

## The site that posts to it

The landing page is not part of Xivi. Prefer a **server-side post** from that
site over a browser posting directly: the credential then lives on a server
rather than in a page's source, and this endpoint stays off any public CORS
origin list — there is deliberately no CORS configuration for it.

!!! note "Why it was built this way"

    Why a public surface never provisions directly is `docs/architecture.md`
    §8.12 of the [main repository](https://github.com/Praesidiarius/plc-xivi).
