# Changelog

All notable changes to this project are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-06-26

### Added
- **`mh/section` block** — page-builder wrapper with background colour/image + overlay, padding top/bottom (none/sm/md/lg/xl), container width (narrow/contained/wide/full), text colour override, and optional horizontal rule above. InnerBlocks inside, server-side rendered outer wrapper so CSS variables always apply. Live preview in editor.
- **`matthummel` block category** — all `mh/*` blocks now appear under their own "Matthummel" group in the block inserter, registered via `block_categories_all` filter.
- **Block Patterns** — 11 pre-built patterns in the `matthummel` category: hero, stats, skills, focus, timeline, CTA band, resource groups, project cards, about page (full), résumé page (full), two-column text+image. Insert via Inserter → Patterns → Matthummel.
- **Pattern Library admin page** (Appearance → Pattern Library) — shows all registered matthummel patterns with descriptions, keywords, copy-name buttons, plus tips for using Synced Patterns (reusable blocks).
- **`blocks.css`** — dedicated accessibility-first stylesheet for all `mh/*` blocks. Covers section padding/width utilities, all block component styles, visible focus rings (`:focus-visible`), reduced-motion, forced-colours/high-contrast, and print safety. Imported via `app.css`.
- **Simplified page templates** — About, Résumé, and Resources templates now render `the_content()` only; blocks drive the layout via patterns.
- **`functions.php`** — added `blocks-bespoke`, `block-section`, `block-patterns`, and `pattern-library` to the Sage file loader array.

### Changed
- All 6 bespoke block JS editors updated: `category: 'widgets'` → `category: 'matthummel'` so they appear in the correct inserter group.

## [1.1.0] - 2026-06-25

### Added
- **Hero builder** â€” editable eyebrow / H1 / sub-paragraph, 1-3 columns (content + side image + 2nd image), content position (horizontal & vertical), content max-width + spacing with tablet/mobile overrides, and advanced flexbox controls (direction, justify, align, wrap, gap).
- **Hero media** â€” side image / illustration, plus a background cover image with overlay percentage and min-height.
- **In-Customizer image finder** â€” search Openverse (no key), Unsplash and Pexels (optional free keys), or generate a free AI image (Pollinations). Picks are imported into the Media Library (self-hosted) and set as the hero image.
- **Site-wide on-scroll animations** â€” IntersectionObserver reveal (fade-up/in, zoom, pop, blur, slide) with effect + speed, plus a hero entrance animation. Honours prefers-reduced-motion and never leaves content hidden.
- **Responsive controls** â€” per-device hide toggles (navigation/top-bar social, top-bar & navbar buttons, Menu label, logo shrink) and per-breakpoint inner widths (top bar / navbar / message bar); keep the top bar on one line at tablet.
- **Social Icons** Customizer section consolidating icon appearance and the social account URLs.
- Announcement bar hide-on-mobile option.

### Changed
- **Customizer reorganized** â€” split the overloaded Navigation section into Navigation / Social Icons / Responsive, moved header element alignment into Header Layout and popout controls into Menu & Popout, ordered all sections logically, and added section descriptions.
- Top bar, navbar, and message bar are driven by Theme Options again (removed the widget-area approach).
- Hero content position now moves all copy (flex column), not just the buttons.
- Dark mode: the navbar now uses a dedicated dark surface with light text.

### Fixed
- Top-bar social icons no longer render oversized / stacked (grouped-selector bug).
- Removed a duplicate "Sticky header" control (kept the one in Header Layout).

### Removed
- Experimental Canva integration (kept open-source image search + free AI generation).

## [1.0.0] - 2026-06-24

### Added
- Sage 11 theme scaffold (Blade, Tailwind v4, Vite, Acorn).
- "Paper + Space Grotesk" design system as Tailwind `@theme` tokens.
- Kadence-style **Theme Options** Customizer panel (colors, fonts, layout, header, footer) with live CSS-variable overrides.
- Live GitHub project engine (`app/Github.php`) + `[mh_github]` shortcode.
- Plugin-free contact form template + handler (`app/contact.php`).
- Templates: home/landing, blog index, single post, page, archive, search, 404, projects archive, single project.
- Documentation set: development, architecture, design system, content architecture, editorial + dev SOPs.
- CI workflow (build verification).

[1.1.0]: https://github.com/matthummel-pa/matthummel-theme/releases/tag/v1.1.0
[1.0.0]: https://github.com/matthummel-pa/matthummel-theme/releases/tag/v1.0.0
