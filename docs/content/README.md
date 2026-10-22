# Content snapshot

The CMS content of the local site (webis.test), exported 2026-10-22, so a session without the
database knows what the pages hold. Read-only reference: nothing loads these files, and the
database stays the source of truth. A session in the cloud has an empty database, so pages built
from blocks render empty there; check design changes locally, against the real content.

## What is where

- `index.json`: every published page (type, title, address, blog category), the blog
  categories with their article counts, and every published project (type, client, year,
  summary).
- `pages/{slug}.json`: whole pages, as stored: type, title, address, SEO, and the `blocks`
  array, each block `{ id, type, v, data }`. The template for block type `x` is
  `resources/views/blocks/x.blade.php` (`App\Blocks\BlockRegistry` lists the types).
  - `acasa`: the homepage's blocks (hero, statement, service cards). The rest of the homepage
    (the 3D stage, the logo wall, the latest articles) is code: `resources/views/public/home.blade.php`.
  - `contact`, `produse`, `portofoliu`: the site's own pages (their templates are mostly code:
    `public/contact.blade.php`, `public/clienti.blade.php`).
  - One of each type imported from the old WordPress site: an article
    (`imbunatatiti-vizibilitatea-…`), an industry page at `/servicii/{slug}/`
    (`creare-site-stomatologie`), a service page (`creare-magazin-online`) and the legal page
    (`politica-de-confidentialitate`). Rendered by `public/page.blade.php`.
- `project-little-tokyo.json`: one project, as `/proiect/{slug}/` shows it.

Pages not built from blocks (`/web/`, `/software-la-comanda/`, `/blog/`) are entirely in their
Blade templates under `resources/views/public/`.

Images are referenced by asset id (`image_asset_id`, `data-asset-id`); the files are not in the
repository.
