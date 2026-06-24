# SOPs — Editorial & Development

Two repeatable playbooks: how a post gets published, and how the theme gets versioned.

---

## A. Editorial SOP (idea → published → promoted)

| # | Stage | Done when… |
|---|---|---|
| 1 | **Capture** | Idea added to the backlog with a working title + which pillar. |
| 2 | **Outline** | Focus keyword chosen; category set; 3 key takeaways listed; TL;DR drafted. |
| 3 | **Draft** | Written minimalist (trend #3): bold headings, short paragraphs, front-loaded. |
| 4 | **Self-edit** | Filler cut; reads in one pass; scannable; code tested. |
| 5 | **SEO pass** | Rank Math green (≥80): title ≤60, meta ~155, slug, keyword placed, 3+ internal links, featured image 1200×630 + alt. |
| 6 | **A11y pass** | One H1, logical headings, alt text, contrast AA, links make sense out of context. |
| 7 | **Mobile pass** | Looks right 320→1440px; no horizontal scroll; tap targets ok. |
| 8 | **Schedule/Publish** | Date set; preview checked on phone. |
| 9 | **Promote** | Distribution checklist below. |
| 10 | **Maintain** | Re-check in 60–90 days; refresh stats/links; update "Last updated". |

**Definition of done (pre-publish checklist):**
- [ ] One idea, clearly stated in the first two lines (TL;DR)
- [ ] Headings scannable; no wall-of-text sections
- [ ] Rank Math ≥ 80, focus keyword placed, meta written
- [ ] Featured image (1200×630) + alt; in-body images have alt + lazy-load
- [ ] 3+ internal links, descriptive anchors
- [ ] Mobile preview clean; AA contrast; visible focus
- [ ] Proofread out loud once

**Distribution checklist** (reuse your existing channels):
- [ ] LinkedIn post (+ carousel for tutorials)
- [ ] Dev.to cross-post (canonical URL → your site)
- [ ] Reddit (relevant subreddit, value-first, not spammy)
- [ ] Bluesky + Facebook
- [ ] Add to the next newsletter

---

## B. Starter content calendar (12 weeks, 1/week, rotating pillars)

| Wk | Pillar | Working title | Focus keyword |
|---|---|---|---|
| 1 | WordPress | Local WordPress dev with WP-CLI + SQLite (no Docker, no app) | wp-cli local dev |
| 2 | Web Dev | Fluid type with CSS clamp() — kill the media queries | css clamp typography |
| 3 | Power Platform | 5 Power Automate flows that save an hour a week | power automate flows |
| 4 | WordPress | Build your first Gutenberg block with block.json | gutenberg block tutorial |
| 5 | Web Dev | Accessible focus states that actually look good | accessible focus styles |
| 6 | Power Platform | Canvas vs model-driven: how to choose | canvas vs model-driven |
| 7 | WordPress | Live GitHub repo data in WordPress — no plugin | wordpress github api |
| 8 | Web Dev | Core Web Vitals: quick wins that move the needle | core web vitals quick wins |
| 9 | Power Platform | Dataverse relationships, explained simply | dataverse relationships |
| 10 | WordPress | Why I moved from Kadence to Sage (Roots) | kadence to sage |
| 11 | Web Dev | Responsive images with srcset, the simple way | responsive images srcset |
| 12 | Power Platform | Power Fx patterns for cleaner apps | power fx patterns |

Cadence guidance: 1/week is sustainable. Batch outlines monthly; keep a 3-post buffer.

**Where to run the calendar (minimal-tool):** a simple `Backlog → Drafting → Review →
Scheduled → Published` board. Options, low-overhead first: a Notion/Trello board, a Google
Sheet, or the free **Editorial Calendar**/**PublishPress** plugin only if you want it inside WP.

---

## C. Development / versioning SOP (GitHub)

**Repo:** the theme is its own repo — `wp-content/themes/matthummel` → `github.com/matthummel-pa/matthummel-theme`.

**Never commit build output / dependencies** (Sage's `.gitignore` already covers it):
`node_modules/`, `vendor/`, `public/build/`. These are rebuilt on deploy.

**Branching & commits**
- `main` = always deployable. Work on short-lived branches: `feat/…`, `fix/…`, `chore/…`.
- **Conventional Commits:** `feat: add stat-strip block`, `fix: focus ring on cards`, `docs: brand system`.
- Open a PR → self-review the diff → merge → delete branch.

**Releases**
- Tag meaningful states: `git tag -a v1.1.0 -m "Block library v1"` → `git push --tags`.
- Bump `style.css` `Version:` to match.

**First-time setup (run in the theme folder):**
```bash
cd C:\ClaudeCowork\Projects\DevProjects\matthummel-site\wp-content\themes\matthummel
git init
git add .
git commit -m "chore: initial theme + design system"
gh repo create matthummel-pa/matthummel-theme --public --source=. --remote=origin --push
```

**Day-to-day:**
```bash
git checkout -b feat/hero-block
# …build…
git add . && git commit -m "feat: add hero block"
git push -u origin feat/hero-block   # open PR on GitHub, merge to main
```

**Deploy = build, then ship the built theme:**
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
# upload theme incl. vendor/ + public/build/  (or build on the host)
```

> The brand/design-system + content docs live in the repo too (a `/docs` folder), so the
> standards are versioned alongside the code.

---

## A.1 Social automation (Claude-assisted, review-first)

Generate platform-tailored social copy with the **Claude API** on publish, review, then post.
Minimal-plugin: one small custom plugin/snippet — not a bloated social plugin.

**Flow:** publish → Claude API drafts → review queue (saved to post meta) → 1-click post per platform.

| Platform | API | Mode | Notes |
|---|---|---|---|
| Bluesky | AT Protocol (app password) | auto OK | ≤ 300 chars + link |
| Dev.to | Forem API (api-key) | auto OK | set `canonical_url` back to the post |
| Reddit | OAuth script app | **assist only** | auto-posting risks spam flags / subreddit rules — generate + paste |
| LinkedIn | — | draft only | copy/paste (incl. carousel for tutorials) |

**Keys (you provide; stored in `wp-config`/secure option, never in the repo):** Anthropic API key · Bluesky handle + app password · Dev.to API key · Reddit client id/secret + refresh token.
**Principle:** review-first, never silent auto-post. Mind Claude API cost + platform rate limits/ToS.

---

## D. Build backlog (versioned roadmap)

Planned theme work, in build order. Tracked here, shipped via GitHub (section C), previewed on the local site.

1. **Finish local site** — `wp core install` + SQLite *(in progress)*
2. **Apply design tokens** — type system + accessible color system + fluid scale → `resources/css/app.css`
3. **GitHub versioning** — theme repo `matthummel-pa/matthummel-theme`, `/docs` committed alongside
4. **Category descriptions** set (above) + Rank Math category meta
5. **Page templates**
   - Blog → `index.blade.php` + category archives
   - Projects → `archive-projects.blade.php` (Project Type filter)
   - GitHub Projects → filtered archive (Project Type = "GitHub Project")
6. **Integrated GitHub system** — `app/Github.php` engine → repos archive (auto-list pinned repos + stats), reusable block, 6h cache, no plugin
7. **Bespoke block library** — individual blocks (no mega-block)
8. **Customizer theme options** — Kadence-style (colors, fonts, layout, header/footer)
9. **Social automation** — Claude + Bluesky/Dev.to/Reddit (A.1) — needs API keys
