# Marketing Plan — Acreline & TOCflow

This is the practical marketing plan for the two products currently in the catalog at `resources/data/product-catalog.json`. It maps goals, audiences, channels, and execution steps — no ad spend required until organic traction proves the messaging works.

---

## Products

| Product | Type | Price | Audience | Primary search intent |
|---|---|---|---|---|
| **Acreline** | WordPress real estate agency theme | $79 | Real estate agencies, land offices, farm brokerages | "WordPress real estate agency theme" |
| **TOCflow** | Free WordPress TOC plugin | Free | WordPress bloggers, docs sites, agencies | "WordPress table of contents plugin" |

---

## Acreline

### Positioning

**The only WordPress real estate agency theme built for rural offices.** Not a luxury suburban realtor kit. Not an IDX-first portal. A full-stack Sage 11 theme agencies install in WordPress, brand in the Customizer, and edit in the block editor — without a developer on retainer.

Primary keyword: `WordPress real estate agency theme`
Secondary: `farm and land WordPress theme`, `rural realtor WordPress`, `real estate agency theme GPL`, `white-label real estate WordPress`

### Audiences

1. **Land offices and farm brokerages** — sole prop agents and small offices selling acreage, parcels, and farms. They need a website that matches how rural buyers search (acreage, township, lot type) not how suburban portals work.
2. **Real estate agencies going rural** — existing offices expanding into land or farm sales who do not want a second codebase.
3. **WordPress agencies** — shops that build sites for real estate clients and need a GPL base they can white-label, install for a client, and customize without ACF or Elementor.
4. **Hiring managers** — the product page demonstrates Gutenberg block architecture, Sage, and Tailwind v4. This is the technical proof of work.

### Content marketing

#### Blog posts (matthummel.com/journal)
These drive organic traffic before any ad spend:

1. **"Why I built Acreline — a WordPress theme for rural real estate agencies"** — first-person origin story. Who it is for, what was broken about existing themes, how the block architecture works. Links to the demo and product page. Targets `WordPress real estate theme for farms`.
2. **"21 Gutenberg blocks in a real estate theme — no Elementor required"** — technical deep-dive. Shows the ServerSideRender approach, the Block Generator tool, and why native blocks beat a page builder for agency sites. Targets `Gutenberg real estate theme`.
3. **"How to white-label Acreline for a client real estate agency"** — step-by-step for WordPress agencies. Child theme, Customizer branding, GPL resale rights. Targets `white-label real estate WordPress theme`.
4. **"Acreage listing filters in WordPress without a plugin"** — snippet-style post on how the query-arg filter system works. Links to the GitHub repo and the product page. Targets developers.
5. **"WordPress real estate agency theme: Acreline vs. Houzez vs. RealHomes"** — comparison post (factual, no fake claims). Positions Acreline's rural focus and Gutenberg-first architecture against luxury/IDX-first competitors.

#### Demo site copy (acreline.matthummel.com)
Make the demo site do more selling work:
- Add a visible "Buy Acreline — $79" button in the demo header linking to the product page.
- Add a brief "About this theme" footer strip with the tagline and product page link.
- Make the demo listing inventory accurate to what a real rural office would show (farm parcels, not suburban condos).

#### Product page (matthummel.com/shop/acreline — or `/projects/acreline/`)
The product page now ships with:
- Full benefits list (8 points)
- Challenge / approach narrative
- 7-question FAQ with "Is Acreline a real estate agency theme?" as the first question (targets the primary keyword)
- SoftwareApplication + FAQPage JSON-LD schema (Rank Math detects both)
- WooCommerce short_description = blurb (feeds Rank Math product tab)
- WooCommerce long_description = full rich HTML (challenge, approach, benefits, deliverables, audience, architecture, handoff)

Rank Math target score: 80+. Key actions to reach it on the product page:
1. Set the focus keyphrase in Rank Math to "WordPress real estate agency theme".
2. Ensure the focus keyphrase appears in: H1, first paragraph, at least one H2, meta description, and alt text of the featured image.
3. Add an internal link from the About page and from at least one blog post.
4. Set meta description to the `blurb` field (now auto-filtered via `mh_rank_math_product_description`).

### Distribution

| Channel | Action | Effort | Timeline |
|---|---|---|---|
| **WordPress.org** | Submit to the theme directory (GPLv2-licensed, no premium upsell) | High — requires review | Month 2–3 |
| **ThemeForest** | Submit the ThemeForest pack (already has marketplace docs) | Medium — already partially prepared | Month 1–2 |
| **GitHub** | Tag a 1.2.3 release, update README with "real estate agency theme" in the first sentence | Low | Week 1 |
| **r/Wordpress** | Post a "Show HN"-style thread: "I built a WordPress theme for rural real estate agencies" | Low | Week 2 |
| **DEV.to** | Cross-post the blog posts | Low | Ongoing |
| **Bluesky / WordPress community** | Short thread + demo screenshot when the post goes live | Low | With each blog post |
| **Agency cold outreach** | Email 5 WordPress agencies that build real estate sites: "GPL base you can white-label" | Medium | Month 1 |

### Pricing

$79 one-time is a reasonable launch price for a niche theme with this feature set. Do not discount below $49 — it signals low quality. Consider:
- **Launch price**: $79 (current)
- **Agency license**: $149 for unlimited-site installs (add when ThemeForest approves or when first agency inquiry arrives)

---

## TOCflow

### Positioning

**The free WordPress TOC plugin that works the first time.** Server-rendered, accessible, SEO-friendly. Insert the block; the outline builds itself. No configuration, no React runtime, no freemium lock.

Primary keyword: `WordPress table of contents plugin free`
Secondary: `Gutenberg TOC block`, `accessible table of contents WordPress`, `server-rendered TOC plugin`

### Audiences

1. **WordPress bloggers and content publishers** — long posts need jump links. They want it to work without a settings screen.
2. **Docs sites and agencies** — need accessible, SEO-compatible TOC without adding a plugin with 50 unused features.
3. **Developers** — evaluating the codebase as a reference for a clean Gutenberg block with PHP render.
4. **Lead-in to hiring** — a free, useful plugin that puts my name in front of developers and agencies who might hire me.

### Content marketing

#### Blog posts
1. **"TOCflow: a free WordPress table of contents plugin that is server-rendered"** — announce and explain the block. Why server-rendered HTML matters for SEO and screen readers. Links to GitHub and the product page.
2. **"Building a Gutenberg block with a PHP render callback — no React on the front end"** — technical post showing how TOCflow's block works. Targets developers. Links to the repo.
3. **"WordPress TOC plugins compared: TOCflow vs. Easy Table of Contents vs. LuckyWP"** — comparison on accuracy, render method, accessibility. Factual only.

#### Product page (matthummel.com/shop/tocflow)
- FAQ schema with "Is TOCflow really free?" as the first question (targets price-intent queries).
- SoftwareApplication schema with price: 0.
- Rich description in WooCommerce covering: what it does, how it works (server-rendered), what is included, who it is for.

Rank Math target score: 75+. Focus keyphrase: "WordPress table of contents plugin".

### Distribution

| Channel | Action | Effort |
|---|---|---|
| **WordPress.org plugins** | Submit for free listing — already GPLv2, meets standards | Medium |
| **GitHub Releases** | Tag 1.0.2, ensure README first line says "server-rendered WordPress TOC plugin" | Low |
| **r/Wordpress, r/webdev** | Post announce thread linking to docs site | Low |
| **DEV.to** | Cross-post the technical blog post | Low |

### Pricing

Free forever. $0 checkout captures email for update notices (optional). The plugin is a lead magnet — it puts Matt Hummel in front of agencies who might hire for a custom block or theme build.

---

## Shared tactics

### Internal linking
Product pages link to each other (related products). Blog posts about Acreline link to TOCflow and vice versa where relevant. Every post links to at least one product page.

### Rank Math setup checklist (per product)
- [ ] Focus keyphrase set in Rank Math product screen
- [ ] Keyphrase in H1 (product title), first paragraph, at least one H2
- [ ] Meta description set to catalog `blurb` (auto-filtered via `mh_rank_math_product_description`)
- [ ] FAQPage JSON-LD on product page (now shipped via `single-product.blade.php`)
- [ ] SoftwareApplication JSON-LD on product page (now shipped)
- [ ] Featured image with descriptive alt text
- [ ] At least 2 internal links from other pages

### Measurement
No Google Analytics client bundle (per `agency-core.mdc`). Track:
- Rank Math SEO score in wp-admin (target 80+ per product)
- GitHub release download counts
- WooCommerce order count
- Contact form inquiries mentioning the product by name

---

_Last updated: September 2026. Update this file when a new product enters the catalog, when a major channel strategy changes, or when pricing changes._
