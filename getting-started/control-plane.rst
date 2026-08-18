The Control Plane
=================

The control plane is where an operator administers the installation: the list of
customers, what each of them uses, what the modules cost, and who else may sign
in here. It is the same application, served on a hostname of its own.

Reaching it
-----------

In development it is on ``control.localhost``:

.. code-block:: terminal

    $ open https://control.localhost/control/

Sign in with the operator created in :doc:`first-operator`.

Giving it a hostname
--------------------

In production, set ``CONTROL_PLANE_HOST`` to a name this installation answers on
and that **no customer is served on**:

.. code-block:: env

    CONTROL_PLANE_HOST=admin.example.net

That one variable is all there is to it. The value is added to the list of hosts
served *without* a customer, so the host serving the control plane is by
construction one that resolves no customer, and there is no second setting to
keep in step with the first. Point DNS and the certificate at it like any other
host; there is no separate deployment, because it is the same application.

Three consequences worth knowing before choosing a name:

**Nothing else is served there.** A request to that host for anything but
``/control/…`` answers 404 — and ``/control/…`` answers 404 on every other host.

**No customer can be routed to it.** Provisioning refuses that hostname, along
with every other host served without a tenant.

**The hostname is not a security boundary.** Anybody who can set the ``Host``
header reaches the sign-in page from any address that terminates the connection.
That is not a defect and no setting changes it: the control-plane host is by
construction one of the names this installation answers to. What keeps people out
is the sign-in itself and the operator role behind it, both of which treat a
forged ``Host`` exactly as they treat a real one.

Prefer a name that is not guessable from the customer-facing domain — not because
guessing it grants anything, but because there is no reason to advertise it.

What you can do there
---------------------

Once signed in:

* **Customers** — every tenant, its status, its hostnames, and what it uses.
  Usage figures are collected by a scheduled command rather than live; until it
  has run, the columns say so rather than showing a zero.
* **Modules** — what this build carries, how far along each module is, and
  :doc:`what it costs </the-basics/modules>`.
* **Purchases** — modules a customer has asked to buy, which is what a priced
  module produces instead of installing itself.
* **Operators** — from the command line only, as
  :doc:`first-operator` describes.

Next
----

An installation with no customers has nothing to administer, so:
:doc:`first-tenant`.
