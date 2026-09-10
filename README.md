# GraceLinks

A simple link-in-bio page for Grace Generation Church. Public page shows a list
of links; a password-protected admin panel lets staff add, edit, reorder,
show/hide, pin and delete them.

No build step, no external services — just PHP and a single SQLite file.

## What you need on your hosting

- A subdomain pointed at this codebase (e.g. `explore.gracegeneration.co.uk`)
- PHP 8.0 or later
- The `pdo_sqlite` and `fileinfo` PHP extensions enabled (both are on by
  default on almost all cPanel hosts — if in doubt, check
  **cPanel → Select PHP Version → Extensions** and make sure `pdo_sqlite` is
  ticked)
- AutoSSL / Let's Encrypt available on the subdomain (needed because there's
  a login form — cPanel usually does this automatically once the subdomain
  exists)

## Deploying via cPanel Git Version Control

1. In cPanel, create the subdomain first (e.g. `explore`), and note the
   document root it's given (something like `/home/youruser/explore.gracegeneration.co.uk`).
2. Push this codebase to a Git repository you control (GitHub, GitLab, or a
   private repo), or push directly into a bare repo cPanel creates for you
   under **cPanel → Git Version Control → Create**.
3. Point the repository's deployment path at the subdomain's document root
   from step 1, and deploy/pull so the files land there.
4. In **File Manager** (or via SSH), check that the web server user can write
   to two folders — this is required, everything else can stay read-only:
   - `data/` (this is where the SQLite database file gets created)
   - `uploads/` (and its `links/` and `logo/` subfolders — this is where
     uploaded images are stored)

   `755` is usually enough on cPanel; if you get a "could not save" error on
   first use, try `775` on those two folders specifically.
5. Visit the subdomain in a browser. Because no setup has run yet, you'll be
   redirected to a one-time setup wizard.

## First-run setup

The setup wizard (`install.php`) only runs once. It asks for:

- Church name, logo, intro paragraph, and footer/copyright text
- The single admin password everyone managing links will share

After you submit it, `install.php` locks itself (it checks a flag in the
database and redirects straight to the admin login from then on), and you're
taken to `/admin/login.php`.

**If you ever need to reset the admin password without logging in** (e.g. you
forgot it), the simplest route is to delete the `admin_password_hash` value
directly in the database, or just delete `data/church.sqlite` entirely to
start over — note that this also wipes all links, so only do that as a last
resort. A safer option: ask whoever has file access to run one line of PHP
via a temporary script to reset the hash. Ask me if you need a hand with this
later.

## Using the admin panel

Go to `/admin/` (or `/admin/login.php`) and log in with the shared password.

- **Links** — your list of links. Each row has: move up/down, edit, pin,
  show/hide, delete.
- **Pin** — only one link can be pinned at a time. A pinned link always shows
  first on the public page, above everything else, regardless of manual
  order.
- **Show/hide** — hidden links stay in the admin list (so you don't lose
  them) but disappear from the public page. Useful for seasonal links you
  want to switch on and off.
- **Delete** — moves a link to **Trash**, not a permanent delete. Trashed
  links are kept for 7 days (in case someone clicks the wrong one) and are
  then removed automatically. You can also restore a trashed link, or delete
  it permanently right away, from the Trash page.
- **Settings** — church name, logo, intro text, footer message, and changing
  the shared password.

Sessions time out after 2 hours of inactivity — after that, admins need to
log in again.

## How it's built (if you want to poke around)

- `index.php` — the public page
- `go.php` — every public link actually points here first; it logs a click
  then redirects to the real URL (this is how click counts get recorded)
- `install.php` — one-time setup wizard
- `admin/` — the admin panel (login, dashboard, add/edit form, trash,
  settings, and the small action scripts for pin/hide/reorder/delete)
- `includes/` — shared PHP: database connection + schema, auth, CSRF
  protection, and all the link/settings helper functions
- `assets/css/style.css` — the whole visual design lives in this one file
- `data/church.sqlite` — the database (created automatically on first run)
- `uploads/links/` and `uploads/logo/` — uploaded images

There's no framework and no dependencies to install — it's all plain PHP, so
it should keep working with minimal maintenance for years.

## A couple of things worth knowing

- **Backups:** since everything (links, settings, and the admin password) is
  in `data/church.sqlite`, backing that one file up regularly is effectively
  a full backup of the site. Worth checking whether your cPanel plan includes
  automatic backups, and if not, downloading that file occasionally.
- **Multiple admins:** everyone uses the same password. There's no per-person
  login or activity log — if that becomes a problem later (e.g. wanting to
  know who changed what), that's a bigger change and worth a conversation
  before adding more admins.
- **Click counts:** links are tracked in the database (a `clicks` column) but
  there's no page in the admin panel to view them yet — this was marked
  "nice to have, not critical" in the spec. Happy to add a simple stats view
  later if useful.
