# The Basics

Four ideas, and everything else in Xivi follows from them.

## A record is a thing a customer keeps

A contact, an article, an order, an invoice. [Records](records.md) explains
what one is and what happens to it over its life.

## A module gives records their shape

Modules are the units a customer installs. Each arrives with a starting shape,
which fields a contact has and what states an order moves through, and from
then on that shape is the customer's. [Modules](modules.md).

## A field is the customer's to change

Adding a field, renaming one, marking one required: none of that is a
deployment. [Fields](fields.md) explains what a customer may change and the few
things they may not.

## One installation, many customers

Each customer has their own database and their own hostname, and none of them
can see another's records. That is [Tenancy](../architecture/tenancy.md).
