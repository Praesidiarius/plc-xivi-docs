# Commands

An installation is administered from the console as much as from the control
plane. In development every one of these is typed through `bin/compose`:

```console
bin/compose exec php bin/console tenant:list
```

On a deployment they are `bin/console <command>` inside the application
container, from the image that carries the administration surface. See
[Deploying](deploying.md).

## Customers

| Command | What it does |
| --- | --- |
| `tenant:provision <slug> <hostname...>` | Creates the row, the role, the database and its schema; `--admin-email` adds the first user |
| `tenant:deprovision <slug>` | Removes the row, the database and the role. Asks first, defaults to *no*, and needs `--force` to run unattended |
| `tenant:user:create <slug> <email>` | Adds a user to one customer; `--admin` grants ROLE_ADMIN |
| `tenant:module:install <slug> <module>` | Installs a module for one customer: its table and field definitions. `--preset` picks which fields, `--locale` which language its labels are seeded in |
| `tenant:list` | Shows the registry |
| `tenant:migrate [--slug=]` | Applies tenant migrations to every customer. Exits **0** when all are at the latest version, **1** when it could not run at all, and **3** when one failed while the others succeeded. `bin/deploy` runs it |
| `tenant:files:check [--slug=]` | Reports records naming a file that is not there, and files no record names. Exits **0**, **1** or **3** like `tenant:migrate`. It reports and never deletes. See [Backups](backups.md) |
| `tenant:schema:validate [--slug=]` | Checks each customer's schema against the mapping, entering each database the way the application does |
| `tenant:permissions:grant-all <slug>` | Grants every action on every installed module to one customer's non-admin users: the upgrade path for an installation that predates permissions, and the way back into a locked-out one |
| `tenant:usage:collect [--slug=]` | Counts each customer's users, last sign-in and records into the control plane. Put it in cron; see [Scheduled jobs](scheduled-jobs.md) |
| `tenant:purchase:collect [--slug=]` | Collects the modules each customer has asked to buy into the control plane, so an operator sees the request at all. Put it in cron |
| `tenant:support:collect [--slug=]` | Collects customers' support tickets into the control plane, where the operator answers them. Put it in cron |
| `tenant:rotate-secrets` | Re-encrypts stored passwords with the active key. See [Configuration](configuration.md) |
| `tenant:reset <slug>` | **Development only**, absent from a production image. Deprovision, provision, install `--modules`, generate `--records`, print the admin password |
| `tenant:inspect [slug] [module]` | **Development only.** Customers with their schema state and installed modules; with a slug, that customer's real field definitions |

## Modules

| Command | What it does |
| --- | --- |
| `module:list` | The catalogue: what this build carries, each module's state and price |
| `module:state <module> <state>` | Publishes a module or takes it back out of the store |
| `module:price <module> …` | Sets what this deployment charges: free, an amount, or not for sale |

## Operators

| Command | What it does |
| --- | --- |
| `control:operator:create <email>` | Creates somebody who can sign in to the control plane. Asks for the password, or takes `--password` for a scripted run; refuses an address that already has an operator |
| `control:operator:list` | Who can sign in, revoked accounts included and marked |
| `control:operator:revoke <email>` | Withdraws an operator's access, keeping the account. Refuses to revoke the last one who can still sign in |
| `control:operator:restore <email>` | Gives a revoked operator their access back, with the password they had |
| `control:operator:password <email>` | Sets a new password, signing out every session that account had |

More about what an operator is, and why there is no sign-up:
[The first operator](../getting-started/first-operator.md).

## Deploying

| Command | What it does |
| --- | --- |
| `deploy:check-secrets` | Refuses in production on a secret still set to the placeholder committed in `.env`. The container entrypoint runs it on every start; it does nothing outside `APP_ENV=prod` |
| `deploy:check-hosts` | Prints the hostnames this installation answers to, and names every customer the pattern would answer with a 400. `bin/deploy` runs it and stops on exit 3; the entrypoint runs it and only prints |
| `deploy:check-grants` | Verifies the customer-facing role can read what this release reads, when `XIVI_PUBLIC_ROLE` names one. `bin/deploy` runs it and stops on exit 3. It checks and does not repair |
| `deploy:check-control-plane [--address=]` | Prints which addresses `CONTROL_PLANE_ALLOWED_IPS` admits, and whether one you name would get in. Exit 3 on a bad entry or a refused address. **Run it before you depend on that variable** |
| `deploy:crontab [--directory=]` | Prints the cron entries this build needs, what goes stale without each one, and whether anything is watching it. The output is a crontab, so redirect it rather than retyping. Exit 3 when some jobs are watched and others are not |
| `deploy:registry-grants <role>` | Prints the SQL that gives the customer-facing image its read-only control-plane role. See [Deploying](deploying.md) |
| `signup:provision [--email=]` | Turns confirmed self-service signups into customers, one at a time, and invites each first user. Put it in cron |
| `doctrine:migrations:migrate --em=control` | Control-plane schema only. `bin/deploy` is normally what runs this |
