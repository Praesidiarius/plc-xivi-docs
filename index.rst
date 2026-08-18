Xivi Documentation
==================

Xivi is a multi-tenant ERP: one installation, many customers, each with their own
database and their own hostname. What a customer's records look like is theirs to
decide — the modules ship a starting shape and the customer changes it — so most
of what follows is about that idea and its consequences.

These pages are for the two people who meet Xivi from outside the code: whoever
**deploys and runs an installation**, and whoever **uses one**. The reasoning
behind how it is built lives with the code instead, in ``docs/architecture.md``
of the `main repository`_.

.. _`main repository`: https://github.com/Praesidiarius/plc-xivi

Getting Started
---------------

From nothing to an installation with a customer in it.

.. toctree::
    :maxdepth: 2

    getting-started/index

The Basics
----------

The handful of ideas everything else is built on: records, modules, fields, and
what it means for a customer to change their own.

.. toctree::
    :maxdepth: 2

    the-basics/index

Architecture
------------

How an installation is put together — tenancy, the control plane, the two images
— for somebody deciding how to run it.

.. toctree::
    :maxdepth: 2

    architecture/index

Topics
------

.. toctree::
    :maxdepth: 1

    getting-started/installation
    getting-started/first-operator
    getting-started/control-plane
    getting-started/first-tenant
    the-basics/records
    the-basics/modules
    the-basics/fields
    architecture/tenancy
    architecture/control-plane
    architecture/two-images

Licence
-------

The documentation is MIT, like Xivi itself — see `LICENSE`_. Its shape takes
after `symfony/symfony-docs`_, which is a fine example of what a documentation
repository can be; none of its text is used here, and none needs to be.

.. _`LICENSE`: https://github.com/Praesidiarius/plc-xivi-docs/blob/main/LICENSE
.. _`symfony/symfony-docs`: https://github.com/symfony/symfony-docs
