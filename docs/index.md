# Xivi Documentation

Xivi is a multi-tenant ERP: one installation, many customers, each with their own
database and their own hostname. What a customer's records look like is theirs to
decide — the modules ship a starting shape and the customer changes it — so most
of what follows is about that idea and its consequences.

These pages are for the two people who meet Xivi from outside the code: whoever
**deploys and runs an installation**, and whoever **uses one**. The reasoning
behind how it is built lives with the code instead, in `docs/architecture.md` of
the [main repository](https://github.com/Praesidiarius/plc-xivi).

<div class="grid cards" markdown>

-   :material-rocket-launch: **Getting Started**

    ---

    From nothing to an installation with a customer in it, in the order the steps
    actually have to happen.

    [:octicons-arrow-right-24: Start here](getting-started/index.md)

-   :material-cube-outline: **The Basics**

    ---

    The handful of ideas everything else is built on: records, modules, fields,
    and what it means for a customer to change their own.

    [:octicons-arrow-right-24: The ideas](the-basics/index.md)

-   :material-sitemap: **Architecture**

    ---

    How an installation is put together — tenancy, the control plane, the two
    images — for somebody deciding how to run it.

    [:octicons-arrow-right-24: How it fits together](architecture/index.md)

-   :material-github: **The code**

    ---

    Xivi itself, its design brief, and the issue tracker where the reasoning for
    each decision is recorded.

    [:octicons-arrow-right-24: On GitHub](https://github.com/Praesidiarius/plc-xivi)

</div>

## What Xivi is

**One installation serves many customers.** Each has their own PostgreSQL
database and their own hostname; none can see another's records, and that
separation is the database's rather than a filter somebody has to remember to
apply.

**A customer's records are theirs to shape.** Modules — contacts, articles,
orders, invoices — arrive with a starting set of fields, and from then on the
customer adds, renames and removes them without anybody deploying anything.

**An operator runs the installation.** They see every customer, provision new
ones and decide what the modules cost. They are not a user of any customer and
cannot read anybody's records.

## Licence

This documentation is MIT, like Xivi itself — see
[LICENSE](https://github.com/Praesidiarius/plc-xivi-docs/blob/main/LICENSE).

Its shape takes after [symfony/symfony-docs](https://github.com/symfony/symfony-docs),
which is a fine example of what a documentation repository can be. None of its
text is used here, and none needs to be.
