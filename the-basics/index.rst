The Basics
==========

Four ideas, and everything else in Xivi follows from them.

.. toctree::
    :maxdepth: 1

    records
    modules
    fields

A record is a thing a customer keeps
------------------------------------

A contact, an article, an order, an invoice. :doc:`records` explains what one is
and what happens to it over its life.

A module gives records their shape
----------------------------------

Modules are the units a customer installs. Each one arrives with a starting shape
— which fields a contact has, what states an order moves through — and from then
on that shape is the customer's. :doc:`modules`.

A field is the customer's to change
-----------------------------------

Adding a field, renaming one, marking one required: none of that is a
deployment. :doc:`fields` explains what a customer may change and the few things
they may not.

One installation, many customers
--------------------------------

Each customer has their own database and their own hostname, and none of them can
see another's records. That is :doc:`/architecture/tenancy`.
