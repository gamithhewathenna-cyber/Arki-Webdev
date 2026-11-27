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
| **Work / Learn / Store Page** | The three storefront landing pages (`/work/`, `/learn/`, `/store/`), section by section. |
| **Header & Footer** | The logo, Login/Search/Cart links, main menu (including dropdown links), footer columns, footer links, contact/social buttons, payment logos, copyright and legal links. |
| **Products** | The product catalogue (see below). |
| **Settings** | Site name, browser title, favicon and the **development password lock**. |
| **Administrators** | Add or remove CMS users and change your own password. |

**About images**

* Uploads accept JPG, PNG, WebP and GIF up to 10 MB.
* **Cropping is mandatory.** As soon as you choose an image, a cropper opens with the exact shape for that spot on the website. Drag to position, zoom with the slider or mouse wheel, then click **Crop & use image**. Without a confirmed crop the image can't be saved.
* Each image field shows its final size (e.g. 840 × 1200 px). Gallery images let you pick a shape. Logos and the favicon can be zoomed out and are saved as transparent PNGs.
* A WebP copy is created automatically, and browsers that support WebP are served it.
* Images in `uploads/demo/` are **low-resolution placeholders cut from the design mockup**. Replace them with the final photography and logos through the CMS.

**Line breaks:** in heading fields, press Enter to force a line break (e.g. "Quality Brands. ⏎ Considered Solutions."). In body text, leave a blank line between paragraphs.

### Products

| Submenu | What it does |
|---|---|
| **All Products** | Search, filter (storefront, category, brand, stock, status), bulk actions, Duplicate/Delete, and drag ⋮⋮ to set the display order. |
| **Add New Product** | Ten tabs, each saved on its own: General Information, Pricing, Images & Media, Product Options, Specifications, Technical Information, Downloads, Product Family, Related Products, Inventory & Visibility. |
| **Categories … Environmental Ratings** | The central lists that products choose from and the website filters by. Categories can have subcategories; colours have a HEX code and optional swatch image; brands have a logo. |
| **Product Families** | Collections (e.g. Luna Collection). Members show each other under “Family Of Products”. |
| **Inventory Management** | Stock for every product in one table. Low stock is highlighted. |
| **Storefront Settings** | Listing banners for Work / Learn / Store, the call-to-action banners and the product page texts. |

* **One product, three storefronts.** Tick **Work** and/or **Learn**; the main **Store** always shows every published product. Pricing and stock are shared.
* Only **Published** products appear on the website. Logged-in administrators can preview drafts via *View on website*.
* **Agent / dealer prices** are stored but never sent to the website – visitors see “Login To View”. Add To Quote, Add To Wishlist and the heart icons are shown but inactive until those features are built.
* Website pages: `/work/products/`, `/learn/products/`, `/store/products/` (filters, sorting, 9 per page) and `/product/?p=product-name`.

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

**A page with the Work / Learn layout** (banner, intro, product categories, solutions, projects, brands, inspiration, CTA), e.g. Store:

1. `app/schema/pages.php`: add `$schema['store'] = $innerPage('Store Page', 'store/');`
2. `app/schema/seeds.php`: add a `'store' => $inner([...banner...], 'Explore All Store Products', [...categories...])` entry, copying the `learn` one.
3. Copy `learn/` to `store/` and change `render_inner_page('learn')` to `render_inner_page('store')`.
4. To link a homepage card to it, add a line to `app/Migrations.php`: `'…-link-store-card' => static fn() => self::linkHeroCard('Store', 'store/')`. You can also set the card's Link URL in the CMS.

**A page with a different layout:**

1. Add a page block to `app/schema/pages.php`, e.g. `$schema['about'] = ['label' => 'About Us', 'url' => 'about/', 'sortable' => true, 'sections' => [...]];`. You can reuse homepage section definitions (`$home['solutions']`, …).
2. Create a template for each new section in `templates/sections/` (or reuse an existing one). A section called `banner` puts the header over the image in white.
3. Add starting content to `app/schema/seeds.php`. Use `'copy_from' => 'home'` to start a section as a copy of the homepage one. It's created on the first visit, so no SQL import is needed.
4. Create the front controller `about/index.php` the same way as `learn/index.php`: `render_inner_page('about')`.
5. The page appears in the CMS menu automatically. Add a link to it from the menu or a card (Link URL `about/`).

To change content in databases that are already live (e.g. to set a link), add a one-time entry in `app/Migrations.php`.

Opening the page in the CMS creates its sections in the database automatically. Field types available: `text`, `multiline`, `textarea`, `url`, `image`, `select`.

### Product catalogue (for developers)

* Tables: `products`, `product_terms` (all managed lists, keyed by `taxonomy`), `product_families`, `product_storefronts`, `product_images` (gallery + drawings), `product_specs`, `product_downloads`, `product_term_links` (offered sizes/colours/materials), `product_variants`, `product_related`. Definitions + demo data: `app/schema/products_db.php` (applied once by `app/Migrations.php`; the same SQL is in `database-update-products.sql`).
* Queries: `app/ProductRepository.php`. Public methods never select `agent_price` – keep it that way. Lists/families: `app/Catalog.php`. Storefront pages: `app/Shop.php` + `templates/shop/`.
* A new list type (e.g. “Finishes”) = one entry in `Catalog::TAXONOMIES`; the admin screen and menu item appear automatically.
* Ready for later phases: carts, quotes, orders and agent accounts can reference `products.id` / `product_variants.id`, which already carry SKU, currency, agent price and stock.
* Product image crop sizes are set once in `P_CROP` in `admin/products/_lib.php`.

### Image fields must be cropped (required for all future development)

Every image upload goes through the mandatory cropper, and the server rejects images that don't match the crop shape. When you add **any** new image field (new page, section, item or setting):

1. Give it a `'crop'` spec in `app/schema/pages.php`. Use the aspect ratio the image is displayed at in `assets/css/main.css`, at about 2× its on-screen size:
   ```php
   'image' => ['type' => 'image', 'label' => 'Banner', 'crop' => ['w' => 2400, 'h' => 900]],
   'logo'  => ['type' => 'image', 'label' => 'Logo',   'crop' => ['w' => 600, 'h' => 300, 'fit' => true]], // transparent PNG, may zoom out
   'photo' => ['type' => 'image', 'label' => 'Photo',  'crop' => ['presets' => [['Portrait', 800, 1000], ['Square', 1000, 1000]]]],
   ```
2. Always render and save it with `AdminForm::render()` / `AdminForm::collect()`. Never write a plain `<input type="file">`. These two functions pass the crop shape to the cropper (`admin/assets/admin.js`) and to `Uploader::image($file, $crop)`.
3. For uploads that aren't part of a page section (e.g. the favicon in `admin/settings.php`), define a field array with `'crop'` and use the same `AdminForm` calls.

A field without `'crop'` falls back to 2400 × 1600, which is almost never the right shape, so always set one.
