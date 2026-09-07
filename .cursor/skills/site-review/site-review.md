# Skill: Monthly Site Review

Use this skill when Matt asks for a site review, performance check, or SEO audit.

## Overview

Run through these checks in order. Fix small issues in-place. Log larger findings in a numbered list and present them after the review.

---

## 1. Dead links and stale references

```bash
# Check for references to old /projects/ URL
rg "/projects/" resources/views --include="*.blade.php" --include="*.php"

# Check for references to deleted templates
rg "template-projects\|content-single-project" app/ resources/

# Check for hardcoded HTTP in production URLs
rg "http://matthummel" resources/ app/ --include="*.php" --include="*.blade.php"
```

Expected: zero results. Fix any found.

---

## 2. SEO quick audit

Check each key page:

| Page | Expected title (first ~40 chars) | Focus keyword |
|---|---|---|
| / (home) | `WordPress Developer` | WordPress developer |
| /shop/ | `WordPress Themes, Plugins & Web Apps` | WordPress themes plugins |
| /shop/acreline/ | `Acreline Real Estate Theme \| WordPress Theme` | WordPress real estate agency theme |
| /shop/tocflow/ | `TOCflow \| WordPress Plugin` | WordPress table of contents plugin |
| /portfolio/ | `WordPress Developer Portfolio & GitHub` | WordPress developer portfolio |
| /journal/ | `WordPress Development Journal` | WordPress development |

Run this to spot-check:
```bash
curl -s http://localhost:8080/shop/ | grep -o '<title>[^<]*</title>'
```

---

## 3. Schema markup audit

Check product pages for all three schema types:

```bash
curl -s http://localhost:8080/product/acreline/ | grep -o '"@type":"[^"]*"' | sort | uniq
```

Expected on each product: `FAQPage`, `SoftwareApplication`, `Product` (WC native).
Expected on /shop/: `CollectionPage`, `FAQPage`.

---

## 4. PHP lint + pint

```bash
vendor/bin/pint --test
```

Should pass cleanly. If it fails, run `vendor/bin/pint` to auto-fix.

---

## 5. Build

```bash
npm run build
```

Should complete without errors in `public/build-next/`.

---

## 6. Rank Math one-shot sync check

On the live site, verify these options are NOT set (they trigger re-sync):

```bash
wp option get mh_synced_seo_analysis_bodies_v4
# Expected: 1 (means sync ran)

wp option get mh_product_catalog_v6
# Expected: 1 (means latest catalog seed ran)
```

If any return empty, the sync will run on next page load. This is fine.

---

## 7. Product catalog freshness

Check `resources/data/product-catalog.json` — each product should have:

- [ ] Current version number (matches GitHub release)
- [ ] Current screenshot paths (check files exist in `resources/images/`)
- [ ] Accurate `compatible` field (e.g. `WordPress 6.5+`)
- [ ] No `for_sale: false` on products that should be listed

---

## 8. Internal link check

Each product page should link from at least:

- [ ] About page (sidebar or body)
- [ ] Now page
- [ ] At least one blog post

Check with:
```bash
rg "acreline\|tocflow" resources/views --include="*.blade.php" -l
```

---

## 9. CHANGELOG.md + FEATURES.md freshness

After any notable change, update both. Check that recent changelog version is consistent with what's deployed:

```bash
head -5 CHANGELOG.md
```

---

## 10. Site health checklist

| Check | Command or location |
|---|---|
| No 500 errors | `curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/shop/` |
| WC shop active | `wp eval 'echo wc_get_page_id("shop");'` |
| Theme activated | `wp theme status matthummel` |
| Rewrite rules fresh | `wp rewrite flush` |

---

## Findings log format

When presenting findings, use this format:

```
## Site review findings — [date]

### Fixed in-place
- Fixed: stale /projects/ link in template-services.blade.php

### Action items for Matt
1. [High] Set Rank Math focus keyphrase on /shop/acreline/ to "WordPress real estate agency theme"
2. [Medium] Add a featured image to the TOCflow product (missing, hurts score)
3. [Low] Blog post "Why I built Acreline" is drafted in docs/posts/ but not published
```
