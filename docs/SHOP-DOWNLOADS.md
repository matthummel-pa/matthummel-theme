# Theme download shop (WooCommerce)

How Acreline, WalkRidge, and other zip products get into buyers’ hands on **[hummelwp.com](https://hummelwp.com)**.

## How it works

```text
Product repo (wp-acreline / wp-walkridge)
  → GitHub Release zip (theme-latest or a version tag)
  → catalog `download` in resources/data/product-catalog.json
  → wp mh shop-downloads
  → WooCommerce product: Virtual + Downloadable
  → buyer pays (WooPayments)
  → receipt email + My account → Downloads
```

Do **not** commit theme zips into this (matthummel) repo. The shop product points at the GitHub asset URL (or a copy under `uploads/woocommerce_uploads/` if you run `--localize`).

## Catalog `download` block

Each for-sale theme/plugin in `resources/data/product-catalog.json` needs:

```json
"download": {
  "github_repo": "matthummel-pa/wp-acreline",
  "release": "theme-latest",
  "asset": "latest",
  "asset_prefix": "acreline-",
  "label": "acreline.zip"
}
```

| Field | Meaning |
| --- | --- |
| `github_repo` | `owner/repo` that publishes the zip |
| `release` | Tag name (`theme-latest`) or `latest` for GitHub’s latest release |
| `asset` | Exact zip filename, or `latest` to pick the highest `asset_prefix` + semver |
| `asset_prefix` | e.g. `acreline-` matches `acreline-1.5.4.zip` |
| `label` | Name shown to the buyer (usually `acreline.zip`) |

## Publish a new theme zip (Acreline pattern)

1. In the **product** repo, build the installable pack (`bin/build-theme-zip.sh` or your marketplace pack script).
2. Attach it to GitHub Release **`theme-latest`** as `acreline-X.Y.Z.zip` (versioned name required for update emails).
3. On the shop site (after this theme is deployed at ≥ 3.5.14):

```bash
wp mh shop-downloads --force --slug=acreline
```

Buyers who already purchased get the **Product download update** email when the version increases. First attach does not email.

WalkRidge must use the same pattern once a built zip exists. Until then, keep the WalkRidge product **draft** or **out of stock** so nobody pays for an empty Downloads page.

## One-time shop settings (digital goods)

Seeded by `mh_seed_digital_download_store()` when WooCommerce is active:

| Setting | Value |
| --- | --- |
| Shipping | Disabled |
| Guest checkout | On (account signup offered) |
| Download method | Force (PHP serves the file) |
| Grant access after payment | Yes |
| Require login to download | No (receipt link works) |
| Reviews | Off |

Payments on hummelwp.com: **WooPayments** (card + Apple Pay / Google Pay). No need for a separate Stripe plugin.

Also keep **WooCommerce → Settings → Products → Approved download directories** allowing `https://github.com/matthummel-pa/` (the theme adds these automatically).

## Canonical products on hummelwp.com

| Product | SKU | Price | Download source |
| --- | --- | --- | --- |
| Acreline | `theme-acreline` | $79 | `wp-acreline` → `theme-latest` → `acreline-*.zip` |
| WalkRidge | `theme-walkridge` | $59 | Needs a built GitHub zip first |
| TOCflow | `plugin-tocflow` | $0 | `tocflow` release zip |

Use the SKU’d products (`/product/acreline/`, `/product/walkridge/`, `/product/tocflow/`). Hide or trash SKU-less stubs (`acreline-2`, etc.) so the shop grid stays clean.

## Buyer path after purchase

1. Checkout with email (guest OK).
2. Order completes → Woo grants download permission.
3. Completed-order email includes the download link.
4. **My account → Downloads** lists the zip; version badge updates when you ship a newer release.

## CLI cheat sheet

```bash
# Attach / refresh GitHub zips for all for-sale catalog products
wp mh shop-downloads

# One product, re-fetch even if a file exists
wp mh shop-downloads --force --slug=acreline

# Copy remote zip into uploads/woocommerce_uploads (optional)
wp mh shop-downloads --force --localize --slug=acreline

# Resend update mail to prior buyers
wp mh shop-notify-updates --slug=acreline
```

## Checklist before you sell a theme

- [ ] GitHub Release has a versioned `.zip` buyers can install via Appearance → Themes
- [ ] Catalog `download` points at that repo/release/prefix
- [ ] `wp mh shop-downloads --slug=…` reports `attached` or `already attached`
- [ ] Product is Virtual + Downloadable with at least one file in wp-admin
- [ ] WooPayments is live (not test) if you want real charges
- [ ] Test order: free product or test mode → confirm My account → Downloads
- [ ] No SKU-less duplicate products visible in `/shop/`
