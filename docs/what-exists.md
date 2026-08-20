# What exists today

Xivi is early, and no longer a skeleton. The engine is built and tested:
multi-tenancy, sign-in, the metadata layer and per-action permissions. Six
modules run on it, from contacts through articles, orders, invoices and
vouchers to a knowledge base, and every page of them is built from definitions
in the customer's own database. What is still assembled by hand is the customer
themselves: templates deciding which modules and fields a signup receives are
the missing half of provisioning.

This page is the inventory. [The Basics](the-basics/index.md) explains the
ideas behind it, and [Architecture](architecture/index.md) how an installation
is put together.

## Tenancy and access

**Tenant resolution.** One deployed codebase serves every customer. The tenant
resolves per request from the `Host` header against a control-plane database,
and each tenant's data lives in **its own PostgreSQL database**. Isolation is
physical, not a `WHERE tenant_id = ?` somebody can forget (§4).

**A control plane, not config files.** Domains, database DSN, status, plan and
enabled modules are rows, so onboarding a customer is a command rather than a
deploy. There are deliberately no per-customer `.env` files.

**Per-tenant database credentials.** Every tenant gets its own PostgreSQL role,
and its database revokes all rights from `PUBLIC`. A bug that hands Doctrine
the wrong DSN fails to connect instead of quietly reading another customer's
data. Passwords come from `random_bytes`, are stored encrypted, and are never
printed.

**Sign-in, per tenant.** Users live in each customer's own database, so the
same email address is a different person at a different customer. Sessions are
stamped with the tenant that created them and refused anywhere else, because
Symfony restores a session by user *identifier* and those collide across
tenants (§8.2).

## The engine

**A metadata-driven engine.** A module declares its fields; installing it
writes those definitions into that customer's database and creates its table.
From then on the definitions drive validation and storage, and the customer
adds fields of their own. Most modules are a declaration and little else. The
invoice module is a declaration and a translation file, with no controller, no
entity and no form class (§5). One generic controller and one generic form
serve every module.

**Presets, so installing is a choice.** A module ships named subsets of its own
fields, picked at install time (§6.1). A preset names fields the declaration
already contains, so a field key means one type across the whole module
whichever preset a customer got.

**Child collections.** A contact's addresses and an order's lines are the same
kind of thing as the module itself: their own table with a real foreign key,
their own definitions, edited inline with the parent and soft-deleted with it
(§5.1). Rows come in kinds where the module says so (an order line is an
article, a comment or a subtotal), and they keep the order the customer put
them in.

**One module, more than one kind of record.** A contact is a person *or* a
company, each variant with its own fields, in a single module, because "pick a
contact" has to stay a plain foreign key (§5.5).

**History per module.** Every write goes through a single writer, which records
one entry per action in that module's own history table (§5.2). A record's
timeline is on its page, and because the history stores values, an article's
price over time can be drawn as a chart.

**Filtering, sorting and paging.** Compiled from the customer's definitions, so
a field they added this morning is filterable this afternoon (§5.3). A
filtered list is a URL somebody can send to a colleague.

**An editor for the metadata.** Administrators add, relabel, reorder and remove
fields on any shape, collections included; group a long form into sections; put
options on a choice field; point a reference at a module; and set up document
numbering with a live preview (§5.4). Changes that would strand data are
refused with a count and the offending values rather than performed, and
removing a field leaves its values untouched, so re-adding the key brings them
back.

**Field types beyond text and numbers.** Money stored exactly, dates, choices,
references, formatted text (Markdown, escaped and sanitized before it ever
renders), phone numbers normalised to one international form, and periods, a
from-and-until as one value, with the database refusing two overlapping
periods on one resource (§5.21, §5.23, §5.27).

**Shared lists.** A list the customer keeps once (regions, topics) that choice
fields across modules point at, with colour, one level of hierarchy, and a
merge that turns `Zurich` and `Zürich` back into one entry across every record
holding them (§5.26).

**Export, and import back.** A module's records as a spreadsheet, one sheet per
shape, carrying whatever the list was filtered to, and the same file back in
(§5.6). Every row is validated by the rules the form uses, and the file is
applied in one transaction or refused whole. **A check is the import, rolled
back**, so it catches what only a write can: two rows of one file claiming the
same unique email collide on the second, because by then the first is really
there.

## What a customer's people can do

**Users, managed from the application.** An administrator adds colleagues,
makes them administrators, deactivates the ones who leave and resets a lost
password (§8.4.1). Nobody is ever deleted, so everything stays attributable,
and every refusal is about lock-out: you cannot deactivate yourself, demote
yourself, or leave the installation with no administrator.

**Colleagues are invited, not handed a password** (§8.8). Adding a user sends a
link that works once and for twenty-four hours; no password is generated at
all.

**Permissions, on two axes** (§8.4, §8.4.3). What can be done to a module is a
closed list (view, list, add, edit, delete, export, import, the template and
mail verbs, transitions, follow-ups), crossed with the modules a customer has
installed, so there is nothing to seed and nothing to keep in step. The store
is the second axis: browsing, installing, and asking to buy, the last split
out because deciding what the installation consists of and committing the
company's money are different authorities. Grants only ever add, and reading
or writing can be narrowed to **only the records that person owns**, which is
a WHERE clause rather than a check after loading. Administrators bypass all of
it; everybody else starts with nothing.

**Language, region and timezone, per person** (§8.4.2, §8.4.4). English and
German words; figures, dates and week starts follow the region; moments are
read in the person's own timezone, derived from the country where that is
unambiguous. A module's labels are seeded in the customer's language at
install and are their data from then on.

**A dashboard of widgets** (§8.3.1). Due follow-ups, unpaid invoices, notices
from the operator. Each person arranges their own page, and a module can ship
a widget of its own.

**Follow-ups** (§5.18). A dated to-do about one record, with a note thread, an
assignee and a reversible done stamp; overdue ones stay on the dashboard until
somebody closes them.

## Business records

**Money that adds up** (§5.9). Lines carry a quantity and a price; the
document's net, VAT and gross derive from them on save and are *stored*,
because a price list that changes must not restate an invoice already sent.
The same arithmetic runs behind the figures that update while you type. Prices
can be quoted net or including VAT, per document, and the gross the customer
typed is the gross that prints (§5.9). Articles carry a unit, and lines print
it (§5.20).

**One record from another** (§5.12). An invoice is seeded from an order: the
form comes back filled in, copies rather than live references, and a partially
invoiced order knows what is outstanding per line.

**Numbers, lifecycles and links** (§5.10, §5.8, §7.6). Documents are numbered
from the customer's own counters, gap rules decided. A record moves through
declared states, a move can be refused because of the record ("an order needs
at least one line"), and some states lock a record. References link records
across modules, offered only where the reader may open the target.

**Payment terms and due dates** (§5.16). Terms live on the tenant and the
contact; an invoice materialises its due date when it is sent, and overdue is
computed rather than stored.

**Vouchers** (§5.19, §5.24, §5.25). A code with a worth, a validity window and
a guarded redemption counter two simultaneous checkouts cannot overrun. A
voucher applies to the whole order as its own discount line, VAT handled per
rate, or to one chosen line, which is reduced in a column the recipient can
check.

**Documents from a Word template** (§5.7). A tenant uploads a .docx with
placeholders and a record fills it, as Word or as PDF. A table row containing
a collection's marker repeats per line, the customer's logo has a marker of
its own, and a token the engine cannot fill is reported next to the template
rather than discovered in a finished letter.

**Mail, as the customer** (§8.7, §5.13 to §5.15). Email templates are written
in the application in Markdown, sent from a record with the recipient taken
from the module's own declaration, and a generated document goes out attached,
with a size ceiling chosen against receiving servers. Outside production
nothing can reach a real mail server at all.

**A store** (§6.3). A tenant installs modules themselves, with no shell and no
operator in the loop. Modules can carry a price the operator sets; a priced
module is not installed on request but produces a purchase request an operator
answers (§6.5, §8.15).

**A knowledge base** (§5.22). A very simple wiki: entries with a formatted
body and a topic, written by whoever is granted writing, searched with the
same filters as everything else.

## Between a customer and the operator

**Notices** (§8.16). An operator announces to every customer or to named ones,
and the notice appears on dashboards, dismissible per person.

**Support tickets** (§8.17). Anybody signed in can ask a question; the
operator's answer appears on the same page the customer asked on, with no mail
in either direction.

**Usage figures** (§8.11). The tenant list shows what each customer uses,
collected on a schedule, and says how old every figure is rather than
presenting a stale one as current.

**Self-service signup** (§8.12 to §8.14). Off by default. Switched on, a
public form or your own site records a signup, a confirmation link proves the
mailbox, and a scheduled command provisions the customer and invites the first
administrator. The anonymous surface never provisions anything.

## How it is served

**Classic PHP execution, on purpose.** FrankenPHP runs without worker mode, so
no PHP state survives a request boundary and cross-tenant leakage (§7.4) is
structurally impossible for web requests. It costs a few milliseconds per
request.

**Server-rendered, no build step.** Twig and Bootstrap, self-hosted through
AssetMapper: no Node, no bundler, and **no CDN calls from a customer's
browser**. The interactive pieces are Symfony UX Live Components, so every
re-render is still the server's; the pages assume JavaScript is on.

**An installation that states its own needs.** It prints the cron entries it
needs (`deploy:crontab`), refuses to start in production on a published
placeholder secret, checks its hostnames and database grants during a deploy,
and its scheduled jobs can ping an external monitor so that silence raises an
alarm (§4.2 to §4.5).

!!! note "The § references"

    They cite `docs/architecture.md` in the
    [main repository](https://github.com/Praesidiarius/plc-xivi), which is
    where the reasoning behind each of these lives. It is quoted by section
    number throughout the issue tracker and stays with the code rather than
    moving here.
