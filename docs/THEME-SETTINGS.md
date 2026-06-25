---
title: Settings reference
---

# matthummel theme — settings reference

Every setting the theme exposes, where to find it, and what it does. The theme is
built on **Sage 11 / Acorn**; all options write WordPress **theme mods** (or options,
where noted) and render with no build step.

Three places hold settings:

1. **Customize → Theme Options** — the live, preview-as-you-edit panel.
2. **Appearance → Theme Settings** — a tabbed admin page mirroring the most-used options.
3. **Appearance → Theme Tools / Local Fonts** — utilities (presets, import/export, font hosting).

---

## Customizer → Theme Options

### Colors
- **Brand / buttons** — primary green used for buttons, links, accents (`--color-green`).
- **Page background** — site background (`--color-khaki`).
- **Headings** — heading text color (`--color-ink`).
- **Body text** — body copy color (`--color-body`).

### Typography
- **Heading font** / **Body font** — pick from Geist, Bricolage Grotesque, Schibsted Grotesk, Space Grotesk, Sora, Inter Tight, Fraunces, Inter, Work Sans, or System.
- **Base font size** — root body size (15–19px).
- **Body line height** — 1.5 to 2.0.
- **Heading line height** — 1.0 to 1.3.
- **Heading letter spacing** — tighter → wide.

### Typography (advanced)
- **Navigation font** / **Button font** — assign different families to the nav and buttons (loads only when set).
- **Heading / Body / Nav / Button weight** — per-element font weights.
- **Nav letter case** — normal / UPPERCASE / lowercase.
- **Body letter spacing** — tight / normal / loose.
- **Base font on tablet / mobile** — responsive overrides at ≤1024px and ≤600px.

### Extras
- **Underline content links** — underline links inside post/page content.
- **Button corner radius** — square → pill.
- **Card corner radius** — 6–20px.
- **Text selection color** — `::selection` background.
- **Scroll-to-top button** — floating back-to-top control.

### Layout
- **Default content width** — global content max-width (standard presets).
- **Per type (Pages / Posts / Projects / Archives)** — width preset, custom width, and show-sidebar toggle for each content type.

### Header Layout
- **Full-width menu** — stretch the nav across the header.
- **Header width / height / gap** — sizing of the header row.
- **Element position** — order of logo, menu, social links, and button.
- **Header button** — show/hide, text, and URL of the header CTA.
- **Sticky header** — pin the header on scroll.
- **Shrink on scroll** — reduce header padding once scrolled (needs sticky).
- **Transparent overlay header** — off / front page only / all pages.

### Top Bar
- **Enable top bar**, **contact text**, **show social links**, **button text/URL**, **background** and **text color** (palette).

### Navigation
- Full flexbox control of the menu: **direction, justify, align, align-content, wrap, gap**.
- Menu item box/type: **padding, min-height, font-size, weight, transform, letter-spacing, radius, color, hover color**.
- Popout (off-canvas) appearance controls.

### Menu & Popout
- **Use menu icon on desktop / tablet / mobile** — where the hamburger replaces the inline nav.
- **Panel background** — solid or gradient (start, end, angle).
- **Text / icon color**.
- **Social URLs** — LinkedIn, GitHub, Dev.to, X, Bluesky, YouTube, Instagram, Facebook, Mastodon, Email, RSS (each shows its Blade icon).

### Announcement Bar
- **Show bar**, **message**, **link text/URL**, **background/text color**, **dismissible** (remembered per visitor), and optional **start/end dates** for scheduling.

### Dark Mode
- **Enable dark mode toggle** and **default mode** (light / dark / auto by system).

### Footer & Header
- **Sticky header**, **show social icons in footer**.
- **Footer background / text** — palette choice or custom hex.
- **Footer columns** — 1–4 (each maps to a block widget area under Appearance → Widgets).
- **Footer tagline**.

### CTA & Intros
- Global **project CTA** plus intro text for the Projects and Contact templates.

### SEO & Schema
- **Output meta + schema** (auto-disables if Rank Math/Yoast is active).
- **Entity** — Person or Organization, **name**, **logo**, **default share image**, **Twitter/X handle**.
- Emits Open Graph, Twitter cards, and JSON-LD (Person/Org, WebSite, Article, BreadcrumbList).

### Performance
- Toggles: **disable emojis, oEmbed/wp-embed, jQuery Migrate, XML-RPC/pingbacks, dashicons (logged-out), wp_head cleanup, defer scripts**.
- **Preconnect domains** — comma-separated origins.

### Custom Code
- **Head / Body / Footer code** injection (analytics, pixels, verification) and a **Custom CSS** box.

### Newsletter
- **Form action URL** (Mailchimp), **heading**, **sub-note**, **button text** — rendered by `[mh_newsletter]`.

### Cookie Notice
- **Show notice**, **message**, **accept button**, **policy link** (URL + text). Dismissal remembered locally.

### White Label
- **Login logo**, **login background**, **admin footer text**, and **"Get started" dashboard widget** toggle.

---

## Appearance → Theme Settings (admin tabs)

A tabbed panel mirroring the most common options for quick edits:

- **General** — header button text/URL, show button, footer tagline.
- **Design** — colors, heading/body fonts, default content width.
- **Layout** — per-type width preset, custom width, sidebar.
- **Header** — full-width menu, sizing, element order, top bar, menu-icon breakpoints.
- **Footer** — show social, columns, background/text colors, tagline, sticky header.
- **Projects** — default GitHub owner, API token, data cache (hours), **OAuth Client ID**, and **Connect with GitHub** (device-flow login).

---

## Appearance → utilities

### Theme Tools
- **Style Kits** — one-click palette + font + radius presets (Editorial, Sage Classic, Warm Sand, Midnight, Mono Slate).
- **Export / Import** — download all theme settings as JSON and re-import elsewhere.
- **Reset** — return to defaults.

### Local Fonts
- **Download fonts now** — fetch the active families' woff2 into `uploads/mh-fonts/`.
- **Serve fonts locally** — use the local stylesheet and remove every Google Fonts request + preconnect.

---

## Blocks (in the inserter, "Matt Hummel" category)

- **Social Icons** — inline Blade SVG social icons; pulls from site social links by default, fully styleable (size, shape, brand/mono, colors, hover, alignment).
- **Icon (Blade)** — drop in any Blade icon by name (`si-…`, `heroicon-o-…`, `lucide-…`, `mh-…`), with size, color, alignment.
- **Post Grid** — query posts/projects/pages into a responsive card grid (columns, count, order, image/excerpt/date/category toggles).
- **Patterns** — Hero, Pricing, Testimonials, Logo cloud, Feature grid, Callout, CTA band, Stat strip, FAQ.

## Shortcodes
- `[mh_newsletter]` — newsletter signup form (Customizer → Newsletter).
- `[mh_breadcrumbs]` — breadcrumb trail with BreadcrumbList schema.

## Per-project (Projects → edit → "Project Details")
- **GitHub owner / repo**, **eyebrow**, **demo URL** — drive the live GitHub data section on each project.

---

## Notes for developers
- All front-end overrides are emitted via the `mh_head_end` action, which fires after the
  built stylesheet, so settings win without `!important` gymnastics.
- Icons use **Blade Icons** (Simple Icons `si-`, Heroicons `heroicon-o-`/`-s-`, Lucide `lucide-`,
  and a local `mh-` set in `resources/svg/`).
- Module files live in `app/*.php` and are registered in `functions.php`.