# ARKI Environments – Website & CMS (Phase 1: Homepage)

Built with PHP 8, MySQL, HTML5 and CSS3. It has no frameworks, no Composer and no build step, so it runs on any standard cPanel host.

---

## 1. Requirements

* PHP **8.0 or newer** (8.2+ recommended): cPanel → *MultiPHP Manager*
* MySQL 5.7+ or MariaDB 10.3+
* PHP extensions: `pdo_mysql`, `gd` (for image resizing and WebP), `fileinfo`. These are on by default on most cPanel hosts.
* Apache with `mod_rewrite` and `mod_headers`. LiteSpeed also works.

## 2. Installing on cPanel

1. **Create the database.** Go to cPanel → *MySQL® Databases*:
   * create a database (e.g. `cpuser_arki`)
   * create a user with a strong password
   * add the user to the database with **All Privileges**
2. **Import the data.** Go to cPanel → *phpMyAdmin* and click your database in the left-hand list. Then click **Import**, choose `database.sql` and click **Import / Go**. This creates all 5 tables with the homepage content.
3. **Upload the website.** Go to cPanel → *File Manager* → `public_html`. Upload the ZIP and click **Extract**. The files extract directly into `public_html`, with `index.php` at the top level.
4. **Connect to the database.** In File Manager, open `config/config.php` and click **Edit**. Replace the placeholders with your database name, user and password, then save.
   * If you installed into a sub-folder (e.g. `public_html/dev`), also set `'base_url' => '/dev'`.
5. **Delete `database.sql`** from `public_html`. It is blocked from the web, but it doesn't need to stay on the server.
6. **Log in and change the default passwords.**

| Login | Where | Default username / password |
|---|---|---|
| CMS | `yourdomain.com/admin/` | `admin` / `ArkiAdmin@2026` → change it under **Administrators** |
| Website preview (lock is ON) | `yourdomain.com` | `arki-preview` → change it or turn the lock off under **Settings** |

**If the site shows a message instead of the homepage:**

* *"Almost there… enter your database name"*: `config/config.php` still has the placeholders.
* *"Could not connect to the database"*: the name, user or password is wrong, or the user isn't added to the database. Set `'debug' => true` temporarily to see the exact error.
* *"…the tables are missing"*: import `database.sql` into the **same** database named in `config.php`.

## 3. Using the CMS

| Menu | What it manages |
|---|---|
| **Homepage** | Every homepage section. You can edit text, images, links and buttons, reorder sections (↑ ↓) and show or hide them. Sections with cards, slides or reviews let you add, edit, copy, reorder, hide and delete each item. |
| **Header & Footer** | The logo, Login/Search/Cart links, main menu (including dropdown links), footer columns, footer links, contact/social buttons, payment logos, copyright and legal links. |
| **Settings** | Site name, browser title, favicon and the **development password lock**. |
| **Administrators** | Add or remove CMS users and change your own password. |

**About images**

* Uploads accept JPG, PNG, WebP and GIF up to 10 MB.
* Images over 2400 px are resized automatically.
* A WebP copy is created automatically, and browsers that support WebP are served it.
* Each image field shows the recommended size.
* Images in `uploads/demo/` are **low-resolution placeholders cut from the design mockup**. Replace them with the final photography and logos through the CMS.

**Line breaks:** in heading fields, press Enter to force a line break (e.g. "Quality Brands. ⏎ Considered Solutions."). In body text, leave a blank line between paragraphs.

## 4. Development-stage protection

* **Password lock:** go to Settings → Development stage and tick *Password-protect the public website*. Visitors then see a password screen. Logged-in admins pass straight through. Repeated wrong attempts are throttled.
* **No indexing:** every page sends `<meta name="robots" content="noindex, nofollow">` and an `X-Robots-Tag: noindex` HTTP header. Uploaded images also get the header.
  `robots.txt` is deliberately **not** used to block crawlers, because Google has to be able to read a page to see its `noindex`.
* **Server-level password (optional):** `.htaccess` includes a commented-out Basic Auth block. You can also use cPanel → *Directory Privacy*.

## 5. Going live (later)

1. Turn off the password lock in Settings.
2. Remove the `noindex` directives:
   * the `X-Robots-Tag` line in `app/helpers.php` → `send_security_headers()`
   * the two robots `<meta>` tags in `templates/layout/head.php`
   * the `X-Robots-Tag` line in `.htaccess` and in `uploads/.htaccess`
3. Uncomment the HTTPS redirect at the top of `.htaccess`.

## 6. Security measures included

* PDO prepared statements everywhere, so there is no SQL injection.
* All output is escaped. CMS links are restricted to `http(s)`, relative paths, `mailto:` and `tel:`, so `javascript:` links are blocked.
* CSRF tokens on every form. All changes are POST-only.
* Passwords use `password_hash` (bcrypt/argon). The session is regenerated on login and expires after 2 hours idle. Cookies are HttpOnly and SameSite.
* Login and preview-password brute-force throttling: 5 attempts per 15 minutes per IP.
* Uploads are checked by their real MIME type and pixel data, then re-encoded with GD, which strips EXIF and any hidden payloads. Files get random names. SVG is not accepted. PHP execution is disabled in `uploads/`.
* `app/`, `config/` and `templates/` are blocked from the web. `config/` has its own deny rule. You can also set `config/config.php` to `640` permissions.
* Headers: Content-Security-Policy (no inline scripts), X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, and HSTS on HTTPS.

## 7. Performance

* Fonts are self-hosted (Noto Serif, Latin subset, WOFF2), with no third-party requests, preloaded and using `font-display: swap`.
* One CSS file (≈24 KB, ≈6 KB gzipped) and one deferred JS file (≈4 KB), with no libraries.
* Images are served as WebP with JPG/PNG fallback. Everything below the hero is lazy-loaded. Every image has width/height set so the layout doesn't shift while loading.
* Gzip and long-term browser caching are set in `.htaccess`, and asset URLs are cache-busted automatically when files change.

## 8. Code structure

```
/                     index.php (homepage front controller), .htaccess
app/                  (blocked from web)
  bootstrap.php       config, DB, session, security headers
  Database.php        PDO wrapper
  helpers.php         escaping, URLs, images (<picture>/WebP), CSRF, throttling
  ContentRepository   reads/writes sections + items
  Settings, Auth, DevLock, Uploader, AdminForm
  schema/pages.php    ★ defines every page, section and editable field
templates/            (blocked from web)
  layout/             page shell, head, header, footer
  sections/           one template per homepage section
  admin/              CMS layout
assets/               css/main.css, js/main.js, fonts/
admin/                CMS pages
uploads/              CMS images (scripts can't run here)
config/config.php     database connection settings (blocked from web)
```

### Adding inner pages (next phase)

The CMS forms are generated from `app/schema/pages.php`, so a new page needs **no database changes**:

1. Add a page block to `app/schema/pages.php`, e.g. `'about' => ['label' => 'About Us', 'sortable' => true, 'sections' => [...]]`.
2. Create a template for each section in `templates/sections/` (or reuse an existing one).
3. Create the front controller, e.g. `about/index.php`, using the same pattern as `index.php`: `render('layout/page', ['sections' => ContentRepository::page('about'), …])`.
4. Add a menu link to the page in **Header & Footer**.

Opening the page in the CMS creates its sections in the database automatically. Field types available: `text`, `multiline`, `textarea`, `url`, `image`, `select`.
