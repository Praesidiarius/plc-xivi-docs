# Xivi Documentation

The documentation for [Xivi](https://github.com/Praesidiarius/plc-xivi), a
multi-tenant ERP. Written for the two people who meet it from outside the code:
whoever **deploys and runs an installation**, and whoever **uses one**.

## Reading it

```bash
bin/docs serve      # build, then read it on http://localhost:8080
bin/docs build      # build once into _build/output
```

Both run in a container. There is no PHP on the host and no step that installs
one — the same rule the main repository works under. The first run downloads the
builder into `_build/vendor/`; later runs start immediately.

## Writing it

Pages are [reStructuredText](https://docutils.sourceforge.io/rst.html) and the
structure is the table of contents in `index.rst`. A new page needs to be added
to a `toctree` somewhere or nothing links to it.

Three things worth knowing before adding a page:

**Say who it is for.** Every topic here has an audience — an operator deploying
an installation, or somebody using one. A page that serves neither probably
belongs in the main repository instead.

**This is not the design brief.** The reasoning behind decisions —
why records are not ORM entities, why mail is synchronous — lives with the code
in `docs/architecture.md` of the main repository, is cited by section number
throughout the issue tracker, and stays there because the people and tools
changing the code read it out of the working tree. These pages describe what an
installation *is*, not why it was built that way.

**Commands are shown as `bin/compose exec php bin/console …`**, because that is
what somebody actually types.

## Tooling

Built with [`symfony-tools/docs-builder`](https://github.com/symfony-tools/docs-builder)
(MIT), installed from Packagist. `_build/build.php` and everything else here is
ours; nothing is copied from `symfony/symfony-docs`.

That builder is, in its own words, an internal tool with no support and no
backward-compatibility guarantee. It is used here anyway, with eyes open, because
it produces exactly the shape of documentation this project wants and the cost of
replacing it later is a build script and a theme.

## Licence

MIT — see [LICENSE](LICENSE), the same licence as Xivi itself.

The shape of this documentation takes after
[`symfony/symfony-docs`](https://github.com/symfony/symfony-docs), which is a
fine example of what a documentation repository can be. Its *text* is licensed
CC BY-SA 3.0 and none of it is used here — every page is written for Xivi, about
Xivi. Inspiration is not adaptation, so no attribution is owed beyond this
paragraph, which is offered because it is deserved rather than because it is
required.
