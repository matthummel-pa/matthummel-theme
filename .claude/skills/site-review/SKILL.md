---
name: site-review
description: "Site review of the staging site: SEO, content and voice, schema, shop pages, accessibility, forms, and repo checks, ending in a numbered findings list. Use when Matt asks for a site review, a staging review, an SEO audit, or a check before publishing staging to live."
---

# Site review (staging)

Review the **staging** site: `https://matthummel-com-623249.hostingersite.com/` ("hummel-dev"). Never log in to, install on, or edit `matthummel.com`. Matt publishes staging to live from hPanel. Reading production's public pages is fine when you need to compare.

Report facts you checked. Mark anything you could not verify as unverified. Do not invent numbers, scores, or proof. Rules to apply: `.claude/rules/content.md`, `portfolio-seo-playbook.md`, `seo-ai-search.md`, `product-pages` skill, and the playbook rules for SEO, a11y and marketing copy.

## How to look (read this first)

- **Crawl one page at a time with a 1.5 s gap.** A parallel crawl gets 429, 500, then 403 block pages from Hostinger. If it blocks you, stop and wait.
- Fetch from a logged-in admin tab with `fetch()` in the page, started in the background and stored on `window`, then poll. Long `javascript_tool` calls detach.
- WebFetch text includes hidden elements. Judge the form messages and tabs from the live DOM, not from that text.
- Staging `robots.txt` blocks Googlebot on purpose. Note it, do not "fix" it, and make sure it never carries to production.
- LiteSpeed is not purged by the install. A logged-out visitor can see cached HTML. After a theme install, ask Matt to purge it in hPanel (or say it is not purged).
- Resize the viewport with a same-origin 390px iframe. `resize_window` does not change the real viewport.

## 1. Repo checks (only when code changed)

```bash
vendor/bin/pint --test
python3 .github/scripts/wp-review
npm run build        # when CSS, JS, or Blade changed
```

Stale references (expect no hits outside CHANGELOG and docs):

```bash
rg "WooCommerce|wc_get|/product/|/cart/|/checkout/" app resources --glob '*.php' --glob '*.blade.php'
rg "http://matthummel" app resources
```

WooCommerce is removed. Checkout is Freemius. A hit is either dead code or a legacy redirect. Say which.

## 2. Pages and indexing

Check these URLs on staging. Each returns 200 (or the redirect noted), has one `h1`, a `<title>` under 60 characters, a meta description under 155, a canonical, and OG tags.

| URL | Notes |
| --- | --- |
| `/` | Home |
| `/shop/` | Listing and compare table |
| `/themes/acreline/`, `/themes/walkridge/`, `/themes/cobbleandcandle/` | Theme product pages (check the slugs in `resources/data/product-catalog.json`) |
| `/plugins/tocguide/` | Plugin product page. A product under the wrong prefix 301s to its own |
| `/about/`, `/services/`, `/work/`, `/journal/` | `/about/#hire` is the hiring section |
| `/start/`, `/start-here/`, `/contact/`, `/get-updates/` | `/start/` is the project brief form, `/start-here/` is the newcomer guide. Keep both |
| `/hire/` | Redirects to `/about/#hire` on purpose (`app/filters.php`). Do not recreate the page |

- Rank Math owns titles and meta. No second `<title>` or canonical in Blade.
- Two pages chasing one keyphrase is a finding (a blog post against a product page is the usual case). Look at the focus keyword in Rank Math.

## 3. Schema

Read the JSON-LD on the rendered page. Do not add types the page does not need.

- Product pages: `SoftwareApplication` and `FAQPage`. Note when `SoftwareApplication` has no `Offer` (expected while a theme is Coming soon).
- Shop: `CollectionPage` or `ItemList`, plus `FAQPage` if the FAQ is on the page.
- Posts: Rank Math may already output `Article`. Add one only when Matt asks (`seo-ai-search-blog.md`).
- No `AggregateRating` or review markup without real reviews.

## 4. Copy and voice

On pages and posts:

- First person: "I" and "my". No "we", "us", "our", and no "the developer" or "the team" for Matt.
- Glossary: shops, agencies, developers, learners.
- One primary call to action, "Say hello", to `/contact/`. Product pages use their own buy or demo actions.
- No fake proof: no invented clients, testimonials, metrics, or ratings.
- No price, rate, or discount stated on Matt's behalf except the catalog price already published.
- No government or military detail in new marketing copy.
- Coming-soon products: the page must not promise an instant download or a purchase that is not live yet.
- Copy is block content edited on staging. A theme PR touches copy only for code defaults, seeds, and UI chrome. Note whether each fix belongs in a PR or an edit in the block editor.

## 5. Shop and catalog

- `resources/data/product-catalog.json` is seeded into the database once per version (`mh_apply_product_catalog_vN()` in `app/concept-pages.php`, option `mh_product_catalog_vN`). A JSON edit alone does not change existing product pages. If staging shows old catalog text, a new one-time refresh is missing.
- Per product: version matches the latest GitHub release, screenshot files exist under `resources/images/products/`, `compatible` is accurate, `coming_soon` and `for_sale` match what Matt intends to sell.
- Shop copy defaults live in `app/shop.php`. A value saved in Page content (theme) overrides a default, so compare the rendered text with the code.
- Freemius links and demos open and return 200. Do not buy anything.

## 6. Forms and the newsletter

- `/contact/` and `/start/` render, label every field, and validate. Do not submit a form unless Matt asks. A sent form emails Matt.
- Newsletter forms come from the Hostinger Reach plugin. The "not available" messages in its markup are hidden templates, not visible errors. Check that the form renders and the button is enabled. An end-to-end signup is unverified unless Matt did one.

## 7. Accessibility and layout

- One `h1`, headings in order, no skipped levels.
- Images have `alt`, `width` and `height` (or an aspect ratio).
- Fields have labels. Links and buttons have a visible focus state.
- Desktop and a 390px width: nothing overflows sideways, the mobile menu opens, tap targets are usable.
- Dark mode (`html.mh-dark`) keeps text readable.

## 8. Internal links

- Every product page is reachable from the shop, and at least one blog post links to it.
- A post about a product links to the product page, and the product page links back (Acreline has a "Read the full story" link through `story_url`).
- Posts have related posts or a next step.
- No link to a retired URL (`/projects/`, old Acreline paths) and no dead link.

## 9. Docs freshness

- `CHANGELOG.md` top entry, `style.css` Version and `readme.txt` Stable tag agree with each other and with the installed version shown at Appearance, Update Theme on staging.
- `docs/FEATURES.md` mentions any user-visible feature that shipped.

## Findings list

Lead with the answer: what is wrong and what matters most. Then list the findings, most important first. Mark each one Fixed, Needs Matt, or Unverified, and say whether it is a PR or a staging edit.

```
## Site review, [date], staging

### Fixed in this review
- [what changed, and where]

### Needs Matt
1. [High] [finding] ([URL or file]). Fix: [PR or staging edit].
2. [Medium] ...

### Unverified
- [what could not be checked, and why]
```

Do not edit production, delete posts or categories, or spend money. Do not purge caches you cannot reach. Say so.
