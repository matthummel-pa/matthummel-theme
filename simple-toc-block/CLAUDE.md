# CLAUDE.md — Simple TOC Block

Context file for Claude (Cowork / Claude Code) working on this project.

## What this is
A single-purpose WordPress block plugin: a **Table of Contents** block that
auto-generates a linked outline from a post's headings. Built deliberately as
ONE focused block (not a block library) to compete on quality, not quantity.

This is Matt's first WordPress product — it doubles as portfolio for an eventual
WordPress agency and as a freemium passive-income product.

## Tech / conventions
- Modern block dev with `@wordpress/scripts` (wp-scripts). Build with `npm run build`,
  develop with `npm run start`.
- **Dynamic block**: front-end markup is rendered in `src/render.php` (server-side),
  so the TOC is present in the initial HTML — good for SEO and accessibility.
- JS uses JSX + `@wordpress/*` packages. PHP follows WordPress coding standards
  (tabs, escaping, text domain `simple-toc-block`).
- Helper PHP functions live in the root file `simple-toc-block.php` (loaded once).
  `src/render.php` only contains procedural output — never define functions there
  (it is included on every render and would fatally redeclare them).

## How it works
1. `simple_toc_get_all_headings()` parses the post with `parse_blocks()` and builds
   ONE slug-stamped list of every heading. This is the single source of truth.
2. `render.php` filters that list to the selected levels (H2/H3/H4), normalizes
   depths, and prints a nested `<ul>`/`<ol>` of anchor links.
3. A `render_block` filter injects matching `id` attributes into the actual
   headings on the front end so the links have targets. Both sides use the same
   precomputed slugs, so anchors always match.

## File map
- `simple-toc-block.php` — plugin header, block registration, all PHP helpers + the heading-id filter.
- `src/block.json` — block metadata + attributes + supports.
- `src/index.js` — registers the block.
- `src/edit.js` — editor UI (InspectorControls: title text, level toggles, ordered toggle).
- `src/render.php` — server render of the front-end TOC.
- `src/style.scss` — front-end + shared styles.
- `src/editor.scss` — editor-only styles.

## Roadmap (free vs pro)
**Free (this repo):** auto TOC, choose H2/H3/H4, numbered/bulleted, smooth anchors,
server-rendered, accessible `<nav>`.

**Next free polish:**
- Smooth-scroll + scroll offset for sticky admin bars/headers.
- "Collapse/expand" toggle for the list.
- ServerSideRender live preview in the editor.

**Pro ideas (paid upgrade):**
- Sticky sidebar TOC with scroll-spy active highlighting.
- Multiple style presets / numbering styles.
- Auto-insert before first H2 site-wide (no manual block placement).
- Per-post include/exclude of specific headings.
- Schema / structured-data output.

## Current status
v0.1.0 — core free feature set scaffolded and building. Next task: test in a real
WP install (wp-env or Local), then add smooth-scroll offset.
