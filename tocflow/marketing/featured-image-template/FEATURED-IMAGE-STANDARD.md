# Featured Image Standard — matthummel.com projects

The TOCflow featured image is the **standard layout** for every project's
featured image. Keep the layout identical; **swap in each project's own brand
colors** (this is the guideline going forward).

## Canvas
- **Size:** 1200 × 630 px (Open Graph / social ratio)
- **Format:** PNG, exported from the SVG template

## Fixed layout (don't move these)
| Element | Position | Notes |
| --- | --- | --- |
| Background | full bleed | Diagonal gradient, 3 stops (dark → mid → light) in the **project's brand colors** |
| Logo tile | 80, 74 — 112×112, radius 26 | White rounded square holding the project's icon (icon in the brand's mid color) |
| Wordmark | x≈214, baseline 138 | Project name, white, bold ~42–44px |
| Tagline | x≈216, baseline 170 | Uppercase, letter-spaced, light-accent color |
| Eyebrow pill | 80, 230 | Translucent white pill, e.g. "WEB DESIGN PROJECT", "FREE · GPL" |
| Headline | x80, baselines 350 / 404 | Two lines; line 1 white, line 2 light-accent; bold 44px |
| Subline | x82, baseline 462 | Feature list separated by `&#183;` (middle dot) |
| Preview card | 800, 210 — 320×300, radius 16 | White card with a project-specific mini-mockup (UI snippet, browser frame, etc.) |
| Footer URL | x80, baseline 556 | Always `matthummel.com` |

## What changes per project (brand guideline)
1. **Gradient colors** — pull the project's primary brand colors for the 3 gradient stops.
2. **Accent text color** — a light tint of the brand for the second headline line, tagline, and subline.
3. **Icon** — a simple glyph that represents the project, drawn in the brand's mid color.
4. **Wordmark / tagline / pill / headline / subline** — the project's name, category, and value prop.
5. **Preview card** — a small mockup that fits the project (e.g. a UI snippet for a plugin, a browser frame for a website).

## Worked examples
- **TOCflow** (plugin): green brand (`#22503a → #2f6f4e → #3f936a`), list-outline icon, "FREE · GPL" pill, "On this page" TOC preview card.
  - File: `../free-wordpress-table-of-contents-block-tocflow.png`
- **Bradley Goldsmith Law** (web design): navy/blue brand (`#0f3a66 → #1b5a9c → #2f7fc4`), courthouse icon, "WEB DESIGN PROJECT" pill, browser-mockup preview card.
  - File: `../bradley-goldsmith-law-featured.png`

## How to make a new one
1. Copy `featured-image-template.svg`.
2. Replace the gradient stops + accent colors with the project's brand.
3. Swap the icon, wordmark, tagline, pill, headline, subline, and preview card.
4. Export to PNG at 1200×630 and set it as the project's Featured Image (plus alt text).
