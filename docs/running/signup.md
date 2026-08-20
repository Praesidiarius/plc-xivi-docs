# Self-service signup

**Leaving `SIGNUP_HOST` empty means there is no signup endpoint.** Not a route
that refuses; a routing table with nothing in it. That is the default, and it
is the right one for an installation whose customers are provisioned by hand.

## Switching it on

```ini
SIGNUP_HOST=signup.example.com
XIVI_SIGNUP_SECRET=…   # php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
SIGNUP_PAGE=true
```

- **It must not be `CONTROL_PLANE_HOST`.** That host answers to people who can
  see every customer; this one is configured into a marketing site. The
  application refuses to build a routing table when the two are equal.
- **The secret is not optional.** An empty one refuses everybody, so a
  deployment that set the host and forgot the secret has a feature that does
  not work rather than an open endpoint.
- **`MAILER_SENDER` is optional here as everywhere else.** An empty one sends
  the confirmation from `no-reply@` at `SIGNUP_HOST`, which is the name the
  form was just submitted to.

Remember that `XIVI_TRUSTED_DOMAINS` has to admit the domain provisioned
customers will be served on, which is the parent of `SIGNUP_HOST`. See
[Hostnames](hostnames.md).

## Page, endpoint, or both

`SIGNUP_PAGE` decides whether Xivi also draws the form:

| | What a visitor gets |
| --- | --- |
| `SIGNUP_HOST` set, `SIGNUP_PAGE` on | Xivi's own signup page, on that host |
| `SIGNUP_HOST` set, `SIGNUP_PAGE` off | The endpoint alone, for a site of your own |
| `SIGNUP_HOST` empty | Nothing; no route exists |

The built-in page shows the visitor the address they will get as they type
their company name, lets them edit it, and posts to the same contract any
other site would. A deployment with its own marketing site leaves the page off
and posts to the endpoint from there.

## What the endpoint does

Deliberately little: it records a signup and mails the address a confirmation
link. It creates **no tenant, no database and no role.** That happens later,
from a cron entry an operator can see. See
[Scheduled jobs](scheduled-jobs.md).

The request and response shapes are documented on the signup controller in the
main repository, and `XIVI_SIGNUP_PLANS` decides which plans a caller may ask
for, most ordinary first.

## A site of your own that posts to it

Prefer a **server-side post** over a browser posting directly: the credential
then lives on a server rather than in a page's source, and this endpoint stays
off any public CORS origin list. There is deliberately no CORS configuration
for it.

!!! note "Why it was built this way"

    Why a public surface never provisions directly is `docs/architecture.md`
    §8.12 of the [main repository](https://github.com/Praesidiarius/plc-xivi);
    the built-in page, and why it posts to its own public contract, is §8.13.
