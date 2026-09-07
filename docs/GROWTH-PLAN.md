# Growth Plan — matthummel.com

A practical roadmap for shipping, writing, marketing, and customer outreach. Updated as work moves. No fake metrics, no ad spend until organic traction proves the message works.

_Last updated: September 2026._

---

## Site goals in priority order

1. **Sell** — WordPress themes, plugins, and web apps through the shop
2. **Share** — open-source code and technical writing through GitHub + journal
3. **Stay hireable** — keep the portfolio clean for employers and agency leads
4. **Build an audience** — followers who come back to buy or hire

---

## Current catalog (September 2026)

| Product | Type | Price | Primary keyword | Status |
|---|---|---|---|---|
| Acreline | WordPress real estate theme | $79 | WordPress real estate agency theme | Live |
| TOCflow | WordPress TOC plugin | Free | WordPress table of contents plugin | Live |

---

## Dev roadmap

### Next product to build: WordPress theme #2

**Criteria for the next product:**
- Serves a narrow audience with money (not just hobbyists)
- Has at least 3 Gutenberg blocks that matter to that audience
- Can demo in under 5 minutes
- Is different enough from Acreline to capture a new keyword cluster

**Candidate niches:**
- Food/restaurant (local business, high search volume)
- Yoga/wellness studio (growing, underserved by GPL themes)
- Photography portfolio (high developer adoption, good keyword)
- Plumbing/HVAC local service (evergreen local SEO)

**To add a product:**
Read `.cursor/skills/add-product/add-product.md` — full flow from JSON to deploy.

### Next plugin to build

**TOCflow v2 ideas:**
- Collapsible table of contents (most-requested Gutenberg TOC feature)
- Floating sidebar TOC (for long-form docs/posts)
- Progress indicator integration

**New plugin idea:**
- WordPress breadcrumb block — server-rendered, no jQuery, schema.org BreadcrumbList. Tight niche with clear SEO value.

### Site features still in backlog

- [ ] Add a `related_products` key to `product-catalog.json` and render a related products strip on single product pages
- [ ] Add a product wishlist/notify email capture (free product, collect email for updates)
- [ ] Swap TOCflow $0 checkout for an email capture form (MailPoet or similar)
- [ ] Add a testimonials/proof section once real buyers provide feedback
- [ ] Breadcrumb schema on all pages (not just products)

---

## Blog / content calendar

Target: **2 posts per month**. Write one technical post (developer audience) and one conceptual post (shop/agency audience) per month.

### Posts queued (write next)

#### Month 1 — Acreline launch

| Post | Target keyword | Audience | Draft status |
|---|---|---|---|
| Why I built a WordPress theme for rural real estate agencies | WordPress real estate theme for farms | Shops + agencies | Not started |
| 21 Gutenberg blocks in a real estate theme — no Elementor required | Gutenberg real estate theme | Developers | Not started |

#### Month 2 — TOCflow launch

| Post | Target keyword | Audience |
|---|---|---|
| TOCflow: a free WordPress table of contents plugin that is server-rendered | WordPress table of contents plugin free | All |
| Building a Gutenberg block with a PHP render callback — no React on the front end | Gutenberg block PHP render | Developers |

#### Month 3 — Portfolio/hiring

| Post | Target keyword | Audience |
|---|---|---|
| How I structure a Sage 11 WordPress theme build | Sage 11 WordPress theme | Developers |
| WordPress vs. page builders for small business sites in 2026 | WordPress vs page builders | Shops + agencies |

#### Month 4 — Comparison posts (SEO compounders)

| Post | Target keyword | Audience |
|---|---|---|
| WordPress real estate agency theme: Acreline vs. Houzez vs. RealHomes | WordPress real estate theme comparison | Shops + agencies |
| WordPress TOC plugins compared: TOCflow vs. Easy Table of Contents vs. LuckyWP | best WordPress TOC plugin | All |

### Content rules (always follow)

- Write in `docs/posts/YYYY-MM-DD-slug.html` first. Paste into WP after review.
- First paragraph must answer the headline directly (BLUF).
- Every post links to at least one product page.
- Cross-post to DEV.to the same day (set canonical URL).
- Use `.cursor/skills/new-blog-post/new-blog-post.md` for the full workflow.

---

## Marketing cadence

### Weekly (15 minutes)

- [ ] Check WooCommerce orders and email any buyers personally if they are the first or tenth buyer of a product
- [ ] Reply to any GitHub issues on product repos (Acreline, TOCflow)
- [ ] Post one short update on Bluesky: a code snippet, a WIP screenshot, or a shipping note

### Monthly (2 hours)

- [ ] Publish 2 blog posts (from the content calendar above)
- [ ] Cross-post both to DEV.to
- [ ] Check Rank Math scores on both product pages (target 80+)
- [ ] Run `.cursor/skills/site-review/site-review.md` checklist
- [ ] Update `docs/MARKETING.md` if strategy changes
- [ ] Check GitHub release download counts (Acreline, TOCflow)

### Quarterly (half day)

- [ ] Refresh `product-catalog.json` with any version bumps or copy improvements
- [ ] Review CHANGELOG.md and update FEATURES.md
- [ ] Check for broken demo links (acreline.matthummel.com)
- [ ] Submit/resubmit one product to a new channel (WordPress.org, ThemeForest, r/WordPress)
- [ ] Review the job listing on /hire/ — keep availability and stack current

---

## Distribution channels

### Organic (zero cost)

| Channel | Action | Products |
|---|---|---|
| **matthummel.com/journal** | 2 posts/month, focus keywords, internal links to products | Both |
| **DEV.to** | Cross-post same day, canonical URL to matthummel.com | Both |
| **GitHub README** | First line of each repo README = primary keyword phrase | Both |
| **GitHub Releases** | Tag clean releases, write good release notes | Both |
| **r/WordPress** | "Show HN"-style post per product launch, genuine participation | Both |
| **WordPress Slack** | #showcase + #general when a plugin is released | TOCflow |
| **Bluesky** | Weekly update threads, demo screenshots | Both |
| **LinkedIn** | Post after each blog post (3-sentence update + link) | Both |

### Marketplace submissions (medium effort, compounding)

| Channel | Status | Notes |
|---|---|---|
| **WordPress.org plugins** | Not submitted | TOCflow is GPLv2, meets standards. Submit when v1.1 is stable. |
| **WordPress.org themes** | Not submitted | Requires review. Good for SEO backlink + trust signal. |
| **ThemeForest** | Not submitted | Acreline has marketplace docs ready (`docs/MARKETPLACE.md`). Priority channel for paid revenue. |
| **GitHub Marketplace** | n/a | No Actions to list |

---

## Customer outreach

### Inbound (primary strategy for now)

The site is built for inbound. Make the inbound work before adding outbound:

1. Rank Math scores 80+ on both products ← current focus
2. Blog posts targeting long-tail keywords ← next 60 days
3. WordPress.org / ThemeForest listing ← 90-day goal

### Outbound (start only after first 5 organic sales or inquiries)

**Agency cold outreach — for Acreline:**

Target: WordPress agencies that build real estate sites. Find them via:
- Agency directories (Clutch, Codeable partner list, WPEngine agency partners)
- Search "WordPress real estate agency theme" + LinkedIn

Email template:
```
Subject: GPL real estate theme you can white-label

Hi [Name],

I build WordPress themes for sale at matthummel.com. Acreline is a GPL real estate agency theme I built for rural offices — 21 Gutenberg blocks, Sage 11, no Elementor.

If you build sites for real estate clients, it might save a project or two. Live demo at acreline.matthummel.com. Product page with full details at matthummel.com/shop/acreline.

$79 one-time, unlimited installs for agencies.

Matt
```

**Developer / learner outreach — for TOCflow:**

- Reply to relevant WordPress.org support threads where people ask about TOC plugins (helpful, not spammy)
- Post in WordPress Facebook groups when a TOC question comes up

---

## Hiring / employer strategy

The site serves employers too. These pages must stay accurate:

- `/hire/` — availability, resume timeline, Power Platform mention
- `/portfolio/` — GitHub repos, featured work
- `/code/` — GitHub stats, contribution grid
- Blog — technical posts show craft

**Actions to keep this channel healthy:**
- [ ] Pin 5 repos on GitHub: pressroot, matthummel-theme, tocflow, ridgesandvalleys, keepary (already in `app/Github.php`)
- [ ] Keep `/hire/` availability status current (edit in wp-admin Page fields)
- [ ] Add one new technical blog post per quarter that specifically demonstrates WordPress architecture or Power Platform work
- [ ] Review the recruiter glance on the home page every 3 months for accuracy

---

## Metrics to track (no analytics bundle)

Check these in wp-admin monthly:

| Metric | Where |
|---|---|
| WooCommerce order count | WooCommerce → Orders |
| Product Rank Math scores | Edit product → Rank Math panel |
| GitHub repo stars + followers | github.com/matthummel-pa |
| Contact form inquiries mentioning a product | Emails / n8n CRM webhook |
| GitHub release download count | GitHub Releases |

Do not install Google Analytics, PostHog, or Mixpanel unless there is a specific decision to make that requires that data.

---

## Skills and rules quick reference

| Task | Use |
|---|---|
| Add a new product | `.cursor/skills/add-product/add-product.md` |
| Write a blog post | `.cursor/skills/new-blog-post/new-blog-post.md` |
| Monthly site review | `.cursor/skills/site-review/site-review.md` |
| Sync a product from its repo | `.cursor/rules/product-theme-sync.mdc` |
| Edit WooCommerce product page standards | `.cursor/rules/woocommerce-product.mdc` |
| Voice and grammar | `.cursor/rules/master-content-writer.mdc` |
| SEO and keyword strategy | `.cursor/rules/portfolio-seo-playbook.mdc` |
| Deploy | `.github/workflows/deploy.yml`, `docs/INSTALL.md` |
