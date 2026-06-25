---
title: Support
---

# Matt Hummel theme — support & help

Everything you need to set up, configure, and get help with the **Matt Hummel**
WordPress theme. Built on **Sage 11 (Roots)** — server-rendered, accessible,
and deliberately light on plugins.

[Settings reference]({{ site.baseurl }}/THEME-SETTINGS.html){: .btn }
[Report an issue](https://github.com/matthummel-pa/matthummel-theme/issues){: .btn }

---

## Getting started

1. Download or clone the theme into `wp-content/themes/`.
2. From the theme folder, install dependencies and build assets:
   ```bash
   composer install
   npm install
   npm run build
   ```
3. In WordPress, go to **Appearance → Themes** and activate **Matt Hummel**.
4. Open **Appearance → Theme Tools** and apply a **Style Kit** to get a polished
   starting point, then fine-tune in the Customizer.

## Where settings live

| Area | What's there |
|---|---|
| **Customize → Theme Options** | Colors, typography, layout, header, navigation, menu/popout, announcement bar, dark mode, footer, SEO, performance, custom code, newsletter, cookie notice, white-label. |
| **Appearance → Theme Settings** | A tabbed admin panel mirroring the most-used options (General, Design, Layout, Header, Footer, Projects). |
| **Appearance → Theme Tools** | Style Kits, export/import settings, reset to defaults. |
| **Appearance → Local Fonts** | Download + self-host Google Fonts (removes the external request). |

The full catalog of every control is in the **[settings reference]({{ site.baseurl }}/THEME-SETTINGS.html)**.

## Common tasks

- **Re-skin the whole site** — Appearance → Theme Tools → pick a Style Kit (Editorial, Sage Classic, Warm Sand, Midnight, Mono Slate).
- **Self-host fonts** — Appearance → Local Fonts → *Download fonts now*, then toggle *Serve fonts locally*.
- **Connect GitHub for project pages** — Theme Settings → Projects → paste your OAuth App Client ID → *Connect with GitHub* (device-flow login). This raises the API rate limit for the live repo data.
- **Add social icons to a post/page** — insert the **Social Icons** block; it pulls from your site social links by default.
- **Add any icon** — insert the **Icon (Blade)** block and type a name like `si-github`, `heroicon-o-rocket`, or `lucide-zap`.
- **Show a posts/projects grid** — insert the **Post Grid** block.

## FAQ

**Do I need a page builder?**
No. The theme ships starter **patterns** (Hero, Pricing, Testimonials, Logo cloud, Feature grid, CTA band, FAQ, Stat strip) and dynamic blocks you compose in the normal block editor.

**Will it conflict with my SEO plugin?**
No. The built-in SEO/schema output automatically disables itself when Rank Math or Yoast is active.

**Does it work without Docker?**
Yes — there's a full no-Docker local stack (WP-CLI + SQLite + `wp server`). See the development docs.

**How do I move settings to another site?**
Appearance → Theme Tools → **Export** a JSON file, then **Import** it on the other site.

## Documentation

- [Settings reference]({{ site.baseurl }}/THEME-SETTINGS.html) — every control
- [Development](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/DEVELOPMENT.md) — local environment, build, deploy
- [Architecture](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/ARCHITECTURE.md) — how the theme works
- [Brand & design system](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/BRAND-DESIGN-SYSTEM.md)

## Get help

Found a bug or have a question? **[Open an issue](https://github.com/matthummel-pa/matthummel-theme/issues)** on GitHub.
