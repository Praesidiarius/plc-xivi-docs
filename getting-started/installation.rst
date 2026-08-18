Installation
============

Xivi needs **Docker and Docker Compose** on the host and nothing else. There is
no PHP to install, no Composer step and no Node: the image carries all of it, and
every command in these pages runs inside a container.

Getting the code
----------------

.. code-block:: terminal

    $ git clone git@github.com:Praesidiarius/plc-xivi.git
    $ cd plc-xivi

Starting it
-----------

.. code-block:: terminal

    $ bin/compose up -d --wait

That builds the image, starts PostgreSQL, installs dependencies and applies the
control-plane migrations. When it returns, the application is on
``https://localhost`` with a self-signed certificate — expect a browser warning,
or use ``curl -k``.

.. caution::

    Use ``bin/compose``, never ``docker compose`` directly.

    It is a thin wrapper that forwards every argument through, after pointing
    Compose at *this* checkout's stack. A checkout is the unit of isolation here:
    the compose project, the published ports, the bind mount and the image name
    are all derived from the directory, so two checkouts run side by side without
    colliding. A bare ``docker compose`` knows none of that. It also runs the
    container as you rather than as root, so the files it creates belong to you.

Asking which stack you are on
-----------------------------

``bin/compose`` with no arguments answers that:

.. code-block:: terminal

    $ bin/compose
    checkout   plc-xivi (the main one)
    project    plc-xivi
    image      xivi-php-dev
    app        https://localhost:443

Hostnames in development
------------------------

Every customer gets a hostname, and in development those are ``*.localhost``,
which resolves to ``127.0.0.1`` on most systems. If yours disagrees, add the name
to ``/etc/hosts``.

The development server matches a single wildcard label, so development hostnames
are one level deep: ``acme.localhost`` works, ``www.acme.localhost`` does not.

Checking that it is running
---------------------------

Before there is a customer to sign in to, the quickest confirmation is the
tenancy diagnostic, which reports the resolved customer and asks PostgreSQL which
database the connection actually reached:

.. code-block:: terminal

    $ curl -k https://localhost/_tenancy/whoami

With no customer hostname it will tell you no tenant resolved, which is the
correct answer and means the stack is up. It is served only when debug is on.

Next
----

An installation with nobody able to administer it is not much use, so the next
step is :doc:`first-operator`.
