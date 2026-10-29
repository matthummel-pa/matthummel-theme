# Product delivery

How a product reaches a buyer or a visitor. WooCommerce was removed from the theme in 3.12 and 3.13; before that this file described the WooCommerce download shop. Products are now sold through Freemius or downloaded for free from a public GitHub release.

Rule for every product: **do not commit theme or plugin zips to this (matthummel) repo.** Zips live in the product's own repo releases or in Freemius.

## Paid themes: Freemius

Cobble & Candle is the first one. Freemius is the merchant of record: it takes the payment, handles sales tax and VAT, issues the license key, sends the receipt, delivers the zip and the updates.

```
Product page /themes/{slug}/
  → Pricing section (#buy), plan picker from the catalog `freemius` block
  → Freemius checkout opens in an overlay (no cart page)
  → receipt + license key by email, download and updates from Freemius
  → buyer lands on /account/, which links to the Freemius Customer Portal
```

- **Catalog:** the `freemius` block in `resources/data/product-catalog.json` (store, product, plan, public key, tiers). Prices are set in the Freemius dashboard and mirrored in the catalog by Matt. `mh_project_freemius()` reads it.
- **Refunds, renewals, white-label:** configured in the Freemius dashboard, not in code. Keep page copy in line with them.
- **Releasing a new version:** bump `Version:` in the theme's `style.css`, push a tag `vX.Y.Z` in the product repo. Its `release-freemius.yml` builds the zip, attaches it to a GitHub Release and uploads it to Freemius as **pending**. It needs the repo secret `FREEMIUS_API_TOKEN` and the variable `FREEMIUS_PRODUCT_ID`; without them it builds and attaches the zip and skips the upload. Matt sets the version to **Released** in the Freemius Developer Dashboard → Deployment. That keeps a person between a tag and a paid update.
- **Testing a purchase on staging:** define `MH_FREEMIUS_SANDBOX_TOKEN` and `MH_FREEMIUS_SANDBOX_CTX` in the staging `wp-config.php` (values from the Freemius dashboard). They are ignored on matthummel.com. Never commit them.

## Free plugins: GitHub release

TOCguide is the first one. There is no cart and no login.

```
Product page /plugins/{slug}/
  → Download free  →  https://github.com/{owner}/{repo}/releases/latest/download/{asset}.zip
```

The link is built by `mh_project_free_download_url()` from the catalog `download` block (`github_repo` and a fixed `asset` name), and only for products with `price` 0. Publish a new release in the plugin's repo and the link serves it.

## Not on sale yet

`coming_soon: true` in the catalog. The product page and the card show a **Coming soon** badge, no buy button, and no schema.org Offer. Acreline and WalkRidge are in this state until they have Freemius products.

## What this replaced

The WooCommerce shop (Cart, Checkout, My Account, product syncing, download delivery, update emails, the `wp mh shop-downloads` commands) is gone. Old WooCommerce product posts, orders and the `_mh_*` meta that may still sit in an old database are ignored by the theme. Old `/product/{slug}/` URLs 301 to the product page.
