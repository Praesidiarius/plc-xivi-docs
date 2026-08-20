# Configuration

Xivi is configured by environment variable and by nothing else. There are
deliberately no per-customer configuration files, because who the customers are
lives in the control-plane database rather than on disk.

Values are set in `.env` for development; on a real deployment, override them
with real environment variables or with the Symfony secrets vault.

## The variables

| Variable | Purpose |
| --- | --- |
| `DATABASE_URL` | Control-plane database |
| `TENANT_DSN_TEMPLATE` | Template for new tenant DSNs; `{database}` and `{user}` are substituted |
| `TENANT_ADMIN_DSN` | Used **only** by provisioning, for `CREATE DATABASE` / `CREATE ROLE` |
| `TENANT_SECRET_KEYS` | `{"id": "base64 32 bytes"}`: the keys that encrypt tenant passwords |
| `TENANT_SECRET_KEY_ID` | Which of those keys new values are written with |
| `XIVI_TRUSTED_DOMAINS` | The domains this installation answers to, comma separated. **Empty means the `Host` header is not checked at all**. See [Hostnames](hostnames.md) |
| `TRUSTED_PROXIES` | Addresses of a reverse proxy in front of this application. Empty means `X-Forwarded-*` is ignored. See [Hostnames](hostnames.md) |
| `CONTROL_PLANE_HOST` | The hostname the control plane is served on. See [The control plane](../getting-started/control-plane.md) |
| `CONTROL_PLANE_ALLOWED_IPS` | Addresses and CIDR ranges that may reach the control plane. **Empty means no restriction**; customers are never affected. See [The control plane](../getting-started/control-plane.md#restricting-it-to-your-own-addresses) |
| `SIGNUP_HOST` | The hostname the public signup endpoint is served on. **Empty means no signup route exists at all**. See [Self-service signup](signup.md) |
| `SIGNUP_PAGE` | Whether Xivi also draws the signup form on that host. Off, and the endpoint alone serves a site of your own. See [Self-service signup](signup.md) |
| `XIVI_SIGNUP_SECRET` | The shared secret the calling site presents in `X-Xivi-Signup-Key` |
| `XIVI_SIGNUP_PLANS` | Which plans self-service may ask for, comma separated, most ordinary first |
| `XIVI_PUBLIC_ROLE` | The database role the customer-facing image runs as, so a deploy can verify its grants. Empty means a single-image deployment, and the check stands down. See [Deploying](deploying.md) |
| `PRICE_CURRENCY` | The ISO 4217 code this deployment's module price list is in. **Empty means prices render as bare numbers**. See below |
| `XIVI_MAX_ATTACHMENT_BYTES` | The largest document a mail may carry, chosen against what receiving servers accept. The default is 7 MiB |
| `XIVI_MONITOR_PINGS` | `command=<ping url>` pairs, comma separated: each watched scheduled job pings its URL when it runs. **Empty means nothing is sent anywhere and nothing else changes**. See [Monitoring](monitoring.md) |
| `MAILER_DSN` | Where mail this installation sends goes |
| `MAILER_SENDER` | The address this installation sends as. Empty is allowed. See below |

## Before deploying anywhere real

The values committed in `.env` are placeholders and are public. Replace at
minimum `APP_SECRET`, `TENANT_SECRET_KEYS`, `CONTROL_PLANE_HOST` and the
PostgreSQL password, and set `XIVI_TRUSTED_DOMAINS`. Until you do, this
installation answers to any hostname that reaches it, and any of them can end
up in an invitation link.

```console
php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'   # a TENANT_SECRET_KEYS value
```

`TENANT_ADMIN_DSN` should name a role with `CREATEDB` and `CREATEROLE` rather
than a superuser, and ideally should not be present in the web processes'
environment at all.

**Two of those are enforced rather than advised.** An instance starting with
`APP_ENV=prod` on the `APP_SECRET` or the `TENANT_SECRET_KEYS` committed in
`.env` **refuses to start**, naming the variable and printing the command that
generates a real one. The check is `deploy:check-secrets`, the entrypoint runs
it before anything touches a database, and it does nothing at all in
development or in the test suite, both of which run on those placeholders on
purpose.

The failure it exists for is quiet: the image build compiles `.env` into
`.env.local.php`, a real environment variable overrides it, and a deployment
that supplies none runs on a published secret while looking perfectly healthy.

## Where a customer's mail comes from

Where a customer's mail comes from is **their own setting**, on their company
profile: a sender address, and optionally their SMTP server, in which case the
mail is genuinely from them. Without a server it goes out through this
installation, with their name on it and their address to reply to.

`MAILER_SENDER` is the address this installation sends as; leaving it empty
uses `no-reply@` at the hostname that customer reaches you on.

## What this deployment charges for a module

The prices themselves are **not** environment variables. They live on the
control-plane `module` row, and an operator sets them at `/control/modules` or
with `module:price`, because a price somebody has to edit in a file is a price
nobody can change without a deploy.

`PRICE_CURRENCY` is the one part that is a deployment fact, and it is **not**
the currency on a customer's profile. That one is about the invoices *they*
write; this is what the company running Xivi charges. A deployment picks it
once, and changing it invalidates every figure on the list at the same moment,
because 49.00 CHF and 49.00 EUR are not the same offer. That is a re-pricing
exercise rather than an edit.

```ini
PRICE_CURRENCY=CHF
```

Left empty, prices show as plain numbers and the operator screen says which
variable to set. Nothing is guessed: a currency guessed for somebody is wrong
quietly.

What being priced does to a module, and why a module with no price is not free,
is [Modules](../the-basics/modules.md).

## Rotating the encryption key

Stored secrets record which key wrote them, so both keys are valid during the
changeover and the job is resumable:

1. Add a new key to `TENANT_SECRET_KEYS`, point `TENANT_SECRET_KEY_ID` at it.
2. Run `tenant:rotate-secrets` until it reports nothing stale.
3. Remove the old key.

!!! note "Why it was built this way"

    The reasoning behind these, why a price lives in the registry (§6.5), why
    mail is synchronous and what the sender fallback is honest about (§8.7),
    and what the secret check exists for (§4.2), is in `docs/architecture.md`
    of the [main repository](https://github.com/Praesidiarius/plc-xivi).
