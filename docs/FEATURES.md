# Feature log

What the 3.x Sage theme does, and where it lives.

## Editor’s notes (3.6.38 Get updates)

- Newsletter behavior lives in `plugins/matthummel-newsletter/`. Do not add Mailchimp, HubSpot, or another ESP.
- Theme `app/newsletter.php` is the fallback when `MHN_VERSION` is not defined. The footer calls `mhn_render_footer_form()` when the plugin is active.
- Visitor hero copy for the page is `upd_kicker`, `upd_h1`, `upd_lede` on template `template-get-updates.blade.php`.
- Legacy footer rows are copied once into the plugin list as subscribed `legacy_single`. New signups are double opt-in. Auto-send stays off.
- **Create newsletter** (`admin.php?page=mhn-wizard`) is the guided path. It autosaves a draft `newsletter_issue` and edits the same post as the block editor.
- Templates live in `email_templates()` (`mhn_email_templates`). Blog update, blog digest, and custom message each have a note field. Patterns `mhn/blog-update`, `mhn/blog-digest`, and `mhn/custom` seed the editor.
- Publishing a post drafts a blog update. The note starts with `Hi {first_name|there},` and the P.S. stays empty. It does not send. Owner steps: `docs/NEWSLETTER.md`.
- The confirm page collects optional first and last name (`sanitize_text_field`, 80 characters). Columns ship through `mhn_db_version` 2 (`ensure_subscriber_columns()`), so plugin 1.0.0 can add `last_name` without a version bump. Merge tags `{first_name}`, `{last_name}`, and `{full_name}` escape HTML and accept a fallback (`{first_name|there}`). The wizard preview uses sample names Ada Lovelace.
- Blog update inserts the featured image near the top, linked to the post, at most 600px wide, with width, height, and a fluid style. Digest cards use a 280px thumbnail. Alt text is the attachment alt, then the post title. No featured image means no image block. `_mhn_feature_show` hides it for one send. `_mhn_feature_image_id` replaces it on a blog update. The same compile path is what an automatic send would use.
- Email accessibility checks live in `includes/a11y.php`. CI runs `php plugins/matthummel-newsletter/bin/check-email.php`. Send and schedule are blocked when a content image has no alt text. A leftover name merge tag is an error.
- Click and open links use a tracking token, not the unsubscribe token. A click is honored only when tracking is on and the destination was signed. Signup limits are per IP, and each address can get one confirmation email every 30 minutes.

## Editor’s notes (3.6.37 projects listing)

- Do not pass `featured` on `partials/work-card.blade.php` from `template-projects.blade.php`. Every live project, including TOCguide, is a same-size grid card.
- The Projects listing hero uses `useScene` so it does not pull the first project screenshot. Project singles still pass catalog slides into `page-hero`.
- `mh_project_card_image_url()` prefers `_mh_project_image` (catalog WebP). Featured image is fallback only.
- `mh_project_page_slides()` leads with stored / catalog screenshots. WP thumbnail is last-resort.
- Feature list items are plain `li.project-feat` text. The blue check is CSS `::before` only (`studio.css` 3.6.37 last-win). Do not add `.project-feat__icon` back.
- Hide Rank Math / Yoast crumbs on project CPT and the Projects template (`mh_is_project_surface()`).

## Editor’s notes (3.6.36 screenshot URLs)

- `_mh_project_screenshots` stores theme-relative paths (`products/slug/file.webp|caption`). Do not write full site URLs at seed time.
- `mh_product_media_url()` and `mh_project_page_slides()` rewrite baked `/themes/{folder}/resources/images/` URLs so local and Hostinger hosts both work.
- Catalog bump is `mh_product_catalog_v10`.

## Editor’s notes (3.6.35 project feedback)

- Project singles (`partials/content-single-project.blade.php`) include like/star (`partials/project-react.blade.php` + `mh_project_react` AJAX), `comments_template()`, and compact `partials/contact-form.blade.php`. Do not add a form plugin.
- Visitor counts are `_mh_visitor_likes` / `_mh_visitor_stars`. GitHub `_mh_project_stars` stays separate.
- TOCguide is the catalog key and project slug. Keep `tocflow` aliases, SKU `plugin-tocflow`, and the 301 from `/projects/tocflow/`.
- Catalog bump is `mh_product_catalog_v9` (force upsert). Contact page reuses the shared form partial.

## Editor’s notes (3.6.34 page hero padding)

- `.page-header--photo` padding-block is `clamp(3.75rem, 9vh, 6rem) 6.25rem`. Home `.h-hero--viewport` is `clamp(5.25rem, 11vh, 7.25rem) 6.25rem`. Bottom stays above the wave (`clamp(3.4rem, 8vw, 5.75rem)`). Do not drop back to `4.5rem` bottom.

## Editor’s notes (3.6.33 On this page padding)

- `.h-page-nav__inner` is `.7rem` padding-block by default, `1rem` from `901px` up. Do not match the header’s `1rem` on small screens — the jumper is sticky there and should stay compact.

## Editor’s notes (3.6.31 featured-image heroes)

- `partials/page-hero.blade.php` is a full-bleed photo hero: featured image, white wash, white copy panel, wavy bottom. Do not restore `$split` / `$aside` snapshot cards.
- Home uses `.h-hero--photo.h-hero--viewport` (`min-height: calc(100dvh - header)`). The white `.h-hero__panel` / `.page-header__panel` is full width of `container.wide` (`--page-max`). Do not cap it at `42rem`.
- Photo URL is `mh_hero_background_url()`: work featured image if it is not a headshot, else a page-matched studio screenshot (`mh_hero_scene_url()`). Never GitHub avatars, Gravatar, or `matt-hummel.jpg`.

## Editor’s notes (3.6.30 no hero availability pill)

- Marketing page heroes (About, Hire, Code, Contact) do not print `.hire-avail` in the copy column. Do not put the Open for work pill back under the lede. Header, footer, Hire snapshot status, and the About availability section stay.

## Editor’s notes (3.6.29 split hero photos / current nav hover)

- `partials/page-hero.blade.php` renders `$split` / `$aside` again. Do not hide `.page-header-split__aside` or `.h-hero-illu`. Glow/orbs stay off.
- Snapshot photos use `.h-hero-illu__photo` in `partials/hero-panel.blade.php`. About is square (`--square`); work/journal shots are 16×10. Empty `alt` when the card title already names the image.
- Current header nav hover is white on `--blue-800`, not `--blue-400` on navy. Last-win lives at the end of `studio.css`. Same for `.filter-pill.is-active` and `.h-page-nav__pill.is-active`. Exclude those from the 3.6.21 site-wide blue hover.

## Editor’s notes (3.6.28 footer bottom on mobile)

- Last-win in `studio.css`: `.footer-bottom` is a column with `align-items: center` under 860px. Do not leave `justify-content: space-between` on that breakpoint.

## Editor’s notes (3.6.27 heading scale)

- Body stays `--type-body`. Content headings use `--type-h2` … `--type-h6` in `portfolio.css` (`:root`) plus last-win in `studio.css`. Do not set content `h2` back to `clamp(1.15rem, 2vw, 1.35rem)`.
- Card titles (work cards, who-cards, glance) keep their own `font-size`.

## Editor’s notes (3.6.26 project header / journal cats)

- Pill labels come from `mh_project_type_row_labels()`. Do not print Type/Place again in `mh_project_buyer_docs()` specs.
- Combined cat strings (`Themes · Real estate`) split; type-plural cats stay off the row. Place stays a separate pill.
- Journal cards: unique cats on the meta row. Skip `uncategorized`. Do not render a second cat line under the date.

## Editor’s notes (3.6.25 project type pills)

- Shared chrome is `partials/project-type-row.blade.php`. Do not put a second plain-text `pf-meta` cat line on project heroes or work cards.
- Skip category when it is the type label or its plural (`Theme` / `Themes`). Keep industry cats (`Tours`) and place pills.
- Screenshot disclaimer is `.project-stage__note`, not a muted caption.

## Editor’s notes (3.6.24 footer social / green hover)

- Footer social lives in `.footer-brand-social` under Open for work. Do not put `.soc-list` back in `.footer-bottom`.
- Open for work hover is white on `#14532d`, never `--blue-400`. Keep it excluded from the last-win `a:hover` blue rule in `studio.css`.

## Editor’s notes (3.6.23 shared On this page)

- Sticky jumper chrome is only `partials/page-nav.blade.php` (`.h-page-nav` + `page-nav-track` + `initSectionNav()`). Do not add a second TOC pattern (sidebar details, `.pf-product-toc`, About `<details>`).
- Add the include after the hero when a page has two or more in-page sections. Pass `nested => true` only inside an existing column (product gallery).
- Account desk “Next steps” is not the jumper. 404 stays without a row (one block).

## Editor’s notes (3.6.22 footer signup / simple heroes)

- Footer signup is first-party (`app/newsletter.php`). Do not add Mailchimp, FluentCRM, or another ESP unless Matt asks.
- Copy keys: `footer_signup_label`, `footer_signup_lede`, `footer_signup_button` on Home. Button chrome default is **Sign up**. Heading default is **Get updates**.
- Admin: **Get updates** (`mh-newsletter`) + Export CSV. Table `{prefix}mh_newsletter`.
- `partials/page-hero.blade.php` renders `$split` / `$aside` again (3.6.29). Keep glow/orbs off.
- Keep On this page arrows (`page-nav-track` + `initPageNavTrack()`). Do not restore a visible scrollbar or the About mobile `<details>` dropdown.
- Hover/focus color for links and primary buttons is `--blue-400` (`#4f8fd4`), not `--blue-700`.

## Editor’s notes (3.6.21 visible hover)

- `--color-accent-hover` is `--blue-400` (`#4f8fd4`), not `--blue-700`. Do not set link/button hover back to navy (`#0a2446` / `#0d2e57`) — it fails as a perceivable state change.
- Footer and site-wide `a:hover` / `.btn:hover` last-win lives at the end of `studio.css`. Exclude `.header-avail` / `.footer-avail` so Open for work stays green.

## Editor’s notes (3.6.20 on this page arrows)

- Overflow on `.h-page-nav` / `.pf-product-toc` is driven by `partials/page-nav-track.blade.php` + `initPageNavTrack()` in `section-nav.js`. Do not restore a visible scrollbar or the About mobile `<details>` dropdown unless Matt asks.
- Arrow buttons stay `hidden` when the row fits. Disabled at the start/end when it overflows.

## Editor’s notes (3.6.19 footer social / hover)

- Footer social lives in `.footer-brand-social` (under Open for work). Icon-only `.soc-link` has no circle and no hover fill.
- Follow / RSS is the last footer column (`footer-follow`), not the brand column.
- Hover for `.btn` and `a` must change `color` (and keep `:focus-visible`). Do not restore filled social circles.

## Editor’s notes (3.6.18 split heroes / RSS)

- `partials/page-hero.blade.php` is a featured-image hero (3.6.31). Do not restore `$split` / `$aside` snapshot cards.
- Journal subscribe is `\App\field('write_subscribe_*')` via `partials/write-subscribe.blade.php`.

## Editor’s notes (3.6.17 kicker gap)

- Kicker → heading is `margin-top: 12px` in `studio.css`. Do not restore `-0.22em` flush or the `1.85em` mid-flow `p + h2` on `.h-section-label`.

## Editor’s notes (3.6.16 local hostname)

- `mh_local_dev_hosts()` in `app/setup.php` is `matthummel-theme.local`, `localhost`, `127.0.0.1` only. Do not add `matthummel.com`. `option_home` / `option_siteurl` follow `HTTP_HOST` for those names so Cloud `:8080` and `.local` both work.
- Preferred local URL: `http://matthummel-theme.local:8080` plus a hosts line. PHP `wp server` is not Herd HTTPS on 443.

## Editor’s notes (3.6.15 kicker → heading)

- Last-win in `studio.css`: `.eyebrow`, `.h-section-label`, `.h-hero__kicker` use `margin-bottom: 0`. Headings after those kickers use `margin-top: 12px` (not `-0.22em`).
- Mid-flow `p + h2` (`1.85em`) must exclude `.h-section-label` / `.h-hero__kicker` / `.eyebrow` or **Projects** / **Selected projects.** gets a full heading gap. Do not restore that on kickers.
- Keep heading → lede spacing (`.lead`, `.h-work-intro`). Do not tighten `.h-about-who__label` (list, not a heading).

## Editor’s notes (3.6.14 list checkboxes)

- Content `ul` and `.project-feat` markers are `1.2rem` circles (`border-radius: 50%`) filled with `--color-spark` (`#1a6bb5`), white check. Dark mode uses `--blue-400` on `--color-surface`.
- Align to the first line (`1lh` offset or flex `align-items: flex-start` on feature tiles). Nested `ul ul` stays an open circle; `ul ul ul` stays a dash.
- `.project-arch-list` uses the same circles and first-line alignment, with `border-bottom` hairlines and `background: transparent` (not `.project-feat` grey tiles). Nested architecture rows match circle/dash.
- Do not put these markers on spec lists, GitHub stats, FAQs, or nav.

## Editor’s notes (3.6.13 project info headings)

- Info-column blocks on `/projects/{slug}/` sit in `.project-info-section` with `h2.display-title.is-section`. Copy is UI chrome (`Build notes`, `Theme details`, `Runs on`, `Theme tags`) — not page fields. Skip the heading when the block is empty.

## Editor’s notes (3.6.12 project stats)

- `/projects/{slug}/` GitHub facts stay `.project-stat-grid` / `.project-stat` grey tiles (`--gray-50` / `--color-surface`). Last-win layout is in `studio.css` (4 columns, then 2 on phones). Do not wrap the aside in a silver card.
- Labels (`.project-stat dt`) are `font-weight: 700`. Values (`.project-stat dd`) are `400`. Do not bold the number under the label.
- Spec rows live in `.project-spec-block` (one grey tile, not a 4-col grid). Labels (`.project-spec-label`) are `font-weight: 700`. Values (`.project-spec-value`) are `400`. Hairlines between rows. Do not wrap values in `<strong>`. Do not stretch `.pill` chips.

## Editor’s notes (3.6.11 FAQ toggles)

- Project Questions are `<details class="project-faq">` inside `.faq-list`. Do not restore always-open `<h3>` / `<p>` pairs.
- Grey tile: `background: var(--gray-50)` (dark: `--color-surface`), `border: 0`, `box-shadow: none`. Override lives in `studio.css` so `portfolio.css` hover shadows stay off.
- Chevron is `summary::after`. Keep native details (no JS accordion).

## Editor’s notes (3.6.11 architecture list)

- Architecture on `/projects/{slug}/` is `.project-arch-list` (`ul` / `li`), split by `mh_project_prose_list_items()`. Do not dump it as paragraphs again.
- Dividers are `border-bottom` hairlines on `li:not(:last-child)`. Round spark checkboxes match content lists. Rows stay `background: transparent` — not grey tiles. Do not wrap the block in a silver card or add `box-shadow`.

## Editor’s notes (3.6.10 list markers)

- Content `ul` / `ol` markers live in `studio.css` (front) and `journal-blocks.css` (editor). Do not restore disc/decimal `::marker` on `.post-prose .wp-block-list`.
- Do not put checkbox `::before` on chrome lists (`.mh-tool-grid`, `.mh-ship-pipe`, TOC, comments, process/svc cards).
- Project sample items use `.project-feat::before` checkboxes; keep the 2-col grid.
- Glance/stat grid tiles (`.concept-metric`, `.project-stat`, `.h-glance__fact`) use grey fill. Still no silver frame.

## Editor’s notes (3.6.9 grey heroes / features)

- Heroes use `--color-hero` (`--gray-50` in light, `#172233` in `html.mh-dark`). Do not force `#fff` on `.h-hero` / `.page-header`.
- `.project-feat` keeps a grey fill. Do not add silver `border` / `box-shadow`.
- Info tiles (`.concept-metric`, `.project-stat`, `.h-glance__fact`, `.hire-li-stat`, `.code-gh-stat`) match that fill. Do not grey `.project-detail-block` story copy.

## Editor’s notes (3.6.8 whitespace)

- Content sections use padding/gap, not `border` + `box-shadow`. Do not wrap architecture / GitHub facts in a card again.
- `.pf-section--alt` stays white. Do not restore `page-header::before` gradients.

## Editor’s notes (3.6.7 project column)

- Project singles are one column: gallery, then details, then story. Do not restore the 2-col `project-stage` sidebar.

## Editor’s notes (3.6.6 project story type)

- `.concept-story` is a column with `gap`. Do not flatten those blocks into sibling `p + h2` without keeping the gap.
- Content headings use line-height 1.2. Keep 0.98 on `h1` / `.display-title` only.

## Editor’s notes (3.6.5 pill width)

- Hero and project pills use `width: fit-content` so flex-column parents do not stretch the chip. Do not set those chips to `width: 100%` or `align-items: stretch` without an explicit exception.

## Editor’s notes (3.6.4 type rhythm)

- Mid-flow headings use adjacent-sibling top margin (`p + h2`, block classes). Exclude `.eyebrow`, `.h-section-label`, and `.h-hero__kicker` from that rule. Do not put large `margin-top` on every heading.
- Canonical journal spacing lives in the later `.post-prose` block in `portfolio.css` (not the short layout shell earlier in the file).
- Keep `.eyebrow + h2` tight on legal/a11y shells.

## Editor’s notes (3.6.3 projects mobile)

- Featured work cards on small screens are a stacked 16:9 screenshot + body. Do not restore `height: 100%` on `.work-shot` without an explicit parent height.
- Project gallery stage uses in-flow screenshots (`width: 100%` + aspect-ratio). Do not put `grid-area: gallery` on bare `.pf-product-gallery` — that shop rule hid project galleries under 860px. Scope it to `.pf-product-layout`.

## Editor’s notes (3.6.2 project pages)

- Page heroes are single-column copy on a solid light gray band. Do not restore the stats panel / split hero-illu.
- Project singles use `mh_project_page_slides()` + `mh_project_github_facts()`. Keep architecture and handoff visible — do not hide them in `<details>`.
- Availability chips use solid `#15803d`. Do not go back to pastel `#f0fdf4`.

## Editor’s notes (3.6.1 grey heroes)

- Heroes are flat light grey (`--color-hero`). Do not restore blue radial blobs, mesh, or hero images (home gallery, About profile photo, illustration orbs).
- Body and non-hero sections stay white. Tinted bands (`h-band--tint`) are white, not blue-50.
- Customizer “accent band” is the same grey as the hero. Do not map it back to `--blue-50`.
- Do not fade hero copy or About facts in from opacity 0.

## Editor’s notes (3.6.0 decluttered portfolio)

- Home order is hero → Projects CPT → journal → What I do. Do not put project cards back in the hero.
- Hero copy/layout: Customizer **Home hero** wins when set; otherwise Page content (theme) / `mh_home_hero_default()`.
- No CSS gradients on the home surface. Solid gray hero band; optional Customizer accent stays a flat fill.
- `mh_public_shop_enabled()` stays false. Do not restore shop, cart, or buy CTAs on Home, header, or footer.
- Featured home cards come from `mh_home_featured_projects()` (live Projects CPT first).

## Editor’s notes (3.5.33 production site)

- Canonical WordPress is `https://matthummel.com`. WPVibe `site_url` defaults there. `hummelwp.com` is a separate install — do not use it as the default for content, theme update, or deploy notes.

## Editor’s notes (3.5.32 Woo notice icons)

- WooCommerce notices keep an in-flow CSS-mask icon. Do not restore extra left padding for the WooCommerce icon font (`::before` was overlapping the first letter).
- Empty payment-methods copy lives inside `#payment` — flatten the inner `.woocommerce-info` / notice banner there so it is not a nested card.

## Editor’s notes (3.5.31 minimal blue)

- Visual system is cool paper + navy/blue accents (`#0d2e57` family). Edit `resources/css/studio.css` for the skin; `portfolio.css` owns layout.
- Home keeps hero + **What I do** + projects + journal + CTA. Process, fit, FAQ, principles, and recruiter glance live in `partials/about-hire-sections.blade.php` on About. Do not restore the long Home hire funnel unless Matt asks.
- GitHub repos, activity, and stack detail stay on Code — not Home.
- Copy voice stays plain and first-person. Prefer short sentences over CRO jargon (“without guesswork”, “Pick the door”).
- `mh_minimal_blue_copy_v2` rewrites exact prior home/about/code field defaults once (includes What I do heading).

## Editor’s notes (3.5.30 studio skin)

- Superseded by 3.5.31 for palette. Historical note: 3.5.30 was warm paper + tomato/amber pops.

## Editor’s notes (3.5.29 readable project pages)

- Projects listing copy lives in `mh_projects_listing_default()`. Keep sentences short (grade 6–8). Do not restore the four audience cards or dual closing CTAs unless Matt asks.
- Single-project story headings stay Why I built it / What I did / What you can use. Developer notes (architecture, handoff) stay on the page, not in an accordion.
- Catalog `summary` / `challenge` / `approach` / `result` are the single-page hero and story. Refresh live posts with `mh_projects_readable_copy_v1` (already ran once).

## Editor’s notes (3.5.28 Projects CPT)

- Public work lives on the **Projects** CPT (`/projects/`, `/projects/{slug}/`). Do not send that listing back to WooCommerce.
- Convert theme/plugin/app products once (`mh_products_synced_to_projects_v1`). Skip service add-ons. Do not re-import the old studio demo set unless Matt asks.
- Copy is standard portfolio (I/my, Hire me / Say hello). Keep `mh_public_shop_enabled()` false until Matt asks to sell again.
- Home secondary CTA is Browse projects → `/projects/`.

## Editor’s notes (3.5.27 portfolio home)

- Home is the hireable portfolio. `mh_home_hero_default()` restores the name marque, Hire me → `/hire/`, and Browse projects → `/projects/`.
- Keep one primary home button (hero Hire me, closing Say hello). Do not restore the pathway trio, discovery brief, or extra Browse products bars unless Matt asks.
- Do not reintroduce a product-offer H1 or Buy Acreline as the primary home CTA unless Matt asks.
- `mh_home_sales_hero_v1` / `v2` are no-ops. `mh_home_portfolio_hero_v1` rewrites only the 3.5.26 sales strings.
- Product pages still exist in WooCommerce; public visitors are sent to the linked project.

## Editor’s notes (3.5.26 shop-first sales)

- Superseded by 3.5.27 for the homepage. Product guarantee copy: catalog `guarantee` or `mh_product_guarantee_copy()`. Do not invent testimonials or “limited time” language.
- Single product article no longer renders Overview + benefits + story + Woo description. Hidden CSS dumps are gone; the purchase widget calls `mh_render_product_add_to_cart()`.
- Sticky bar still uses `#pf-sticky-bar` + `woo-sticky-bar.js`. Trust chips hide under 640px so Add to cart stays reachable.

## Editor’s notes (3.5.25 About story WYSIWYG)

- About story body is one `about_story` WYSIWYG (`mh_f_about_story`). Do not restore `about_p1`–`about_p4` inputs. Legacy meta migrates into the new field on theme load, then those four keys are removed from the edit screen.
- Front end renders with `mh_about_story_html()` (`wpautop` + `wp_kses_post`). Style paragraphs via `.about-story__body`, not four hardcoded `<p>` tags.

## Editor’s notes (3.5.24 shop catalog padding)

- Shop catalog uses `padding-block` on `.woo-catalog-shell`. Do not set horizontal padding to `0` on that shell — `.container` gutters keep cards off the viewport edge.

## Editor’s notes (3.5.22 My Account desk)

- Account mirrors cart/checkout desk (`woo-desk--account` / `woo-desk--login`): main left, sticky nav + tools right. Do not restore the old full-width pill nav above content.
- Endpoint title/lead/tools come from `mh_account_desk_header()` and `mh_account_desk_tools()`. Keep copy honest (downloads, update email, GPL) — no fake urgency.
- Blade overrides: `woocommerce/myaccount/my-account.blade.php`, `form-login.blade.php`, `partials/woo-account-aside.blade.php`.

## Editor’s notes (3.5.21 About section nav)

- About page sticky pills use `partials/page-nav.blade.php` (same as Home). Do not restore `.about-jump-band` without matching sticky + scrollspy behavior.

## Editor’s notes (3.5.20 shop-first marketing)

- Marketing pages (Home, Hire, Shop, Start, Journal topics) stay outcome-led for shops and agencies. Named stack (Sage, Blade, Vite, Tailwind, PHP 8.3) belongs on **About**, Uses, and light product tech tags.
- Home `#help` uses `home_build_*` fields. Do not restore the old multi-group skill grid without Matt asking.
- Recruiter glance defaults in `mh_recruiter_glance()` must stay shop-first; empty meta falls through to those PHP defaults, not the old Sage parade.
- Portfolio and Code stay developer-facing, but ledes point stack depth at About instead of naming every tool in the hero.

## Editor’s notes (3.5.19 home first impression)

- Hero name is deliberately smaller so role + CTAs clear the first viewport; proof strip uses `home_proof_*` fields.
- If an old long `home_h1` is saved in Page content (theme), clear it so the shorter default (`Matt Hummel`) applies — or edit it in wp-admin.
- Pathways (`#start`) and Receive (`#receive`) are field-driven. Optional `home_receive_video` swaps the mock walkthrough for a real captioned clip.
- Work case cards prefer Acreline → WalkRidge → TOCflow via `mh_home_case_study_cards()`, pulling `challenge` / `approach` / screenshots from the Woo catalog card shape.

## Editor’s notes (3.5.17 home section pills)

- Home “On this page” is sticky with scrollspy pills via `partials/page-nav.blade.php` (`section-nav.js`).

## Editor’s notes (3.5.16 product gallery column)

- “On this page” pills and the long-form article live in the same content box as the screenshot gallery (same width), directly underneath — not a separate narrower column.

## Editor’s notes (3.5.15 product page readability)

- Product H1 uses the global smaller `.display-title.is-hero` scale. Do not bump product titles back to 5rem+ for “impact.”
- Section pills are sticky under the site header (`--header-h`). Keep `scroll-margin-top` on article sections so jump links clear chrome.
- Blog body is `.pf-product-article` / `.container.narrow` (~42rem). Keep long copy there — not in the buy box.
- Hero gallery is the screenshot surface; lightbox still opens from thumbs. Do not restore the full `#screenshots` grid without Matt asking.

## Editor’s notes (3.5.14 downloadable zips)

- Catalog `download` on a product is the GitHub release spec. `mh_apply_catalog_download_file()` attaches it. Do not put zips in the theme repo.
- Account Downloads version column compares `_mh_download_version` on the product with `_mh_purchased_version` on the order line. Downloading the current zip updates the line so the badge clears.
- Buyer update mail is Woo email id `mh_customer_download_update`. Enabled by `mh_seed_download_update_notifications()`. Trigger only when the zip version increases. First attach does not email. `_mh_notified_version` on the line item prevents duplicates.
- GitHub release hosts are added to WooCommerce approved download directories (`mh_approve_product_download_url()`). Without that, attaching a zip fatals and buyers never get the update mail.
- CLI: `wp mh shop-downloads [--force] [--localize] [--no-notify] [--slug=acreline]` and `wp mh shop-notify-updates [--slug=acreline] [--resend]`.
- Live shop host is **matthummel.com**. Full operator guide: `docs/SHOP-DOWNLOADS.md`.


## Editor’s notes (3.5.21 cart/checkout desk)

- Desk layout mirrors journal posts (`woo-desk__main` + sticky `woo-desk__aside`). Do not put order tools back in a full-width trust strip above the form.
- Sidebar FAQ / next-steps / add-ons come from `mh_checkout_sidebar_faq()`, `mh_checkout_next_steps()`, and `mh_cart_sidebar_addons()` — keep them optional and honest (no fake urgency).
- Cart line meta uses `woocommerce_after_cart_item_name` (not the name filter) so screen-reader product names stay clean.

## Editor’s notes (3.5.13 checkout ease)

- Cart/checkout trust lines switch when the cart is services-only (`mh_cart_is_services_only()`). Do not put GPL/zip copy on a Site Care cart.
- Email field stays first. No coupons, countdowns, or fake urgency.
- Empty-cart “Easy start” cards come from live slugs via `mh_checkout_start_here_products()`.
- Install wants: Woo `order_comments` plus `_mh_install_wants` / `_mh_install_note` / `_mh_install_site`. Chip keys are allowlisted in `mh_install_want_catalog()`. Cart suggestions from `mh_cart_suggested_want_keys()`. Optional, never required. Do not add a second funnel.

## Editor’s notes (3.5.11 shop a11y)

- Sale `del` must stay opaque (`--color-text-muted`). Type badges are solid light chips so they read on navy `.mh-product-fallback` plates.
- Sticky buy bar uses `inert` + `aria-hidden` while off-screen. JS moves `#pf-sticky-bar` onto `document.body` so `position: fixed` is not trapped by `html`/`body` `overflow-x: clip`. Gallery thumbs are a roving tablist; Enter selects, the stage button opens the dialog.
- Dual-tone `.btn:focus-visible` (white ring + navy halo) so primary CTAs are visible on `#0d2e57`. Lightbox controls use `#93c5fd`.
- Do not drop `_mh_project_*` metrics, `#buy`, upsells/related, or the Rank Math Services skip.

## Editor’s notes (3.5.10 Services add-ons)

- Services is product-led: `mh_acreline_addon_products()` + `template-services.blade.php`. Do not put the old SEO lede or 4-pillar cards back in the hero.
- `mh_page_skips_seo_analysis_body()` keeps Rank Math from rewriting Services `post_content`. One-shot `mh_services_acreline_page_v1` clears stuffed TOC HTML and exact prior field defaults only.
- Custom floors stay in `svc_price*` fields. Add-on prices come from Woo when the slug exists.

## Editor’s notes (3.5.9 product shop UI)

- Product hero is gallery left / buy box right. Do not put the H1 back in a separate left column or hide the Woo add-to-cart widget in `#buy`.
- Empty product images use `.mh-product-fallback`, not the Woo placeholder PNG. Service type is first-class (`mh_resolve_product_type()`).
- Gallery JS is `resources/js/product-gallery.js`. Lightbox is a native `<dialog>`. Honor `prefers-reduced-motion` on the sticky bar (show it; skip the slide).

## Editor’s notes (3.5.7 dark glossy blue)

- Accent is `#0d2e57` (`#1a5cad` halfway to black). Navy matches. Shine/highlight is `#4f8fd4` on buttons only. Baby blues stay atmosphere.
- Type is Inter 900 on display, larger fluid scale, more section/card air. Do not shrink it back to the 3.5.6 clamps without Matt asking.
- Later home type rules use `!important` around `.h-hero__name` / `.h-section__title` — edit those, not only the early utilities.
- Header brand is the “Matt Hummel” wordmark only. The MH mark stays in the footer (`mh_logo_uri()`, `resources/images/logo-mark.svg`).
- Motion lives in `resources/js/app.js` (`initMagneticButtons`, `initCardTilt`, `initHeroParallax`, `initPresenceReveal`). Honor `prefers-reduced-motion`. No Framer / GSAP.
- Spark teal stays decorative. Primary CTA remains Say hello → `/contact/`.

## Editor’s notes (3.5.6 token lockstep)

- 3.5.6 locked `--color-navy` to `#173e70` (not `--blue-700` / accent-hover `#154a8a`). 3.5.7 remaps navy and accent together to `#0d2e57` in `portfolio.css`, `@theme`, and Gutenberg.
- Home ticker fades must use `--ticker-edge-start` / `--ticker-edge-end` matching the wash, not a leftover `--gray-50` cap.

## Editor’s notes (3.5.5 contrast + polish)

- Light UI stays light. Chrome (`--color-canvas` `#f6f8fc`) is lighter than `#main` (`--color-bg` `#d8e2ed`). Page heroes inherit the panel. Do not flip to dark mode.
- Accent is richer `#1a5cad`. Navy stays `#173e70`. Spark teal `#0e8a7c` is decorative only.
- Tokens live in `resources/css/portfolio.css` `:root`; keep `app.css` `@theme` and `tailwind.config.js` in lockstep.
- `mh_portfolio_polish_copy_v1` only rewrites exact prior home/hire/services field defaults.

## Editor’s notes (3.5.4 hire traction)

- Work listing lives at `/projects/` (301s to `/shop/` via `mh_redirect_legacy_concept_urls`). Do not add `/work/` hrefs.
- One-shot `mh_hire_traction_copy_v1` rewrites stored `/work/` hrefs and exact prior Now/Hire/SLA meta only.
- Services pricing is `svc_price*` Page content fields. Edit ranges in wp-admin; defaults are floors, not a menu.
- Catalog refresh `mh_product_catalog_v8` pushes the TOCflow Plugins install path.

## Editor’s notes (3.1.66 projects catalog)

- `/projects/` is the SEO catalog for themes and plugins for sale (demos + buy). `/shop/` is checkout for the same packs — do not merge the URLs.
- Drop visitor-facing “concept / example gallery” language. Badges are Theme / Plugin / Demo.
- One-shot `mh_projects_catalog_seo_v1` only writes empty or exact prior meta (plus legacy FAQ/fit/how arrays that still say concept).

## Public site

| Feature | Notes | Code |
| --- | --- | --- |
| Marketplace files | `screenshot.png`, `readme.txt`, `CREDITS.md` for Theme Check / Appearance. **Do not** upload this theme to WordPress.org or ThemeForest — see `docs/MARKETPLACE.md` | `docs/MARKETPLACE.md` |
| Vite assets | Hashed files in `public/build/`; deploys keep old hashes so cached HTML does not 404 CSS | `.github/scripts/preserve-vite-assets.py`, `app/cache-headers.php` |
| Profile photo | Customizer upload → GitHub avatar → bundled headshot → Gravatar | `mh_profile_photo_url()`, `partials/profile-photo.blade.php` |
| Home | Full-viewport featured-image hero (white copy panel + wave); recruiter glance; section anchors; skills ticker; audience cards; Hire me primary CTA | `resources/views/partials/home.blade.php`, `partials/recruiter-glance.blade.php`, `App\Github` |
| Marketing pages | Featured-image hero, white copy panel, wavy blend via `partials/page-hero.blade.php` | `template-*.blade.php`, `partials/page-hero.blade.php`, `mh_hero_background_url()` |
| About | Story body is one Page content WYSIWYG (`about_story`); other About sections stay discrete fields | `template-about.blade.php`, `mh_about_story_html()`, `app/page-fields.php` |
| SEO | Per-template `mh_seo_landing_defaults()` titles/descriptions; page fields for overrides; Woo shop titles | `app/filters.php`, `app/page-fields.php` |
| Shared CTA | Sitewide closing band above the footer on marketing + utility pages: mesh/grid atmosphere, high-contrast type, primary + ghost action, trust note, light scroll reveal | `partials/cta-band.blade.php`, `.cta-band` in `portfolio.css` |
| Typography | Fluid Inter display + IBM Plex body, optical letter-spacing, pretty wrapping, comfortable long-form measure | `resources/css/portfolio.css`, `app.css` @theme |
| Now | Dated list of current focus items; studio copy links to the Projects page at `/projects/` | `template-now.blade.php` |
| Projects | Featured project, search, type counts, Grid/List; context + audience + how-to + FAQ; **View details** + **Live demo**; **Projects CPT**; singles have screenshot lightbox, palette, like/star, visitor notes, compact contact | `template-projects.blade.php`, `partials/content-single-project.blade.php`, `partials/project-react.blade.php`, `partials/contact-form.blade.php`, `mh_work_page_fit/how/faq()`, `partials/work-card.blade.php`, `resources/js/work-tools.js`, `resources/js/project-feedback.js` |
| Uses | Stack reference with Page content fields; affiliate disclosure; external link screen-reader labels | `template-uses.blade.php`, `app/page-fields.php` |
| Resources | Catalog with Page content fields; disclosed affiliate links | `template-resources.blade.php`, `mh_resources_catalog()`, `app/page-fields.php` |
| Services | Acreline add-on card grid (live Woo slugs/prices), theme + demo links, shorter custom/hire floors, FAQ | `template-services.blade.php`, `mh_acreline_addon_products()`, `mh_services_pricing()` |
| Code | Open-source GitHub showcase (profile, followers + stargazers thank-you, earned badges, 90-day contrib grid + tips, activity feed, featured/recent repos), practice cards, skills panel, docs cards, hire CTA | `template-code.blade.php`, `App\Github`, `partials/repo-card.blade.php` |
| Hire | Conversion page with LinkedIn profile panel, resume timeline, skills, process, handoff | `template-hire.blade.php`, `App\LinkedIn`, `partials/resume-timeline.blade.php` |
| Journal | Hero search, newest/oldest sort, Grid/List (`data-post-list` on `.post-list`), topics, years, tags, most discussed, numbered pagination, RSS; unique Read more links; source posts in `docs/posts/` as Gutenberg block markup; single-post hero shows featured image beside title/meta; **Tool Blocks** (`matthummel/tool-grid` + `tool-card` with icon/mark/labels) plus `ship-pipe` / `ship-step` | `index.blade.php`, `archive.blade.php`, `partials/content-single.blade.php`, `resources/js/blocks/`, `app/blocks.php`, `resources/css/journal-blocks.css`, `resources/css/editor.css`, `docs/posts/` |
| Single post | Reading progress bar, hero/bottom share (Bluesky, LinkedIn, Facebook, Reddit, copy link), “What changed” collapsible separator (closed by default), inline TOC, desktop sidebar, tags, author bio, post-end CTA (WordPress/full-stack or Power Platform), prev/next, related posts | `single.blade.php`, `partials/content-single.blade.php`, `partials/post-sidebar.blade.php`, `app/social-share.php`, `mh_enhance_what_changed()` |
| Contact | Split form + square elsewhere cards; what to send / what happens next; POST `mh_contact` → n8n CRM webhook (`wp_mail` fallback); same form compact on project pages | `template-contact.blade.php`, `partials/contact-form.blade.php`, `app/contact.php` |
| Search titles / meta | Rank Math title/description win when set (skill-first WordPress wording, no city stuffing); theme page fields and `mh_seo_landing_defaults()` are fallbacks; optional Page content overrides | `app/filters.php`, `seo_title` / `seo_desc`, Rank Math |
| Rank Math page scores | Field-driven pages sync analysis HTML into `post_content` (not shown on the front) and feed fields via the Rank Math Content Analysis API so marketing scores can reach ~80+ | `app/rank-math-fields.php`, `resources/js/admin-rank-math-fields.js` |
| Light mode | Light-only design; `color-scheme: light`; no dark mode toggle | `resources/css/portfolio.css`, `app.css` |
| Site header | Sticky on all viewports; wordmark; primary nav + availability + Say hello; current page underline | `sections/header.blade.php` |
| Mobile menu | Slide-over dialog (`#mh-popout`): Home + primary links, scroll lock, focus trap, Escape close, Menu label | `sections/header.blade.php`, `resources/js/app.js` |
| Project brief | `/start/` stepped discovery form for agencies/shops; CTA on Home + Services process; POST `mh_discovery` → n8n CRM webhook (`wp_mail` fallback) | `template-start.blade.php`, `partials/discovery-cta.blade.php`, `app/contact.php` |
| Comments | ASCII markdown, preview, reply notices; `wptexturize` off so punctuation stays typed; project CPT uses Leave feedback + visitor like/star | `app/comments.php`, `partials/comments.blade.php`, `partials/project-react.blade.php` |
| Code snippets | VS Code Dark+ windows, highlight.js, copy button on post `pre` and `.snippet` | `resources/js/code-blocks.js`, `resources/css/code-blocks.css` |
| Block editor off on pages | Gutenberg disabled on pages; posts keep the block editor; core patterns stripped | `app/bespoke.php` |
| SVG icons | `mh_svg_icon()` — inline SVG with `currentColor` for brand icons | `app/icons.php` |
| WooCommerce | Optional. Theme support + gallery; Blade shop/product templates with gallery+buy layout, section pills + article under the gallery column (same width), crumbs, empty-image fallbacks, related cards; Cart / Checkout / My Account desk (post-style main + sticky tools aside); login desk; SEO titles/meta; a11y focus/notices/tables; compact in-flow notice icons; seed when active (`mh_woocommerce_pages_seeded_v1`); projects sync to virtual products (`mh_woocommerce_project_products_seeded_v1`); header cart when ready; shop grid 3/2/1 without WC float CSS; catalog GitHub zips as downloadable files; Downloads version/update UI; buyer update email | `app/woocommerce.php`, `app/shop.php`, `app/filters.php`, `resources/views/woocommerce/`, `partials/woocommerce-crumb.blade.php`, `partials/woo-desk-aside.blade.php`, `partials/woo-account-aside.blade.php`, `template-woocommerce.blade.php`, `resources/js/product-gallery.js`, `resources/js/section-nav.js`, `resources/js/woo-desk.js`, `portfolio.css`, `generoi/sage-woocommerce` |

## Editor’s notes (3.1.52 AI comparison UX)

- Cards use a 1px grey border and a letter chip (`data-mark`). Do not add a left accent stripe. Blue stays on chips, pills, and links.
- Pipeline (`.mh-ship-pipe`) is numbered cards, not a left timeline bar.
- After merge, Matt must paste `docs/posts/what-actually-gets-faster-with-ai.html` into the live WP post (or publish it — `https://matthummel.com/what-actually-gets-faster-with-ai/` 404s as of this change). Rank Math title: `What AI Speeds Up in WordPress | Matt Hummel`.

## Editor’s notes (3.1.51 AI comparison post)

- New journal post lives in `docs/posts/`, not Blade. Local import: `.github/scripts/import-docs-posts.py`. Production still needs a wp-admin paste (this repo cannot publish matthummel.com).
- Infographic is HTML+CSS in the post body. Classes: `mh-tool-grid`, `mh-tool-card`, `mh-ship-pipe`, `mh-ship-note`. FAQ reuses `.faq-list`.
- Tools named are only ones already on Uses / Home / contact: Cursor, ChatGPT, Claude, Gemini, VS Code, n8n, MCP, GitHub Actions, Vite. No fake time savings.

## Editor’s notes (3.1.50 adjacent work)

- One sentence from `mh_adjacent_range_copy()`. Glance, About intro, Hire, and the home FAQ use it. Do not paraphrase into a skill cloud.
- FAQ question is **Do you only do WordPress?** — tighten that item; do not add a second FAQ.
- One-shot `mh_adjacent_work_copy_v1` is exact-string only (old About services intro).

## Editor’s notes (3.1.49 contrast and layout)

- Tokens: muted/body text is gray-600/800, not gray-500. One blue accent family.
- Glance keeps the hire story; About/Now/footer use the same Power Platform sentence and concept-gallery wording. Do not add a public demo or email.
- Home extra CTAs (about avail card, fit/principles mini-CTAs, FAQ avail card) stay off — glance + header + closing band cover hire.
- One-shot `mh_hireability_visual_v1` is exact-string only.

## Editor’s notes (3.1.48 recruiter hireability)

- Glance sits under the ticker. Named employers and Power Platform copy come from `/hire/` — do not invent extra companies, a demo, a public email, or a resume PDF.
- GitHub featured list is theme-hardcoded: pressroot, matthummel-theme, tocflow, ridgesandvalleys, keepary. Pin those on github.com too. tocflow is not claimed as a WordPress.org listing.
- Work cards use **Theme / Plugin / Demo** badges. Projects is the catalog; Shop is checkout.
- Homepage about strip should not repeat glance facts (years, availability types, Power Platform).
- One-shot `mh_hireability_recruiter_v1` only writes empty or exact prior meta.

## Editor’s notes (3.1.47 home hero SEO)

- H1 keeps the name and adds the primary phrase (`WordPress developer`).
- Role line carries stack + audience (shops / agencies).
- Lede: ownership benefit, stack proof, open-for-work close — three short sentences.
- Empty fields restore via `mh_home_hero_default()` (Blade + `mh_seo_landing_defaults`). Do not pass older H1/role/lede strings into `field()`.
- One-shot `mh_home_hero_seo_copy_v1` only writes empty or exact prior home meta.

## Editor’s notes (3.1.41 home hero)

Above-the-fold is copy left + illustration right. Stats (repos, followers, Remote, Full stack) sit in the illustration only — not under the CTAs. Availability is a status pill on the card. Keep the left column to kicker, name, role, lede, and two actions.

## Editor’s notes (3.1.40 home hero)

- First viewport: name, role, short lede, Hire me + Browse work (+ quiet GitHub). Stats live in the right-column viz panel, not under the CTAs.

## Editor’s notes (3.1.39 hireable + affiliate)

- Portfolio stays primary (Work / Hire / Code / Journal). Shop is Themes only in footer. Resources holds free starters + disclosed affiliates.
- Compensated links: visible Affiliate badge + page disclosure + `rel="sponsored noopener"`.

## Content helpers

| Feature | Behavior |
| --- | --- |
| Page seed | Creates the standard pages and Primary menu once (`mh_portfolio_seeded_v2`) |
| Projects CPT | Restored in 3.5.28. Theme/plugin/app products sync once (`mh_products_synced_to_projects_v1`). Listing page owns `/projects/`; singles own `/projects/{slug}/`. Admin fields, On site toggle, category/place filters. `/concept/` 301s to Projects. Linked Woo products 301 to the project permalink. Service add-ons stay out of the list. Sync playbook: `.cursor/rules/product-theme-sync.mdc` |
| Support docs | `/support/` hub (`template-support.blade.php`) lists sellable products with **HTML-viewable** guides (jsDelivr CDN of GitHub `docs/marketplace/*.html`, same files as the seller Documentation pack); footer link; Acreline catalog docs point at the rendered hub |
| Social defaults | GitHub, LinkedIn, DEV.to, Bluesky, Reddit, RSS |
| DEV.to | RSS cached 3 hours; Journal sidebar thanks followers (API key or curated list); `DEV.to` category; hourly auto-import; export journal → Markdown / DEV.to draft (`wp mh devto-export`) |
| Social share | Post editor drafts (Bluesky / Facebook / Reddit / LinkedIn / DEV.to tips); auto-post Bluesky + DEV.to; frontend share intents | `app/social-share.php`, `app/bluesky-share.php`, Customizer → Bluesky |
| Featured image AI | Post + **Projects** editor **Generate featured image** (DALL·E 3 → Media Library → set thumbnail; Projects also fill Work card screenshot URL); same OpenAI key as DEV.to | `app/featured-image.php` |
| Bluesky | Auto-share journal posts on publish (AI or pasted summary + link); `wp mh bluesky-share` | `app/bluesky-share.php` |
| GitHub | Transients; optional `mh_gh_token` / `MH_GITHUB_TOKEN`; `hireable` + GraphQL status emoji/message drive availability badges |
| LinkedIn | Hire page profile card; optional `mh_li_token` / `MH_LINKEDIN_TOKEN` for OpenID `/v2/userinfo`; soft OG scrape + field/GitHub fallbacks; share URL helper |

## Intentionally not included

- Page builders, Kadence, theme-options admin
- Gutenberg pattern library on pages (posts still use the block editor)
- Fake testimonials or “3x revenue” style landing modules

## Editor’s notes (3.1.36 SEO)

- Marketing and SEO copy is skill-first (WordPress, plugins, web apps). City names are not required on landings.
- Demo Work card places may still name a town; titles/meta/kickers should not stuff location.
- One-time meta reset: `mh_seo_global_copy_v1` clears saved `mh_f_*` fields that still contain Gettysburg / Adams County.

## Editor’s notes (3.1.x Projects CPT)

- Home hero stays short; kicker leads with craft, not a city.
- Dropped “client” on agency cards and overflow FAQ; kept the agency relationship meaning.
- Did not copy the live About title’s “15+ years” claim into the theme.

## Editor’s notes (3.0.19 copy)

- Split services lede so the Power Platform aside is one sentence, not two.
- Swapped “clients” for “shops” on services fit copy and the Work band (glossary).
- Subject–verb on the contact hint: “sentences are.”
- Combined the two fragment closers on About into one sentence.
- Hero CTAs: filled button is the contact action; GitHub is a text link, not a third button.

## Dev tools

| Command | What it does | Code |
| --- | --- | --- |
| `wp mh theme-update` | Download and install the `theme-latest` GitHub Release zip over HTTPS | `app/theme-updater.php` |
| `wp mh theme-build` | Trigger a new CI build (dispatch `deploy.yml`) | `app/theme-updater.php` |
| `wp mh db-pull` | Export prod DB via SSH → import locally → search-replace URLs | `app/db-migrate.php` |
| `wp mh db-push` | Export local DB → upload to prod via SSH → import + search-replace (requires `--yes`) | `app/db-migrate.php` |
| `bash .github/scripts/db-pull.sh` | Shell-only db-pull (no WP bootstrap required) | `.github/scripts/db-pull.sh` |
| `vendor/bin/pint --test` | Check PHP code style (Laravel Pint) | `pint.json` |
| `npm run build` | Build Vite assets into `public/build/` (gitignored) | `vite.config.js` |
| `wp acorn view:clear` | Clear compiled Blade views (run after any template edit) | — |

SSH credentials for `db-pull` / `db-push` resolve in this order:
1. `--ssh-*` WP-CLI flags (`--ssh-host`, `--ssh-user`, `--ssh-path`, `--ssh-identity`, …)
2. `MH_SSH_HOST`, `MH_SSH_PORT`, `MH_SSH_USER`, `MH_SSH_WP_PATH`, `MH_SSH_IDENTITY_FILE`, `MH_SSH_KEY_PASSPHRASE` constants in wp-config.php
3. Env vars: `MH_SSH_HOST` / `SERVER_IP`, `MH_SSH_PORT` / `SERVER_SSH_PORT`, `MH_SSH_USER` / `SERVER_USER`, `SERVER_DESTINATION_PATH` / `LIVE_WP_PATH`, `SERVER_SSH_IDENTITY_FILE`, `SERVER_SSH_PRIVATE_KEY_PASSPHRASE`

Production hosting is Hostinger (`matthummel.com`). Prefer hPanel backups and WPVibe / `wp mh theme-update` over SSH db-push. Legacy `SITEGROUND_*` env names are not used for deploy.

Passphrase-protected keys need `SERVER_SSH_PRIVATE_KEY_PASSPHRASE` (or an unencrypted deploy key). Cloud Agents often write the key to `~/.ssh/id_ed25519_sg` — that path is auto-detected.

## Stack

Sage 11.2.1 · PHP 8.3 · Tailwind v4 · Vite 8 · Acorn 6 · WordPress 6.6+ · Inter + IBM Plex Sans
