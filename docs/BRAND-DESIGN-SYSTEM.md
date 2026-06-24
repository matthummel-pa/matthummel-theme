# Matt Hummel — Brand & Design System

**North star (Webflow 2026 trend #3 — Minimalism in copy):** *Say less, mean more.*
When AI makes endless text cheap, brevity is the bold move. Whitespace and bold
type carry the message. Every word is load-bearing. This is also an accessibility
and performance win: less to read, faster to load, easier to scan.

---

## 1. Principles

1. **Cut everything that isn't load-bearing.** One idea per section. If a sentence can go, it goes.
2. **Bold headings do the talking.** Big, confident headings; short supporting lines.
3. **Whitespace is a feature.** Generous spacing signals calm and craft.
4. **Scannable by default.** Front-load the point; let people dive deeper only if they want.
5. **Accessible first.** WCAG 2.1 AA minimum, keyboard-friendly, real focus states.
6. **Fast & lean.** Few plugins, native blocks, minimal CSS/JS. Speed is UX.

---

## 2. Typography — minimalist fonts, bold headings

| Role | Font | Weight | Notes |
|---|---|---|---|
| Display / headings | **Space Grotesk** | 600–700 | Geometric, minimal, distinctive. Tight line-height (1.05–1.15). |
| Body / UI | **Inter** | 400 / 500 | Workhorse, highly legible at all sizes. |
| Mono / code | **JetBrains Mono** | 400 | Dev credibility; code blocks only. |

All three are free (Google Fonts), variable, and self-hostable (better speed + privacy).

**Fluid, responsive type scale** (clamp = mobile→desktop, no media queries needed):

| Token | clamp() | Use |
|---|---|---|
| display | clamp(2.5rem, 6vw, 4.5rem) | hero headline |
| h1 | clamp(2rem, 4.5vw, 3.25rem) | page title |
| h2 | clamp(1.6rem, 3vw, 2.25rem) | section |
| h3 | clamp(1.25rem, 2vw, 1.5rem) | subsection |
| body | clamp(1rem, 1.1vw, 1.125rem) | paragraphs |
| small | 0.875rem | meta / captions |

**Readability rules:** body line-height **1.6–1.75**; measure (line length) **60–75ch**;
headings line-height **1.05–1.2**; never justify; never all-caps long text.

---

## 3. Color system — accessible & interaction-optimized

Warm editorial base + clear interaction colors. Every text pairing meets **WCAG AA (4.5:1)**;
UI/borders meet **3:1**.

### Neutrals (foundation)
| Token | Hex | On Paper | Use |
|---|---|---|---|
| Paper (bg) | `#FBFAF7` | — | page background |
| Surface | `#FFFFFF` | — | cards / panels |
| Ink | `#17191E` | 15.6:1 (AAA) | headings, primary text |
| Body | `#2B2F36` | 11.1:1 (AAA) | paragraphs |
| Muted | `#5C636C` | 5.4:1 (AA) | meta, captions, labels |
| Line | `#E6E2D9` | — | borders, dividers |

### Brand + action
| Token | Hex | Pairing | Use |
|---|---|---|---|
| Green (brand/action) | `#2F6B4E` | white text 5.0:1 (AA) | primary buttons, brand |
| Green-hover | `#255840` | white | button hover/active |
| Green-tint | `#EAF1EC` | ink text | subtle button bg, badges |

### Interactive (clarity = usability)
| Token | Hex | On Paper | Use |
|---|---|---|---|
| Link | `#1357C6` | 6.3:1 (AA) | inline links — blue reads as "clickable" |
| Focus ring | `#1357C6` | — | 2px outline + 2px offset on every focusable element |

### Semantic (always pair color with an icon/label — never color alone)
| Token | Hex | Use |
|---|---|---|
| Success | `#1E7A47` | confirmations |
| Warning | `#8A5A00` | cautions |
| Error | `#C0362C` | errors |
| Info | `#1357C6` | notices |

### Accent (use sparingly for energy / dynamic text)
| Token | Hex | Use |
|---|---|---|
| Highlight | `#F2C94C` | marker underline on a key word, never as text on light |

**Interaction-state recipe (every button/link/input):**
default → **hover** (darken ~8%) → **active** (darken ~14%) → **focus** (visible ring) →
**disabled** (Muted + `cursor:not-allowed`). Hover/active are *cues*; focus is *required* for keyboard users.

> Why this works for interaction: blue links are the most universally understood affordance;
> a single saturated action color keeps choices obvious; visible focus + 4.5:1 contrast keep it
> usable for everyone, including keyboard and low-vision users.

---

## 4. Spacing, layout & responsiveness (mobile-first)

- **Spacing scale (rem):** 0.25 · 0.5 · 0.75 · 1 · 1.5 · 2 · 3 · 4 · 6 · 8. Use a few, consistently.
- **Container:** content max-width **720px** (reading) / **1100px** (wide sections); side gutter `clamp(1rem, 5vw, 2rem)`.
- **Mobile-first:** design the 360px view first; enhance up. Breakpoints: **640 / 768 / 1024 / 1280**.
- **Grids:** `repeat(auto-fit, minmax(280px, 1fr))` so cards reflow with no media queries.
- **Touch targets:** min **44×44px**; ≥8px between tappable items.
- **Images:** responsive `srcset`, `loading="lazy"`, `width/height` set (no layout shift).
- **No horizontal scroll** at any width; test 320px → 1440px.

---

## 5. Accessibility standards (baked in)

- One `<h1>` per page; headings never skip levels.
- Color contrast AA (above); **never** convey meaning by color alone.
- Visible focus on **every** interactive element.
- All images have `alt` (decorative = `alt=""`); icons are `aria-hidden` when paired with text.
- Forms: real `<label>`s, error text tied via `aria-describedby`.
- Respect `prefers-reduced-motion` (disable non-essential animation).
- Semantic landmarks: `header`, `nav`, `main`, `footer`; "Skip to content" link.

---

## 6. Voice & copy (the trend #3 engine)

- **Headlines:** 3–7 words. State the value, not the feature. ("Plugins that don't fight you.")
- **Subheads:** one line, ≤ 15 words.
- **Body:** short paragraphs (1–3 sentences). Lead with the takeaway.
- **Buttons:** verbs, 1–3 words ("Read it", "See the code", "Get in touch").
- **Cut filler:** "in order to" → "to"; drop "genuinely / really / very"; avoid hedging.
- **Front-load (TL;DR):** every long page opens with a 1–2 line summary.
- Tone: clear, confident, friendly, plain-spoken. No jargon walls.

---

## 7. Plugin philosophy (keep it lean)

Build in the **theme**, not in plugins. Native Gutenberg blocks + theme code instead of a page builder.

**Keep:** Rank Math (SEO) · the importer (one-time) · SQLite drop-in (local dev only).
**Avoid:** Kadence Blocks / Elementor / page builders · "swiss-army" plugins · anything you can do in ~30 lines of theme code.
**Rule of thumb:** a plugin earns its place only if it does something you genuinely can't (or shouldn't) build, and it's well-maintained.

---

## 8. Bespoke block library (build individually — no mega page-block)

Small, composable blocks. Each: minimal copy slots, bold heading, accessible markup, responsive by default.

**Foundation**
- **Hero** — eyebrow · bold headline · 1 subline · 1–2 buttons. Lots of whitespace.
- **Lead / TL;DR** — 1–2 line summary that opens a page (trend #4 friendly).
- **Section heading** — bold heading + optional kicker, centered or left.

**Content**
- **Feature grid** — 2–4 cards (icon optional, title, one line).
- **Stat strip** — 3–4 metrics (big number + label).
- **Pull-quote / Callout** — emphasized statement (light dynamic-text treatment).
- **Step list / Process** — numbered, scannable.
- **Code block** — dark, JetBrains Mono, copy button.
- **FAQ / Accordion** — keyboard-accessible disclosure.

**Project / dev-specific**
- **Project card** — thumbnail · title · one-line · tags.
- **GitHub repo card** — live stats + README intro (already powered by the theme's GitHub engine).
- **Tech list** — labelled list (what + why), constrained width.

**Conversion / utility**
- **CTA band** — heading + 1 button (cream card, not loud gradient).
- **Newsletter signup** — email + button, single line of trust copy.
- **Post meta** — date · reading time · category.

Each block ships with sensible defaults so a post is fast to assemble, and `block.json`
attributes so they're editable in the inserter and (later) controllable from theme options.
