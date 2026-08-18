# Xivi Documentation

The documentation for [Xivi](https://github.com/Praesidiarius/plc-xivi), a
multi-tenant ERP. Written for the two people who meet it from outside the code:
whoever **deploys and runs an installation**, and whoever **uses one**.

## Reading it

```bash
bin/docs serve      # http://localhost:8081, rebuilding as you edit
bin/docs build      # build once into site/ — this is what CI runs
```

Both run in a container. Nothing is installed on the host, which is the same rule
the main repository works under.

## Writing it

Pages are Markdown in `docs/`, and the navigation is the `nav:` block in
`mkdocs.yml`. A page not listed there is not in the site.

**`bin/docs build` is strict**: a link to a page that does not exist fails the
build rather than shipping. That is a documentation site's most common defect and
the one a build can catch for nothing.

Three things worth knowing before adding a page:

**Say who it is for.** Every topic here has an audience — an operator deploying
an installation, or somebody using one. A page that serves neither probably
belongs in the main repository instead.

**This is not the design brief.** The reasoning behind decisions — why records
are not ORM entities, why mail is synchronous — lives with the code in
`docs/architecture.md` of the main repository, is cited by section number
throughout the issue tracker, and stays there because the people and tools
changing the code read it out of the working tree. These pages describe what an
installation *is*, not why it was built that way.

**Commands are shown as somebody would type them**, which here means through
`bin/compose`.

## Tooling

[Material for MkDocs](https://github.com/squidfunk/mkdocs-material), from the
`squidfunk/mkdocs-material` image its own maintainers publish. There is no
toolchain here to maintain beyond a tag in `compose.yaml`.

An earlier attempt used `symfony-tools/docs-builder`, the tool behind Symfony's
own documentation, and it was abandoned for a reason worth recording: **the
builder is not where symfony.com's appearance comes from.** It ships two themes —
`default`, and a snapshot of the Read the Docs Sphinx theme from August 2021
whose layout is largely commented out and whose stylesheet links resolve relative
to the page, so every file below the root renders unstyled. The design at
symfony.com/doc belongs to the symfony.com website and is not published. Adopting
that builder therefore bought the format and none of the look, and building a
theme from nothing is a cost with no upside when a maintained one exists.

## Licence

MIT — see [LICENSE](LICENSE), the same licence as Xivi itself.

The shape of this documentation takes after
[`symfony/symfony-docs`](https://github.com/symfony/symfony-docs), which is a
fine example of what a documentation repository can be. Its *text* is licensed
CC BY-SA 3.0 and none of it is used here — every page is written for Xivi, about
Xivi. Inspiration is not adaptation, so no attribution is owed beyond this
paragraph, which is offered because it is deserved rather than because it is
required.
