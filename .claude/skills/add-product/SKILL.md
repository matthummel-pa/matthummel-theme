---
name: add-product
description: "Add a new WordPress theme, plugin, or web app to the matthummel.com shop catalog (product-catalog.json, product page, deploy). Use when Matt asks to add a product."
---

# Add a New Product to the Shop

Use this skill when Matt asks to add a new WordPress theme, plugin, or (later) document to the matthummel.com shop.

There is no WooCommerce. A product is a catalog entry that becomes a Project post. Paid themes check out through Freemius on the product page. Free plugins are a direct download from a public GitHub release. Nothing is deployed to production by an agent: everything lands on staging (hummel-dev) and Matt publishes staging to live from hPanel.

## Overview

| Location | Purpose |
|---|---|
| `resources/data/product-catalog.json` | Ground truth for copy, schema, screenshots, how it is sold |
| `app/concept-pages.php` | One-time migration that writes the entry into the Project post |
| `_mh_project_*` post meta | Admin edits on the Project post; they win over the JSON |
| `resources/images/products/{slug}/` | Screenshots and the card image |

`mh_product_entry()` in `app/shop.php` merges the JSON and the post meta. The Project post gets its URL from the product type: `/themes/{slug}/`, `/plugins/{slug}/`, `/documents/{slug}/` (`mh_product_types()`). It shows up on `/shop/` and on its type's listing page by itself.

---

## Step 1: Add the entry to product-catalog.json

Open `resources/data/product-catalog.json`. Each product is an object keyed by slug. Copy an existing entry and edit it:

```json
{
  "my-new-product": {
    "title": "My New Product",
    "product_type": "theme",
    "eyebrow": "Theme · Restaurants",
    "blurb": "Two or three sentences. Feeds the card and the meta description.",
    "summary": "Longer overview shown at the top of the product page.",
    "challenge": "What problem this solves.",
    "approach": "How I built it.",
    "result": "What you walk away with.",
    "audience": "Who it is for.",
    "architecture": "Stack sentence or two.",
    "handoff": "What you receive and how you edit it.",
    "benefits": ["Benefit one", "Benefit two"],
    "deliverables": ["Theme .zip", "Documentation"],
    "tech": ["WordPress", "Sage", "Tailwind", "Vite"],
    "version": "1.0.0",
    "compatible": "WordPress 6.5+, PHP 8.3+",
    "license": "GPL-2.0-or-later",
    "demo": "https://my-new-product.matthummel.com/",
    "github": "https://github.com/matthummel-pa/my-new-product",
    "docs": [["Owner guide", "https://example.com/guide"]],
    "image": "products/my-new-product/featured.webp",
    "screenshots": [["products/my-new-product/featured.webp", "What this screen shows"]],
    "faq": [["Question?", "Answer."]],
    "seo_title": "Under 60 characters",
    "seo_description": "Under 155 characters",
    "live": true,
    "for_sale": true
  }
}
```

Only claim what is true: real features, real demos, real numbers. No invented metrics, testimonials or client names.

### How it is sold (pick one)

- **Paid theme through Freemius:** add a `freemius` block (`store_id`, `product_id`, `plan_id`, `public_key`, `tiers`). Matt creates the product, plan and prices in the Freemius dashboard, and every price comes from him. The page then gets a Pricing section and the card says **Choose a plan**.
- **Free zip:** set `"price": "0"` and add `"download": { "github_repo": "owner/repo", "release": "latest", "asset": "name.zip", "label": "name.zip" }`. The asset must be a fixed `.zip` name. The card and page say **Download free**.
- **Not on sale yet:** set `"coming_soon": true`. No buy button, a **Coming soon** badge, no Offer in the page markup.

---

## Step 2: Add a one-time migration

The JSON alone does not update posts that already exist. In `app/concept-pages.php`, copy the newest `mh_apply_product_catalog_vNN` function, bump the number, and upsert only your slug so edits to other products are left alone:

```php
function mh_apply_product_catalog_v16(): void
{
    if (get_option('mh_product_catalog_v16') || wp_installing()) {
        return;
    }

    $seed = mh_product_catalog_seed_data()['my-new-product'] ?? null;
    if (! is_array($seed)) {
        return;
    }

    if (mh_upsert_catalog_product('my-new-product', $seed, true) > 0) {
        update_option('mh_product_catalog_v16', true);
    }
}
```

Register it next to the others (`add_action('init', __NAMESPACE__.'\\mh_apply_product_catalog_v16', 46);`). Before adding any function, check the name is not already defined in `app/`.

---

## Step 3: Images

Put optimized WebP files in `resources/images/products/{slug}/`:

- `featured.webp`: the card and hero image, 1200×750 px, ≤150 KB
- gallery screenshots: 1200×750 px, ≤120 KB each

Reference them in the JSON relative to `resources/images/`. Alt text describes what the screen shows.

---

## Step 4: Rank Math

The catalog `seo_title` and `seo_description` seed the Rank Math title and description once; anything saved in wp-admin wins. In wp-admin, open the product's Project post and set the **focus keyphrase** (the primary search phrase). Target score 80+.

---

## Step 5: Internal links

Add an internal link to the new product page from:

- [ ] About page (side panel or body)
- [ ] Now page (currently shipping section)
- [ ] At least one existing blog post
- [ ] Support page product listing

---

## Step 6: Check, ship to staging

1. `php -l` on changed PHP, `python3 .github/scripts/wp-review`, Pint, and `npm run build` (say what you could not run).
2. Branch, small PR, CHANGELOG line and a `style.css` / `readme.txt` version bump.
3. After merge, install on **staging only** (`wp mh theme-update` over SSH, or Appearance → Update Theme), flush caches, then open `/shop/`, the listing for its type, and the product page.
4. Matt publishes staging to live from hPanel. Do not install on or edit `matthummel.com`.

---

## Reference files

| File | What it does |
|---|---|
| `resources/data/product-catalog.json` | Catalog JSON |
| `app/concept-pages.php` | `mh_product_types()`, `mh_upsert_catalog_product()`, catalog migrations |
| `app/shop.php` | `mh_product_entry()`, `mh_shop_items()`, `mh_project_freemius()`, `mh_project_free_download_url()` |
| `resources/views/partials/content-single-project.blade.php` | Product page template |
| `resources/views/partials/freemius-buy.blade.php` | Pricing section and Freemius overlay |
| `resources/views/template-shop.blade.php` | `/shop/`, `/themes/`, `/plugins/` listing |
| `app/filters.php` | SEO title and meta for product pages |
