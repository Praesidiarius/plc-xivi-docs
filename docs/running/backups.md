# Backups

**A backup of Xivi is two operations.** It used to be one, and the day a record
could carry a file it stopped being one.

| What | Where it is | How it is taken |
| --- | --- | --- |
| Every customer's data, and the registry | PostgreSQL, one database per customer plus the control plane | `pg_dump` per database, or a dump of the cluster |
| Every file on a record | The directory `XIVI_ATTACHMENTS_DIR` points at | A copy of that directory, or a snapshot of the volume |

Take both, and take them from the same installation. A restore that brings back
only the database produces records that name files nobody has: the record still
lists "Contract 2026.pdf", the page still shows it, and the download answers
404. Nothing is corrupted and nothing says so on its own, which is why the
[check](#checking-that-the-two-still-agree) below exists.

## Where the files are

`XIVI_ATTACHMENTS_DIR` names one directory for the whole installation, with a
subdirectory per customer inside it. The name of that subdirectory is derived
from that customer's own database name, so a customer's files and a customer's
database can never be about different customers.

**It has to be a volume.** A path inside the container's own filesystem is
emptied by the next release, because replacing the image replaces that
filesystem. The `compose.yaml` this project ships mounts a named volume at
`/app/var/attachments` and points the variable at it.

**Both images mount it at the same path.** The customer-facing image is what
serves a download, so a deployment that mounted the volume only into the
internal image would have an administration surface that could read customers'
files and customers who could not read their own. See
[Two images](../architecture/two-images.md).

## Restoring

Restore the database first and the files second, or the other way round: the
order does not matter, because nothing in either half writes to the other. What
matters is that they come from the same moment. Files are only ever added and
removed, never rewritten in place, so a file directory that is *newer* than the
database restores cleanly and leaves a few files no record claims. A file
directory that is *older* leaves records pointing at files that are not there,
which is the direction to avoid.

## Checking that the two still agree

```console
$ bin/console tenant:files:check
$ bin/console tenant:files:check --slug=acme
```

It reports two things per customer:

- **Records pointing at a file that is not there.** Always worth looking at. A
  restore that took only the database produces these in bulk.
- **Files no record claims.** Ordinary in small numbers: a file is written the
  moment somebody chooses it, and a save that is then refused for some other
  reason leaves one behind. Worth investigating when it is thousands.

**It reports and never deletes.** The repair for an orphan is `rm`, and a
command that removed a customer's file because a database was briefly
unreadable would be a worse problem than the one it was fixing.

It exits **0** when everything agrees, **1** when it could not run at all, and
**3** when at least one customer has drift, which is the same contract
[`tenant:migrate`](deploying.md) publishes. It reads and writes nothing, so it
is safe to run against a live installation and safe to put in cron if you want
the answer regularly.

**It is deliberately not part of `bin/deploy`.** The checks a deploy runs are
cheap properties of the deployment whose failure is an outage. This one is a
full walk of every customer's directory, and its expected steady state is a
handful of orphans, so a release blocked at three in the morning by somebody's
abandoned upload would teach everybody to ignore it.

## Removing a customer takes both

`tenant:deprovision` drops the database, drops the role, deletes that customer's
file directory and then removes the registry row. Its confirmation names the
file count and their total size beside the record count, so what is about to be
destroyed is on the screen before you say yes.

If it stops part-way it says which of the four are gone, and running it again
finishes the job.
