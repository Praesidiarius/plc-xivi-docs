# Fields

A field is one thing a record holds: a name, a price, a date, a link to another
record. Which fields a module's records have is **the customer's decision**,
made on a screen, at any time.

## The types

| Type | Holds |
| --- | --- |
| Text | A line of text. |
| Textarea | Several lines of plain text. |
| Markdown | Formatted text: headings, lists, emphasis, links. |
| Email | An address, checked for shape. |
| Phone | A phone number, stored in one international form however it was typed. |
| Integer | A whole number. |
| Decimal | A number with places: quantities, percentages. |
| Currency | Money, stored exactly rather than as a floating-point number. |
| Date | A date. |
| Date range | A period, from one date until another, or open-ended. |
| Choice | One of a list: the field's own, or a shared list the customer keeps. |
| Reference | Another record, in this module or another. |
| File | A file on the record: a signed contract, a delivery note, a datasheet. |

A module can add types of its own. The voucher module ships one for voucher
codes, which knows that `give-10` and `GIVE-10` are the same code.

A file field holds **one** file, of up to 10 MB, of any kind. It is uploaded on
the record form and downloaded from the record page, and the download goes
through Xivi rather than being a link anybody holding it can open: whoever may
not open the record cannot open the file either. Replacing a file removes the
one it replaced, so there is no history of earlier versions. Where the bytes
live and what that means for a backup is
[Backups](../running/backups.md).

A period field can name the field it is exclusive within, and the database then
refuses two overlapping periods for one room, one machine or one person. Two
people booking the same room for the same night cannot both succeed.

## What a customer can change

**Add a field.** It appears on the form and in the record straight away;
existing records simply have nothing in it.

**Rename one.** The label is what people read; the key underneath stays put, so
nothing that referred to the field stops working.

**Reorder them**, set how wide each one draws, and **group a long form into
sections** with headings the customer names.

**Put options on a choice field**, or point it at a shared list. Options on a
field a module shipped can be added and renamed, never removed, because a
module's own states and kinds are options too.

**Mark one required**, or **unique**.

**Remove one.** Which brings us to the part worth knowing.

## Removing a field keeps the values

Removing a field takes it off the form and out of the lists. **It does not
delete what was stored in it.** Add a field back with the same key and the
values are there again.

That is deliberate. A removal is one click, and a click that destroys a column
of a business's data with no way back is not a click anybody should be one
mis-aim away from. The editor says so at the time, rather than leaving it to be
discovered.

## Required and unique are checked against what exists

Marking an existing field **required** when some records are empty in it would
produce records nobody can save, so the editor counts them first and refuses,
naming the number.

Marking one **unique** works the same way and goes further: it names the values
that are already duplicated, because a count leaves somebody scrolling six
hundred contacts looking for four they cannot describe. The values are the
search terms.

Once unique is on, the database enforces it rather than a check the application
runs, so two people saving the same value at the same moment cannot both
succeed. Empty is not a duplicate of empty: unique means no two records share a
value, not that every record must have one.

## Numbered fields

A text field can be **numbered**: the engine fills it from a counter, using a
pattern the customer writes.

```text
RE-{year}-{number:4}     →  RE-2026-0001
ORD-{number:5}           →  ORD-00001
```

The page previews the next number as the pattern is typed, and says which
counter it comes from. A pattern containing `{year}` restarts each January; one
without it never does.

Two things the engine guarantees: **a number is assigned once and never
changes**, and **no two records get the same one**. Turning numbering on for a
field that already holds values takes account of what is in there, so the
counter cannot hand out a number a record already carries.

## What a customer cannot change

**Fields the engine owns.** Totals, VAT, a due date, a document number: the
engine computes these while a record is saved, and a value somebody could type
is a value that can disagree with the lines it was computed from.

**Fields a module marks as its own.** A module that ships a lifecycle needs the
field that lifecycle runs on, so that one stays.

**A field's type.** Stored values may not survive a new type, so the editor
does not offer the change.

!!! note "Why it was built this way"

    The reasoning, including what will happen to values when a field changes
    type, is in `docs/architecture.md` §5.4 and §7.2 of the
    [main repository](https://github.com/Praesidiarius/plc-xivi).
