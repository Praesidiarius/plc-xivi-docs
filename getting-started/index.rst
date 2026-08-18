Getting Started
===============

From an empty machine to an installation with a customer in it, in the order the
steps actually have to happen.

Xivi is served by Docker and needs nothing installed on the host but Docker
itself — no PHP, no Composer, no Node. Every command below runs in a container,
which is why they all start with ``bin/``.

.. toctree::
    :maxdepth: 1

    installation
    first-operator
    control-plane
    first-tenant

Where to go next
----------------

Once a customer exists and somebody can sign in to it, :doc:`/the-basics/index`
explains what they are looking at: records, the modules that shape them, and how
a customer changes that shape without anybody deploying anything.

If you are deciding how to run this rather than how to use it,
:doc:`/architecture/index` is the shorter road — particularly
:doc:`/architecture/two-images`, which is the decision that most affects how an
installation is deployed.
