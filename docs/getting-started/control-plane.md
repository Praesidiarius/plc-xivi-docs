# The Control Plane

The control plane is where an operator administers the installation: the list
of customers, what each of them uses, what the modules cost, and who else may
sign in here. It is the same application, served on a hostname of its own.

## Reaching it

In development it is on `control.localhost`:

```console
open https://control.localhost/control/
```

Sign in with the operator created in [first operator](first-operator.md).

## Giving it a hostname

In production, set `CONTROL_PLANE_HOST` to a name this installation answers on
and that **no customer is served on**:

```ini
CONTROL_PLANE_HOST=admin.example.net
```

!!! warning "This is a deployment step"

    Skipping it leaves the control plane on `control.localhost`, which is the
    development default and is not a hostname anybody will reach in production.

That one variable is all there is to it. The value joins the list of hosts
served *without* a customer, so the host serving the control plane resolves no
customer by construction, and there is no second setting to keep in step with
the first. Point DNS and the certificate at it like any other host; there is no
separate deployment, because it is the same application.

Three consequences worth knowing before choosing a name:

**Nothing else is served there.** A request to that host for anything but
`/control/…` answers 404, and `/control/…` answers 404 on every other host.

**No customer can be routed to it.** Provisioning refuses that hostname, along
with every other host served without a tenant.

**The hostname is not a security boundary.** Anybody who can set the `Host`
header reaches the sign-in page from any address that terminates the
connection. That is not a defect, and no hostname setting changes it, because
the control-plane host is by construction one of the names this installation
answers to. What keeps people out is the sign-in itself and the operator role
behind it, both of which treat a forged `Host` exactly as they treat a real
one, plus the address allow-list below, which is the one thing that *can*
refuse a request for where it came from.

Prefer a name that is not guessable from the customer-facing domain. Not
because guessing it grants anything, but because there is no reason to
advertise it.

You do **not** have to add it to `XIVI_TRUSTED_DOMAINS`; every host served
without a customer is added for you. See [Hostnames](../running/hostnames.md).

## Restricting it to your own addresses

`CONTROL_PLANE_ALLOWED_IPS` is a list of addresses and CIDR ranges, IPv4, IPv6
or both, and a request to the control-plane host from anywhere else is answered
with an empty **403** before anything else looks at it:

```ini
CONTROL_PLANE_ALLOWED_IPS=198.51.100.7,203.0.113.0/24,2001:db8:1::/48
```

**Empty is the default and means no restriction.** Setting it changes nothing
for your customers: it applies only to requests arriving on
`CONTROL_PLANE_HOST`, and a customer's hostname is never affected.

This is a layer *in front of* the sign-in, not a replacement for it. Everything
that kept people out before still does. What it adds is that the operator
console stops being a password prompt the whole internet may attempt, which
matters precisely because, as above, the hostname alone never hid it.

!!! danger "You can lock yourself out, and nobody will tell you"

    Get `XIVI_TRUSTED_DOMAINS` wrong and a customer telephones. Get **this**
    wrong and every customer keeps working, every dashboard stays green, and
    the only symptom is a 403 on a console one person visits, usually at the
    moment they needed it.

    So check before you depend on it:

    ```console
    $ bin/console deploy:check-control-plane --address=198.51.100.7
    Control plane: served on admin.example.net.
      CONTROL_PLANE_ALLOWED_IPS admits 203.0.113.0/24.
      Every other address gets an empty 403, whatever it asks for on that host.
      198.51.100.7 would be refused.
    ```

    Run without `--address` it also offers the address your SSH session appears
    to come from. That is where your *shell* came from, which is your browser's
    address only if both leave your network the same way.

    The way back in is the shell: the variable is set where you deploy, and the
    same command run on the box tells you what the running process actually
    has.

### If there is a proxy in front

The address is resolved the same way every other client address in Xivi is,
through `TRUSTED_PROXIES` and the forwarded headers Xivi believes, and
**never** from a raw header, because a header anybody can set would make this
list a suggestion rather than a restriction.

The practical consequence: **behind a load balancer you must set
`TRUSTED_PROXIES` as well**, or every request will look as though it came from
the balancer, and the only address you could allow-list would be the balancer
itself, which admits everybody behind it. See
[Hostnames](../running/hostnames.md#if-there-is-a-load-balancer-in-front).

If the command says your address is right and requests are still refused, that
is the reason. Compare the two addresses in the `error` line a refused request
writes, which names both what was resolved and what the connection actually
came from.

### A bad entry does not switch it off

An entry that is not an address or a CIDR range is dropped and admits nobody;
the restriction stays in force. `deploy:check-control-plane` names any such
entry and exits 3, which is deliberate: a list that silently stopped
restricting would be worse than no list at all, because you would go on
believing in it.

### Doing it in the web server as well

Refusing at the edge is stronger, because the request never reaches Xivi, and
the two are complementary rather than alternatives. If you terminate TLS in
your own Caddy, nginx or load balancer, the equivalent rule there costs nothing
and the setting above still holds the day that layer is replaced:

```caddyfile
admin.example.net {
	@blocked not remote_ip 198.51.100.7 203.0.113.0/24
	respond @blocked 403

	# … your usual reverse_proxy / php_server block
}
```

Xivi does not generate that block and does not read it. If you use both, they
are two lists to keep in step, which is the cost of the extra layer.

## What you can do there

Once signed in:

* **Customers**: every tenant, its status, its hostnames, and what it uses.
  Usage figures come from a scheduled command rather than live; until it has
  run, the columns say so rather than showing a zero.
* **Modules**: what this build carries, how far along each module is, and
  [what it costs](../the-basics/modules.md).
* **Purchases**: modules a customer has asked to buy, which is what a priced
  module produces instead of installing itself.
* **Notices**: announcements to every customer or to named ones, shown on
  their dashboards.
* **Support**: tickets customers have raised, answered here and read by the
  customer on the page they asked on.
* **Operators**: from the command line only, as
  [first operator](first-operator.md) describes.

## Next

An installation with no customers has nothing to administer, so:
[first tenant](first-tenant.md).
