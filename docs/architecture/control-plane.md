# The control plane

The control plane is the part of an installation that is about the installation
rather than about any customer: which customers exist, what they use, what the
modules cost, and who may administer any of it.

For getting one running, see
[The control plane](../getting-started/control-plane.md) in Getting Started.
This page is what it *is*.

## What it holds

One database, separate from every customer's:

- **the customers**: slugs, hostnames, statuses, and the encrypted credentials
  for their databases;
- **the operators**: the accounts that may sign in here;
- **what each customer uses**: user counts, last sign-in, record counts,
  collected on a schedule;
- **the module catalogue**: how far along each module is, and what this
  deployment charges for it;
- **purchase requests**: modules customers have asked to buy;
- **notices**: what the operator has announced to customers;
- **support tickets**, collected from customers, with the operator's answers.

**No customer's records are in it.** A contact, an order and an invoice live in
that customer's own database and nowhere else. An operator administering the
installation is not thereby able to read anybody's business.

## What an operator is

An operator is **not** a promoted user of some customer. It is a separate
account in a separate table, and holding one grants nothing inside any
customer's installation.

There is no sign-up, no invitation and no password reset. The first operator is
created from the command line, and so is every one after them. That is not an
omission: the surface that can see every customer is not one anybody should be
able to enrol themselves into.

Revoking an operator **deactivates rather than deletes**, so what they did
stays attributable to somebody who still exists. And **the last operator who
can sign in cannot be revoked**, because with no reset and no invitation there
would be no way back in.

## The hostname is not the boundary

The control plane is served on a hostname of its own, and **that hostname is
not what keeps people out.**

Anybody who can set the `Host` header reaches the sign-in page from any address
that terminates the connection. No setting changes that, and the trusted-
hostname configuration does not either, because the control-plane host is by
construction one of the names the installation answers to.

What actually keeps people out happens *after* the request arrives: the sign-in
itself, the operator accounts being a separate provider, and the role required
to reach anything behind it. All three treat a forged `Host` exactly as they
treat a real one. An address allow-list can additionally refuse requests for
where they came from; see
[Restricting it](../getting-started/control-plane.md#restricting-it-to-your-own-addresses).

The name is still worth choosing so it is not guessable from the customer-
facing domain. Not because guessing it grants anything, but because there is no
reason to advertise the address.

## Two ways it is reached

**A person**, signing in on that hostname.

**A schedule.** Scheduled commands keep it current, not background workers: one
collects what each customer uses, one gathers purchase requests, one gathers
support tickets, and one turns confirmed signups into customers. Until a
collector has run, the pages say the figures have not been collected rather
than showing a zero, which is the honest answer.

## It is not in every image

The strongest statement about what the control plane is, is where it **is
not**: the image a customer's hostname is served from does not contain it. Not
disabled, not routed away from. Absent.

See [Two images](two-images.md).

!!! note "Why it was built this way"

    The reasoning, including why a public surface never provisions directly, is
    in `docs/architecture.md` §8 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
