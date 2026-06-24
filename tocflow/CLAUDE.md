# CLAUDE.md — TOCflow

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
  (tabs, escaping, text domain `tocflow`).
- Helper PHP functions live in the root file `tocflow.php` (loaded once).
  `src/render.php` only contains procedural output — never define functions there
  (it is included on every render and would fatally redeclare them).

## How it works
1. `tocflow_get_all_headings()` parses the post with `parse_blocks()` and builds
   ONE slug-stamped list of every heading. This is the single source of truth.
2. `render.php` filters that list to the selected levels (H2/H3/H4), normalizes
   depths, and prints a nested `<ul>`/`<ol>` of anchor links.
3. A `render_block` filter injects matching `id` attributes into the actual
   headings on the front end so the links have targets. Both sides use the same
   precomputed slugs, so anchors always match.

## File map
- `tocflow.php` — plugin header, block registration, all PHP helpers + the heading-id filter.
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

## matthummel.com — Project page design rules (PRIORITY — always apply)
These are standing design requirements for matthummel.com **project pages** (the
`projects` CPT: keepary, tocflow, future repo projects). Apply them automatically,
without being re-asked, on every project-page build or edit:

1. **Headings are always centered.** Center all heading content (hero title, section
   titles, CTA heading, sub-text).
2. **Headings always use the khaki color.** Use the site's khaki/tan heading color
   `rgba(74, 66, 44, .62)` (matches the theme's page headings). Headings are Fraunces
   serif, large (hero ~64px, sections ~46px, uppercase), and **centered**.
   - CRITICAL BUG/GOTCHA: the Kadence theme sets **`-webkit-text-fill-color: #2a303b`**
     on `.entry-content` headings, which paints the visible glyphs dark even when
     `color` is khaki (so `getComputedStyle().color` lies — it shows khaki while the
     text renders dark). You MUST set BOTH `color` AND `-webkit-text-fill-color` to
     the khaki value with `!important` on every heading. Same trick for any element
     whose visible color the theme is overriding (icons: `-webkit-text-fill-color:#fff`).
3. **No green-gradient backgrounds on the CTA.** The bottom CTA block must NOT use a
   green gradient. Use a cream/khaki card instead (e.g. `rgba(255,253,247,.7)` with a
   `#ddd2b3` border), khaki heading, dark body text, green primary button.
4. **Icon glyphs are white.** Inside colored icon chips (tools, highlights, stats),
   the icon (Font Awesome `<i>`) must be white (`color:#fff !important`) on the
   colored chip background. Use Font Awesome (loaded via cdnjs) for tool/tech icons.
5. **Buttons: white text on a green background (accessibility).** Any button with a
   green background must use white text. Force with `color:#fff !important` AND
   `-webkit-text-fill-color:#fff !important` and `text-decoration:none !important`
   (the theme bleeds its green link color + underline into in-content links).
6. **Buttons match the rest of the site.** The theme's buttons (nav "Find me on
   Dev.to", footer, homepage CTAs) are: green `#4e6b4a` bg, white/cream text,
   `border-radius:3px`, `padding:~12px 24px`, `font-size:14px`, `font-weight:700`,
   Inter. Use the SAME spec for all project-page buttons so they're consistent
   site-wide. Primary = filled green; secondary = cream/outline (`rgba(255,253,247,.65)`
   bg, `#2a303b` text, light border).

### Reference tokens (matthummel.com, measured from live pages)
- Page bg (khaki/beige): `#DCCFA6`. Heading khaki: `rgba(74,66,44,.62)`.
- Nav/footer container width: ~1290px (project content `max-width:1240px`, centered).
- Card surface: `rgba(255,253,247,.65)` cream, border `#ddd2b3`, radius 14–18px.
- Page headings: Fraunces serif, H1 ~64–73px, H2 ~46–58px, weight ~530, UPPERCASE,
  centered, khaki. Body: Inter.
- Font Awesome loaded via cdnjs `font-awesome/6.5.1/css/all.min.css`.
- Theme forces `.entry-content pre`/`code` background to light cream — for dark code
  blocks, set the `<pre>`/`<code>` `background:transparent !important` over a dark
  wrapper `<div>` and `color:#e6edf7 !important`.

### Layout/theme notes (so it renders reliably on this Kadence install)
- Content width ~1240px (matches the nav/footer container ~1290px); Kadence per-post
  meta: `_kad_post_layout:fullwidth`, `_kad_post_content_style:unboxed`,
  `_kad_post_title:hide`, `_kad_post_feature:hide`.
- The theme **strips backgrounds** from `.wp-block-group` and from any class
  containing the substring `card`, even inline — so card panels must be raw `<div>`
  with a non-"card" class + inline background (e.g. `rgba(255,253,247,.65)`).
- **CURRENT ARCHITECTURE (June 2026 rebuild):** project pages (keepary #4511,
  tocflow #4478) are now built from **native blocks**, NOT a custom HTML block:
  Kadence Advanced Heading (hero H1 + section H2s), Kadence Advanced Buttons
  (`kadence/advancedbtn` + `singlebtn`), core gallery (2-col screenshots), core
  list (tech/highlights), core code (snippets), core separators (row breaks),
  centered core paragraphs. The old FA-icon/chip/stat-card design was dropped in
  favor of clean native blocks per Matt's "clean native, minimal CSS" choice.
  - Build them programmatically via the block editor's own API on the post-edit
    screen: `wp.blocks.createBlock(...)` -> `wp.data.dispatch('core/block-editor')
    .resetBlocks([...])` -> `wp.data.dispatch('core/editor').savePost()`. This
    yields valid blocks AND runs Kadence's save pipeline. (Hand-writing Kadence
    block markup via REST is unreliable and its dynamic CSS won't generate.)
  - All visual styling lives in ONE compact, scoped block in **Customizer ->
    Additional CSS**, not inline per-post. Markers in that CSS:
    `/* mh: kadence advanced heading true color */` (forces advanced headings to
    show their real color via `-webkit-text-fill-color:currentColor`) and
    `/* mh project-page native block styling */ ... /* END mh project */` (khaki
    centered Fraunces headings 60px/42px, green `#4e6b4a` + white buttons, dark
    `#0f1729` code with transparent inner `<code>`, 2-col gallery, centered
    constrained paragraphs/lists).
  - CRITICAL scoping gotchas in that CSS:
    (a) `.single-projects` is the **body** class -> use `body.single-projects`
        (compound), NOT `... .single-projects` (descendant) or it matches nothing.
    (b) Accumulated older button CSS uses ID-boosted selectors, so the button
        override must out-specify them: `body.single-projects:not(#z)
        .entry-content .wp-block-kadence-advancedbtn .kb-button` (`:not(#z)` adds
        ID-weight) -- same trick the footer CSS uses.
    (c) Theme forces inner `<code>` background light -> set it `transparent`.
- Site-wide a11y fix also in Additional CSS (`/* a11y: green Kadence button
  contrast */`): forces white text on all green `.kb-button`s (header + footer),
  fixing cream-on-green contrast failures across the whole site.
- **GitHub Project block pattern** (for spinning up new GitHub-category projects):
  registered via **Code Snippets** plugin snippet #21 ("GitHub Project block
  pattern", `register_block_pattern('mh/github-project', ...)` in category
  `mh-github` = "GitHub Projects"). It appears in the editor's pattern inserter.
  - It is **Kadence-only**: Advanced Heading for headings (h1/h2) AND body text
    (htmlTag `p`, class `mh-body`/`mh-eyebrow`/`mh-stats`) AND code (htmlTag `div`,
    class `mh-code`, monospace dark via CSS); Advanced Buttons; Icon List for
    tech/highlights; Row Layout (2 col) + Kadence Image for the screenshot pair
    (Kadence Advanced Gallery does NOT round-trip serialize→parse cleanly — it
    shows a block-recovery prompt — so use rowlayout+image instead). Core
    separators for row breaks. Placeholder content the user replaces per project.
  - The pattern markup is embedded in the snippet via a PHP **nowdoc** (`<<<'PATTERN'`)
    so no escaping is needed. To regenerate it, build the blocks with
    `wp.blocks.createBlock` + `serialize` in an editor, verify `parse()` returns
    zero `isValid===false`, then update snippet 21's `code` via the Code Snippets
    REST API (`/code-snippets/v1/snippets/21`).
  - CSS for the pattern lives in the same `/* mh project-page native block styling */`
    block: the khaki heading rule is **tag-scoped** (`:is(h1,h2,h3,h4)
    .wp-block-kadence-advancedheading`) so `p`/`div` advanced headings are NOT
    painted as khaki uppercase headings; `p.wp-block-kadence-advancedheading` =
    centered dark Inter body text; `.mh-code` = dark monospace; `.wp-block-kadence-iconlist`
    + `.wp-block-kadence-rowlayout` are width-constrained and centered.

### CURRENT LIVE DESIGN — Dynamic GitHub redesign (June 2026, latest)
Project pages (keepary #4511, tocflow #4478) were redesigned again to pull repo
info **live from GitHub**. This is the current production design.
- **Data engine — Code Snippet #22** ("GitHub repo data ([mh_github] shortcode)"):
  `mh_github_fetch($owner,$repo)` calls the GitHub API server-side with
  `wp_remote_get` (User-Agent required) for repo metadata, latest release, and the
  README rendered as HTML (`Accept: application/vnd.github.html`), caches in a 6h
  transient. The README "intro" = everything up to the **2nd** `<h2>` (intro +
  first section), with the leading `<h1>` removed, `<h2>`→`<h3>` demoted, and
  badge `<img>` / octicon `<svg>` / in-page `#anchor` links stripped (those leave
  empty links → `link-name` a11y fails otherwise). Output via `wp_kses`.
  Shortcode: `[mh_github owner="…" repo="…" show="desc,stats,intro"]` →
  `<div class="mh-gh">` with `.mh-gh-desc`, `.mh-gh-stats` (badge `<li>`s), and
  `.mh-gh-readme`. (Owner is `matthummel-pa`.)
- **Page layout** (Kadence blocks + dynamic shortcode, built via editor API):
  eyebrow (adv-heading `p.mh-eyebrow`) · title (adv-heading h1) ·
  `[mh_github show="desc"]` subtitle · Kadence Advanced Buttons ·
  `[mh_github show="stats"]` badge row · separator · "A look inside" + **core/gallery
  (2-col)** for screenshots (Kadence Row Layout's wrapper/columns DON'T render
  reliably when built programmatically — images stack full-width — so use core/gallery) ·
  separator · "About this project" + `[mh_github show="intro"]`. **No per-page CTA.**
- **Global CTA — Code Snippet #23** ("Global Project CTA (after content)"): hooks the
  **"Project CTA" reusable block #4498** into Kadence's `kadence_after_main_content`
  hook (priority 20) on `is_singular('projects')`, wrapped in `.mh-project-cta`.
  Renders after content, before footer, project pages only; edit the CTA in block #4498.
  - NOTE: A real **Kadence Element** (`kadence_element` CPT) was attempted but its
    display-conditions meta (`_kad_element_show_conditionals`, a JSON **string** like
    `["singular|projects"]`) + hook/type set via REST did NOT make the element render
    (Kadence needs its own editor UI to register it). The hook snippet is the reliable
    equivalent. Element #4587 is left as a draft (inert).
- **A11y — Code Snippet #24**: JS marks the header `.kb-search-icon svg` decorative
  (`aria-hidden`/`focusable=false`); the `<button>` is already labelled "Search".
  Clears the last site-wide `svg-img-alt`. Both project pages = 0 axe violations.
- **CSS** for the dynamic section lives in Additional CSS under
  `/* mh github dynamic section */ … /* END mh github */` (`.mh-gh-desc` subtitle,
  `.mh-gh-stats` cream badge cards w/ Fraunces numbers, `.mh-gh-readme` constrained
  left-aligned body prose w/ green links).
- Remaining homepage `color-contrast` axe flags are the **intentional faded-khaki
  display headings** (`rgba(74,66,44,.4)`) — a deliberate design choice, not a bug.

> Note: these rules also belong in Matt's **global** Claude instructions (the file
> that holds the matthummel.com WordPress/Kadence instructions) so they apply to all
> matthummel.com work, not just the tocflow folder.
