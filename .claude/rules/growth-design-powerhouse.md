---
paths:
  - "resources/views/**"
  - "resources/css/**"
  - "resources/blocks/**"
  - "resources/js/**"
---

# Growth and design (this repo)

Personas, CRO and design workflow: the `growth-design` skill. This file is only what is specific to this theme.

## This repository (do not ignore)

This is the **matthummel** Sage 11 theme, not a Next.js/shadcn app. Blend the personas below with `agency-core.md`. If they conflict, **shipping patterns win** until Matt asks to migrate.

- **Copy:** visitor-facing copy is block content. `content.md` owns copy location and voice. Do not hardcode landing-page prose in Blade. Do not invent fake testimonials, revenue claims, or security-badge theater (`docs/FEATURES.md`).
- **CTA:** existing primary action is **Say hello** → `/contact/` (header + home). Keep one clear primary CTA; do not add competing “Sign up / Checkout” funnels.
- **Design tokens:** Tailwind v4 `@theme` in `resources/css/app.css` plus `resources/css/portfolio.css`. Navy/blue/gray, Inter + IBM Plex. `tailwind.config.js` is Gutenberg/theme.json only. Never invent random hex or spacing.
- **Icons:** existing `App\mh_svg_icon()` / inline SVG with `currentColor`. Do not add icon-font packs or raster icons.
- **Motion:** CSS/Tailwind transitions already in the theme. Do not add Framer Motion, GSAP, Aceternity, Radix, or shadcn/ui unless Matt asks.
- **Analytics:** no PostHog/Mixpanel/GA4 client bundle unless Matt asks. If he does, events belong in a small `resources/js/` module, not inline Blade.
- **SEO:** semantic Blade (`main`, `article`, `section`), real `alt` text, `language_attributes()`. JSON-LD only when Matt asks for a specific schema; do not spray fake `AggregateRating`.
- **Dark mode:** `html.mh-dark`. Do not switch to `data-theme`.
