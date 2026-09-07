# Skill: Add a New Product to the Shop

Use this skill when Matt asks to add a new WordPress theme, plugin, or web app to the matthummel.com shop catalog.

## Overview

Products live in two places that must stay in sync:

| Location | Purpose |
|---|---|
| `resources/data/product-catalog.json` | Ground truth for copy, schema, screenshots, features |
| WooCommerce product (wp-admin) | Price, cart, purchase flow |
| `_mh_project_*` post meta | Admin overrides that win over JSON |

The PHP function `mh_product_entry(int $product_id)` in `app/shop.php` merges both sources; the WC meta wins on any key it defines.

---

## Step 1 — Add the entry to product-catalog.json

Open `resources/data/product-catalog.json`. Each product is an object keyed by `slug`. Copy an existing entry and edit:

```json
{
  "my-new-product": {
    "name": "My New Product",
    "slug": "my-new-product",
    "product_type": "theme",
    "eyebrow": "WordPress theme",
    "tagline": "One sentence that hooks a buyer.",
    "blurb": "Two or three sentences. Appears in Rank Math meta description.",
    "summary": "Longer overview paragraph shown before screenshots on the product page.",
    "challenge": "What problem this solves.",
    "approach": "How I built or architected it.",
    "result": "What you walk away with.",
    "audience": "Who it is for.",
    "handoff": "What you receive after purchase.",
    "architecture": "Stack sentence or two.",
    "version": "1.0.0",
    "compatible": "WordPress 6.5+",
    "license": "GPL-2.0-or-later",
    "demo": "https://my-new-product.matthummel.com",
    "github": "https://github.com/matthummel-pa/my-new-product",
    "support": "https://matthummel.com/support/#my-new-product",
    "benefits": [
      "Benefit one",
      "Benefit two"
    ],
    "deliverables": [
      "Theme .zip (install under Appearance → Themes)",
      "Child theme starter",
      "Documentation"
    ],
    "tech": ["WordPress", "Sage", "Tailwind", "Vite"],
    "blocks": ["Block One", "Block Two"],
    "screenshots": [
      ["resources/images/my-new-product-hero.webp", "Homepage hero"],
      ["resources/images/my-new-product-listing.webp", "Listing page"]
    ],
    "docs": [
      ["Install guide", "https://cdn.jsdelivr.net/gh/matthummel-pa/my-new-product@main/docs/install.html"],
      ["Customizer guide", "https://cdn.jsdelivr.net/gh/matthummel-pa/my-new-product@main/docs/customizer.html"]
    ],
    "files_included": ["theme.zip", "child-theme.zip", "docs/"],
    "brand_tagline": "Tagline for the brand card.",
    "brand_palette": [
      ["Primary", "#1a2e4a"],
      ["Accent", "#3b82f6"]
    ],
    "faq": [
      ["Is this a real estate theme?", "Answer here."],
      ["Does it include demo content?", "Yes — one-click import via the Getting Started wizard."]
    ],
    "metrics": [],
    "is_product": true,
    "for_sale": true
  }
}
```

**Required** fields for a buyable product: `name`, `slug`, `product_type` (`theme`|`plugin`|`app`), `blurb`, `is_product: true`, `for_sale: true`.

**`product_type`** controls the hero eyebrow label, `SoftwareApplication.applicationCategory`, and the buy/download button label. Use `plugin` for WordPress plugins and `app` for non-WordPress web apps.

---

## Step 2 — Create the WooCommerce product in wp-admin

1. Go to **Products → Add New**.
2. Set the product title to match `name` in the JSON.
3. Set permalink slug to match `slug` in the JSON (e.g. `my-new-product`).
4. Set the **Product category** to `themes` or `plugins` (matches the shop catalog filter).
5. Set the **price**. Free products: leave price blank or set to `0`.
6. Upload a **Featured image** (1200×750 px for themes; 1200×600 for plugins). Rank Math picks this up for the `image` schema field.
7. **Do not** fill in the product short or long description — these come from the JSON file.
8. Publish. Note the product ID.

---

## Step 3 — Sync catalog meta via WP-CLI

On the live server, run the seed WP-CLI command so the `_mh_project_*` meta is written from JSON to the WC product:

```bash
# In wp-admin PHP or WP-CLI eval:
wp eval 'App\mh_seed_product_catalog_entry("my-new-product");'
```

Or trigger the one-shot seed function by bumping the `mh_product_catalog_v*` option key in `app/shop.php` — find the latest `mh_product_catalog_v{N}` option check and increment `N` by one.

---

## Step 4 — Add a featured image to resources/images/

Place optimized WebP screenshots in `resources/images/`. Recommended sizes:

- Hero screenshot: `1200×750` px, ≤150 KB
- Gallery screenshots: `1200×750` px, ≤120 KB each

Reference the filename in `screenshots` array in the JSON (no leading slash; relative to `resources/images/`).

---

## Step 5 — Set Rank Math focus keyword on the product

In wp-admin, open the product edit screen → Rank Math panel → set:

- **Focus keyphrase**: the primary search phrase (e.g. `WordPress real estate theme`).
- **Meta description**: paste the `blurb` value.
- The theme auto-fills `post_content` with analysis HTML on first save, so the score should climb to 70+ immediately. Tweak the focus phrase, H1, and meta description to push above 80.

---

## Step 6 — Internal linking checklist

Add an internal link to the new product page from:

- [ ] About page (side panel or body)
- [ ] Now page (currently shipping section)
- [ ] At least one existing blog post
- [ ] Support page product listing
- [ ] `product-catalog.json` related product cross-links (if you add a `related` key)

---

## Step 7 — Build and deploy

```bash
npm run build
vendor/bin/pint
# commit + push → GitHub Actions builds and deploys
```

---

## Reference files

| File | What it does |
|---|---|
| `resources/data/product-catalog.json` | Catalog JSON |
| `app/shop.php` | `mh_product_entry()`, `mh_shop_product_payload()`, seed hooks |
| `resources/views/woocommerce/single-product.blade.php` | Product landing page template |
| `resources/views/woocommerce/archive-product.blade.php` | Shop listing template |
| `app/filters.php` | SEO title/meta for products and shop |
