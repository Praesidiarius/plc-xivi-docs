The First Customer
==================

A **tenant** is one customer: their own database, their own hostname, their own
users, and their own idea of what a contact or an invoice looks like. Creating
one is a single command.

Provisioning
------------

.. code-block:: terminal

    $ bin/compose exec php bin/console tenant:provision acme acme.localhost \
        --name='Acme AG' --admin-email=you@example.com

That creates the database and its PostgreSQL role, migrates it, registers the
hostname and creates an administrator. **The password is printed once** — there
is no way to read it back afterwards.

Then sign in at ``https://acme.localhost``.

Checking the plumbing
---------------------

Without opening a browser:

.. code-block:: terminal

    $ curl -k -H 'Host: acme.localhost' https://localhost/_tenancy/whoami
    {"tenant":"acme","status":"active","database":"tenant_acme"}

That asks PostgreSQL which database the connection actually reached, rather than
which one the application meant to reach, so it answers the question that is
usually being asked. It is served only when debug is on.

A customer to experiment on
---------------------------

For development there is a command that throws a customer away and builds a fresh
one, with modules installed and records in them:

.. code-block:: terminal

    $ bin/compose exec php bin/console tenant:reset bulk \
        --modules=contact,article,order,invoice --records=300 --seed=24

Worth knowing:

* **Module order is worked out for you.** An invoice needs an order and an order
  needs a contact; list them in any order. A module missing a requirement, or one
  this build does not carry, is refused *before* the existing customer is
  destroyed.
* ``--records`` is **one number applied to each module** — 300 contacts *and* 300
  articles *and* 300 orders.
* ``--seed`` makes the records identical every run, which is what makes "it broke
  on record 4,312" something somebody else can reproduce.
* **It destroys before it builds, and no flag changes that.** If something fails
  afterwards, the command prints what is gone, what is standing, and the line to
  run again.
* **Development only.** It is not in the production image.

Removing a customer
-------------------

.. code-block:: terminal

    $ bin/compose exec php bin/console tenant:deprovision bulk

It names the database, the role, the hostnames and how many records are in there,
then asks — and pressing return is *no*. An unattended run needs ``--force``.

.. danger::

    There is no undo. The database and the role are dropped and the registration
    is deleted. Take a dump first.

Next
----

There is now an installation, an operator, and a customer somebody can sign in
to. :doc:`/the-basics/index` explains what they are looking at.
