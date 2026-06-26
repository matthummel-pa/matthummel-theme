# Support

Help, setup, and documentation for the **Matt Hummel** WordPress theme — a bespoke
**Sage 11 (Roots)** theme. Server-rendered, accessible, and light on plugins.

> 🔗 This page works as a direct link. A prettier version is published via GitHub
> Pages at **https://matthummel-pa.github.io/matthummel-theme/** once Pages is enabled.

---

## Getting started

1. Place the theme in `wp-content/themes/` and from its folder run:
   ```bash
   composer install
   npm install
   npm run build
   ```
2. Activate **Matt Hummel** under **Appearance → Themes**.
3. Open **Appearance → Theme Tools** and apply a **Style Kit** for a polished start,
   then fine-tune in the Customizer.

## Where settings live

| Area | What's there |
|---|---|
| **Customize → Theme Options** | Colors, typography, header layout, navigation, menu & popout, **social icons**, top bar, announcement bar, **hero**, **animations**, footer, layout, **responsive (mobile/tablet)**, dark mode, SEO, performance, custom code, newsletter, cookie notice, extras, white-label. |
| **Appearance → Theme Settings** | Tabbed admin panel (General, Design, Layout, Header, Footer, Projects). |
| **Appearance → Theme Tools** | Style Kits, export/import settings, reset to defaults. |
| **Appearance → Local Fonts** | Download + self-host Google Fonts. |

Full catalog: **[docs/THEME-SETTINGS.md](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/THEME-SETTINGS.md)**.

## Common tasks

- **Re-skin the site** — Appearance → Theme Tools → pick a Style Kit.
- **Edit the hero** — Customize → Theme Options → **Hero**: type your own eyebrow / H1 / subtext, choose 1–3 columns, content position, and an entrance animation.
- **Find or generate a hero image** — Customize → Theme Options → **Hero** → use the *Find a … image* control: search **Openverse / Unsplash / Pexels** or the **AI** tab to generate one; it saves to your Media Library and sets the image. (Unsplash/Pexels need a free API key, entered in the Hero section.)
- **Turn on scroll animations** — Customize → Theme Options → **Animations** → enable + pick effect/speed.
- **Hide things per device** — Customize → Theme Options → **Responsive (mobile & tablet)** → toggle social/buttons/labels per breakpoint.
- **Self-host fonts** — Appearance → Local Fonts → *Download fonts now*, then *Serve fonts locally*.
- **Connect GitHub** — Theme Settings → Projects → paste OAuth App Client ID → *Connect with GitHub*.
- **Add social icons** — insert the **Social Icons** block (pulls from your site social links).
- **Add any icon** — insert the **Icon (Blade)** block, type e.g. `si-github`, `heroicon-o-rocket`, `lucide-zap`.
- **Posts/projects grid** — insert the **Post Grid** block.

## FAQ

**Do I need a page builder?** No — ships starter patterns + dynamic blocks for the normal editor.

**Will it conflict with my SEO plugin?** No — built-in SEO/schema auto-disables under Rank Math or Yoast.

**Does it work without Docker?** Yes — a no-Docker local stack (WP-CLI + SQLite + `wp server`) is documented.

**Move settings between sites?** Appearance → Theme Tools → **Export** JSON, then **Import** elsewhere.

**Where do hero images come from?** Search **Openverse** (no key), **Unsplash**/**Pexels** (free key), or **generate a free AI image** — all from the Hero section in the Customizer. Whatever you pick is downloaded into your **Media Library** (self-hosted, not hot-linked).

**Are the animations accessible?** Yes — on-scroll reveals honour `prefers-reduced-motion`, and content is never left hidden if JavaScript fails (a safety timer reveals everything).

## Documentation

- [Settings reference](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/THEME-SETTINGS.md)
- [Development](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/DEVELOPMENT.md)
- [Architecture](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/ARCHITECTURE.md)
- [Brand & design system](https://github.com/matthummel-pa/matthummel-theme/blob/main/docs/BRAND-DESIGN-SYSTEM.md)

## Get help

Questions or bugs → **[open an issue](https://github.com/matthummel-pa/matthummel-theme/issues)**.
