# Architecture

How an installation is put together, for somebody deciding how to run it.

- **[Tenancy](tenancy.md)** — one installation, many customers, and what keeps
  them apart.
- **[The control plane](control-plane.md)** — what an operator can reach, and
  what they cannot.
- **[Two images](two-images.md)** — why a customer's instance is built without
  the administration surface in it.

!!! note "This is not the design brief"

    The reasoning behind each decision — why records are not ORM entities, why
    mail is synchronous, why a counter is guarded inside one statement — lives
    with the code, in `docs/architecture.md` of the
    [main repository](https://github.com/Praesidiarius/plc-xivi). It is cited by
    section number throughout the issue tracker and it stays where the code is,
    because the people and tools changing the code read it there.

    These pages are the other half: what an installation *is*, for somebody
    deploying or evaluating one rather than changing it.
