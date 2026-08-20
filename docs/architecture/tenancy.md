# Tenancy

One installation serves many customers. Each customer, a **tenant**, has their
own PostgreSQL database, their own PostgreSQL role, their own hostname and
their own users. Nothing is shared between them except the code.

## How a request finds its customer

**The hostname decides.** A request arriving at `acme.example.com` is served
from Acme's database; one arriving at `beta.example.com` is served from Beta's.
There is no customer identifier in the URL, no `?tenant=` and nothing in the
session. The name the browser asked for is the whole of it.

That resolution happens **before authentication**, which is the part worth
understanding. By the time a sign-in form is submitted, the request is already
pointed at one customer's database, so a password from one customer cannot be
tried against another, and a signed-in session cannot follow a link into
somebody else's installation. The separation is not a filter a query might
forget to apply. It is which database the connection is attached to.

A hostname no customer claims **is not served**. It does not fall back to a
default customer, and it does not show a chooser. It answers 404, the same way
a page that does not exist would.

## What "their own database" buys

**A query cannot reach across.** The most common way multi-tenant systems leak
is a query that forgets its `WHERE tenant_id = …`. That clause does not exist
here, so nobody can forget it. A report, an export, a badly written filter:
none of them can return another customer's rows, because the connection has no
access to them.

**Customers can differ.** Because each database is that customer's own, they
can have different fields on a contact, different states on an order, and
different modules installed. That is the point of the product, and it is only
comfortable because the schemas are genuinely separate.

**Backup and removal are per customer.** Taking a dump of one customer, or
deleting one, is an operation on one database rather than a careful delete
across shared tables.

The cost is real and worth stating: **a schema change has to reach every
database.** That is a deploy step rather than a migration somebody runs once,
and it is why the deployment sequence has a tenant-migration stage in it.

## What is shared

One database, the **control plane**, holds the things that are about the
installation rather than about any customer: which customers exist, which
hostnames they answer to, what each one's status is, and the encrypted
credentials for their databases. It also holds the operator accounts.

**No customer's records are in it**, and no customer's request can write to it.
In a production deployment the customer-facing instance holds read-only
privileges on those tables. See [Two images](two-images.md).

Some hostnames are served **without** a customer at all: the control plane's
own, and the self-service signup host when it is switched on. Those are the
only addresses where tenant resolution is deliberately skipped, and a customer
cannot be provisioned onto one; the command refuses.

## A customer's status

Not every registered customer is served. Status is a fact about the
installation's relationship with them, not about their data:

| Status | Serves requests | Means |
| --- | --- | --- |
| `provisioning` | no | Being created. The database may not exist yet. |
| `trial` | **yes** | Serving, on trial terms. |
| `active` | **yes** | Serving. |
| `suspended` | no | Kept intact and refusing requests: unpaid, disputed, on hold. |

**Suspending is not deleting.** A suspended customer's database, records and
users are untouched; the installation simply stops answering for them.
Reversing it is a status change.

## Credentials

Each customer's database has its own PostgreSQL role, and the password for it
is stored **encrypted** in the control plane rather than in a configuration
file. A copy of the control-plane database on its own therefore does not yield
access to customers' data. The keyring is separate, and rotating it is a
command.

## What this means in practice

- **Adding a customer** creates a database, a role, and a registration. It is
  one command and takes seconds. See
  [The first customer](../getting-started/first-tenant.md).
- **A customer's hostname is theirs**, and more than one can point at the same
  customer.
- **Removing a customer** drops the database and the role. There is no undo,
  and the command says so before doing it.
- **Every schema change runs against every customer's database**, which is a
  step in deploying rather than an afterthought.

!!! note "Why it was built this way"

    The reasoning, what was weighed against a shared schema with a tenant
    column and what that would have cost, is in `docs/architecture.md` §4 of
    the [main repository](https://github.com/Praesidiarius/plc-xivi). These
    pages describe what an installation is; the brief records why.
