The First Operator
==================

An **operator** is somebody who administers the installation itself: they see
every customer, provision new ones and decide what the modules cost. They are not
a promoted user of any customer, and they have no access to any customer's
records.

There is no sign-up, no invitation and no password reset for operators. The
control plane refuses everybody until the first one is created from the command
line, which is a deliberate choice rather than an omission: the surface that can
see every customer is not one anybody should be able to enrol themselves into.

Creating one
------------

.. code-block:: terminal

    $ bin/compose exec php bin/console control:operator:create you@example.com

The password is generated and printed **once**. There is no way to read it back;
if it is lost, rotate it.

Managing them
-------------

.. code-block:: terminal

    $ bin/compose exec php bin/console control:operator:list
    $ bin/compose exec php bin/console control:operator:revoke someone@example.com
    $ bin/compose exec php bin/console control:operator:restore someone@example.com
    $ bin/compose exec php bin/console control:operator:password you@example.com

Four things about that set are worth knowing before you need them:

**Revoking deactivates, it does not delete.** The account stays in the list,
marked as revoked, and can be restored. What an operator did remains attributable
to somebody who still exists.

**The last operator who can sign in cannot be revoked.** With no sign-up, no
invitation and no password reset, there would be no way back in. Create the
successor first, then revoke the predecessor.

**Rotating a password signs out every session that account had.** That is the
point of rotating it.

**An operator is not a user of any customer.** Nothing about this account grants
access to a customer's records; those are separate accounts in a separate
database.

Next
----

The operator exists but has nowhere to sign in yet. That is
:doc:`control-plane`.
