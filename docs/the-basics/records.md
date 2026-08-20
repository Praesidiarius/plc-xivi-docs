# Records

A **record** is a thing a customer keeps: a contact, an article, an order, an
invoice. Everything a customer sees in Xivi is a record, a list of records, or
a document made from one.

## A record has a shape, and the shape is the customer's

A record belongs to a module, and the module says which fields it has. That
list is not fixed in the code. It is stored in the customer's own database, and
the customer changes it: adding a field to a contact is something somebody does
on a screen, not something anybody deploys.

The consequence worth understanding: **two customers of the same installation
can have genuinely different contacts.** One has a VAT number and a delivery
note; the other has neither and has added three fields of their own. Both are
running the same code.

## What is stored

Every record carries a few things the engine owns, whatever module it belongs
to:

- **when it was created** and **when it last changed**;
- **who owns it**, where the module tracks that, which is what lets permissions
  say "only my records";
- **whether it is deleted**, which is a date rather than a removal.

Everything else, the fields the customer decided on, is stored together as one
document per record.

## Deleting keeps the record

Deleting a record sets a date on it rather than removing the row. It stops
appearing in lists, in searches and in documents, and it stops being reachable.
It is still there, which is what makes a deletion something an installation can
recover from rather than a phone call about a backup.

## Every change is recorded

A record keeps its own history: what changed, when, and who did it. Not a log
file somewhere on a server, but a list on the record, which is what makes it
useful when the question is *"who set this back to draft?"*

The engine writes the history as part of saving, so a screen that forgot to
write one cannot exist. And because the history stores the values, it doubles
as a time series: an article's price over its life can be drawn as a chart.

## Some values are the engine's

A few fields are not typed by anybody. An invoice's totals, its VAT breakdown,
its due date, its number: these are **derived**, computed while the record is
being saved, from the lines and the settings that apply.

That is why they cannot be edited directly, and it is deliberate. A total that
somebody could type is a total that can disagree with the lines above it, and
an invoice whose figures disagree with themselves is worse than one that is
merely wrong.

## A record can move through states

Some modules give their records a **lifecycle**: an order is a draft, then
confirmed, then delivered; an invoice is a draft, then sent, then paid. The
module declares which moves are legal, and the engine refuses the rest. You
cannot deliver an order nobody confirmed.

Two consequences:

- **A move can be refused because of the record**, not only because of the
  state. Confirming an order with no lines on it is refused, and the reason
  says so.
- **Some states lock a record.** Once an invoice is sent, somebody else is
  holding a copy, and it stops being editable. Correcting one is a second
  document, not an edit.

## Records can point at each other

An order points at a contact; an invoice points at an order. Those are
**references**, a field whose value is another record, and they are how modules
combine without knowing about each other.

A reference into a module the customer has not installed simply finds nothing.
It does not error, and it does not stop the record being used.

## Work about a record

A **follow-up** is a dated to-do on one record: call them back Friday, chase
this invoice next week. It carries a note thread, an optional assignee and a
due moment, and it stays on the dashboard until somebody marks it done, which
can be taken back.

!!! note "Why it was built this way"

    Records are not ORM entities, and the reasoning, what that buys and what it
    costs, is in `docs/architecture.md` §5 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
