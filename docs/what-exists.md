# What exists today

Xivi is early, but no longer a skeleton. Multi-tenancy, sign-in and the metadata
engine are built and tested. A customer can list, filter, create, edit, delete,
export and import records, change their own fields, and read what happened to a
record — every page of it built from definitions in their own database. An
administrator can manage the people who sign in and decide, per module and per
action, what each of them may do. What is missing is templates deciding which
modules a customer is given, and a second module beyond the ones below.

This page is the inventory. [The Basics](the-basics/index.md) explains the ideas
behind it, and [Architecture](architecture/index.md) how an installation is put
together.

## Tenancy and access

**Tenant resolution.** One deployed codebase serves every customer. The tenant is
resolved per request from the `Host` header against a control-plane database, and
each tenant's data lives in **its own PostgreSQL database** — isolation is physical,
not a `WHERE tenant_id = ?` you can forget (§4).

**A control plane, not config files.** Domains, database DSN, status, plan and
enabled modules are rows, so onboarding a customer is a command rather than a
deploy. There are deliberately no per-customer `.env` files.

**Per-tenant database credentials.** Every tenant gets its own PostgreSQL role, and
its database revokes all rights from `PUBLIC`. A bug that hands Doctrine the wrong
DSN fails to connect instead of quietly reading another customer's data. Passwords
are generated with `random_bytes`, stored encrypted (libsodium), and never printed.

**Sign-in, per tenant.** Users live in each customer's own database, so the same
email address is a different person at a different customer. Sessions are stamped
with the tenant that created them and refused anywhere else, because Symfony
restores a session by user *identifier* and those identifiers collide across
tenants (§8.2).

## The engine

**A metadata-driven engine.** A module declares its fields; installing it for a
customer writes those definitions into that customer's database and creates its
table. From then on the definitions drive validation and storage, and the customer
can add fields of their own. `packages/contact` is the whole first module — a
declaration and nothing else, no entity, no repository, no controller and no form
class (§5). One generic controller and one generic form serve every module,
building each page from that customer's definitions.

**Presets, so installing is a choice.** A module ships named subsets of its own
fields — Contact has `basic` and `extended` — picked at install time with
`--preset` (§6.1). A preset names fields the declaration already contains rather
than carrying its own, so a field key means one type across the whole module no
matter which preset a customer got.

**Child collections.** A contact's addresses are the same kind of thing as the
module itself, not a special case: their own table with a real foreign key, their
own definitions, edited inline with the parent and soft-deleted along with it
(§5.1). Contact declares them and still contains no code.

**One module, more than one kind of record.** A contact is a person *or* a
company, each variant with its own fields, in a single module — because "pick a
contact" has to stay a plain foreign key, and two modules would make every link to
one polymorphic (§5.5). A person links to their company by id; the company's
people are a query, not a second copy of the answer.

**History per module, not one table for everything.** Every write goes through a
single writer, which records one entry per action in that module's own history
table (§5.2). Per module because a shared polymorphic table cannot carry a foreign
key, which is exactly what made the last one rot at 60 million rows; per action
because a timeline nobody can read is a feature nobody uses. A record's timeline
is on its page.

**Filtering, sorting and paging.** Compiled from the customer's definitions rather
than written per module, so a field they added this morning is filterable this
afternoon (§5.3). A filter bar, sortable columns, and a filtered list that is a
URL you can send to a colleague. Filtering by a collection compiles to an `EXISTS`
semi-join, and each field type owns which operators apply to it.

**An editor for the metadata.** Admins add, relabel and remove fields on any
shape, collections included (§5.4). Changes that would strand data are refused
with a count rather than performed — turning on required or unique when existing
records would fail it — and removing a field leaves its values untouched, so
re-adding the key brings them back.

**Export, and import back.** A module's records as a spreadsheet, one sheet per
shape, carrying whatever the list was filtered to — and the same file back in
(§5.6). Every row is validated by the same rules the form uses and the file is
applied in one transaction or refused whole, because half an import is a state
nobody can reason about. **A check is the import, rolled back** rather than a
second code path, so it catches what only a write can: two rows of one file
claiming the same unique email collide on the second, because by then the first
one is really there.

## What a customer's people can do

**Users, managed from the application.** An administrator adds colleagues,
makes them administrators, deactivates the ones who leave and resets a lost
password; everybody can change their own password on their account page (§8.4.1).
A generated password has to be replaced before the account is usable — until it
is, every page leads back to the account page, because a password an administrator
read off a screen and passed on is one two people know.
Nobody is ever deleted — records carry the id of whoever owns them and history the
id of whoever changed them, so deactivating keeps all of it attributable and is
reversible. Every refusal here is about lock-out: you cannot deactivate yourself,
demote yourself, or leave the installation with no administrator.

**Colleagues are invited, not handed a password** (§8.8). Adding a user sends a
link that works once and for twenty-four hours; no password is generated at all.

**Permissions, on two axes** (§8.4, §8.4.3). What can be done *to a module* is a
closed list — view, list, add, edit, delete, export, import, templates, document,
email templates, send email, transition — so that half of the set is the list
crossed with the modules a customer has installed, with nothing to seed and
nothing to keep in step. The store is the second axis and deliberately not part of
that crossing: browsing is about no module, and installing is about one you have
not got. Grants are given to a group and inherited by
its members, or to one person on top of that; they only ever add, so resolving
somebody is a maximum rather than a precedence table nobody can hold in their
head. Mutating and reading alike can be narrowed to **only the records that
person owns**, which is a WHERE clause rather than a check after loading — a page
filtered after fetching shows four rows under a total that says twenty-five.
Administrators bypass all of it, because a permission that can be taken from the
last administrator is a locked-out installation. Everybody else starts with
nothing.

**English and German** (XIV-8). Each person picks their language on their account
page; the login page follows the browser, since there is nobody to ask yet. A
module's own labels are seeded from its catalogue at install time —
`tenant:module:install acme contact --locale=de` gives that customer *Kontakte*
and *Vorname* — and from then on they are the customer's data, renameable and no
longer following the catalogue: resolving a label on every render would overrule
that rename every page load. Which means labels are one language per *tenant*,
while everything else follows each reader. A key with no German translation fails
the build rather than falling back quietly.

## Business records

**Money that adds up** (§5.9). Lines carry a quantity and a price; the order's
net, VAT and gross are derived from them on save and *stored*, because a price
list that changes must not restate an invoice already sent. The arithmetic is the
server's, and the same derivers run behind the figures that update while you
type.

**Documents from a Word template** (§5.7). A tenant uploads a .docx with
placeholders, and a record fills it in — as Word or as a PDF. A row of a table
containing a collection's marker repeats once per line, so an invoice's lines
come out as an invoice's lines.

**Numbers, lifecycles and links** (§5.10, §5.8). Documents are numbered from
the customer's own counters; a record moves through declared states rather than
having one written into it; and a reference is a link to the record it names,
offered only where the reader may open it.

**Mail, as the customer** (§8.7, §5.13–§5.15). Email templates are written in the
application in Markdown, sent from a record with the recipient taken from the
module's own declaration, and a generated document can go out attached. Outside
production nothing can reach a real mail server at all.

**A store, so a tenant installs modules themselves** (§6.3). What this build
offers, what each module is, and a wizard that picks a preset — with no shell and
no operator in the loop.

## How it is served

**Classic PHP execution, on purpose.** FrankenPHP runs without worker mode, so no
PHP state survives a request boundary and cross-tenant leakage (§7.4) is
structurally impossible for web requests. It costs a few milliseconds per request
and is worth it — the argument is in the comment in `frankenphp/Caddyfile` of the
[main repository](https://github.com/Praesidiarius/plc-xivi).

**Server-rendered, no build step.** Twig, Bootstrap's CSS and Bootstrap Icons,
self-hosted through AssetMapper — no Node, no bundler, and **no CDN calls from a
customer's browser**. The forms work without JavaScript.

!!! note "The § references"

    They cite `docs/architecture.md` in the
    [main repository](https://github.com/Praesidiarius/plc-xivi), which is where
    the reasoning behind each of these lives. It is quoted by section number
    throughout the issue tracker and stays with the code rather than moving here.
