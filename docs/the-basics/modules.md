# Modules

A module is a unit a customer installs: **Contacts**, **Articles**, **Orders**,
**Invoices**, **Vouchers**. Each brings a kind of record, the fields it starts
with, and whatever rules go with it — an order's states, an invoice's totals, a
voucher's redemption limit.

## Installing one

Installing happens in the **store**, inside a customer's own installation. It
creates the tables and writes the starting field definitions into that customer's
database. Nothing is shared with any other customer, and nothing is deployed.

**Modules can require each other.** An invoice needs an order; an order needs a
contact. The store knows, and installs them in an order that works rather than
asking somebody to.

**Some modules only *use* another.** Vouchers work perfectly well without
Articles — you just do not get the "free article" kind of voucher, and the option
is not offered. That is different from a requirement, and the difference is the
point: a customer wanting `GIVE-10` off a total should not have to sell articles.

## Presets

A module can offer more than one starting shape. Contacts ships **basic** and
**extended** — the same module, with a fuller set of fields to begin from.

A preset is a *starting point*, not a mode. Once installed, the fields are the
customer's like any others.

## Installing does not retro-fit

**A module that gains a field in a later release does not add it to customers who
already installed it.** That is the rule, and it is deliberate: those definitions
are the customer's now — they have renamed things, added their own, removed ones
they did not want — and a release that silently rewrote them would overrule
exactly that.

But it should not mean *never*, so there is a screen for it: a module's page
shows what the module has gained since it was installed, and a customer can take
what they want, item by item.

- It **only adds**. Nothing is removed and nothing existing is changed.
- A field the customer has **already got** is never offered, whatever they have
  since renamed it to.
- A field they **declined or deleted** stops being offered — the answer is
  remembered, so nobody is asked the same question twice. It stays on a list they
  can take back.
- A rule the existing records could not satisfy — a newly required field on
  records that are empty in it — **arrives switched off**, and the page says
  which and why.

## Priced modules

Modules can be free or cost money, and **what they cost is not in the code** —
whoever runs the installation decides, on the control plane. One deployment may
sell the invoice module, another bundle it, a third run Xivi for one company and
sell nothing.

A module is in one of four states as far as buying goes:

| | In the store |
| --- | --- |
| **Not priced** — nobody has decided | no |
| **Free** | **yes** |
| **Priced** | **yes** |
| **Not for sale** — this deployment does not sell it | no |

**A module with no price is not free.** Free is a decision somebody made and it
says so; unpriced means the question has not been answered, and until it is the
module is not offered.

## Asking to buy

Requesting a priced module **does not install it**. There is no payment page and
no card form — Xivi takes no payments. What happens instead: the request is
written down, and whoever runs the installation sees it and either installs the
module or gets in touch.

That is honest about what is actually going on, and it is the shape the rest of
the product uses: anyone may ask, and the thing happening is a separate event.

Being able to *buy* is its own permission, separate from being able to *install*.
Somebody may be trusted to decide what the installation consists of without being
the person who commits the company to a payment.

!!! note "Why it was built this way"

    The reasoning — including why a price lives in the registry rather than in a
    module's code — is in `docs/architecture.md` §6 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
