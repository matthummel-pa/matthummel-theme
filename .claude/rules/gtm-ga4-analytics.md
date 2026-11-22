---
paths:
  - "resources/views/**/*.blade.php"
  - "resources/scripts/**/*.js"
  - "resources/js/**/*.js"
  - "app/**/*.php"
---

# Google Tag Manager and GA4 analytics rules

Container for this site: **GTM-5H3KSG9X** (matthummel.com, account "Matt Hummel"). Build and change the container with the `gtm-setup` skill.

## Architecture and script injection (non-negotiable)
- **One container only.** Never hardcode standalone GA4 (`gtag.js`), Google Ads or third-party pixels in Blade or PHP. Every tag loads through the single GTM container.
- **Site Kit must not also send GA4.** Site Kit's GA4 snippet plus a GA4 tag in GTM counts every pageview twice. Turn off Site Kit's Analytics module (or its snippet) in the same release that adds the container. `resources/js/track.js` already skips `gtag()` when a container is present.
- **Sage 11 hook placement.** Inject the GTM `<head>` snippet on `wp_head` at priority `1` and the `<noscript>` iframe on `wp_body_open`, in `app/setup.php`. Read the container ID from a constant or filter, not a literal scattered in templates.
- **Exclude admins.** Wrap injection in `if (! current_user_can('manage_options'))` so logged-in admin activity stays out of the data.
- **Consent Mode v2.** Print `gtag('consent', 'default', {...})` with denied defaults before the GTM snippet so analytics and ad storage respect the visitor's choice.
- **Performance.** No synchronous scripts and no heavy third-party heatmaps. Keep Core Web Vitals and WCAG 2.2 AA intact.

## DOM instrumentation (`data-*` attributes)
- **No fragile selectors.** Never bind GTM triggers to Tailwind utility classes, tag names or DOM hierarchy. Put semantic `data-*` attributes in Blade.
- Required attributes:
  - `data-track="<event_name>"`: the primary hook for GTM click and submit listeners.
  - `data-track-category="<context>"`: section or funnel context (`hero`, `theme_card`, `services_grid`, `faq`, `footer`).
  - `data-theme="<theme_slug>"`: on every theme card, plan selector, live demo link and owner guide link (`cobble_and_candle`, `walkridge`, `acreline`).

## Naming and parameters
- **Strict `snake_case`** for every custom event name and parameter. No camelCase, spaces or capitals.
- **Standard event schema.** Keep or apply these exact names and parameters when you add or change interactive UI:
  1. `generate_lead`: `/contact` form submissions. Pass `user_role` (the "Who you are" select) and `page_location`.
  2. `begin_checkout` / `purchase`: theme shop plan selection and checkout completion. Pass `currency` (`USD`), `value`, `item_name`, `plan_tier`.
  3. `sign_up`: newsletter ("Get updates") and coming-soon waitlists. Pass `signup_source` (`footer`, `walkridge_waitlist`, `acreline_waitlist`, `journal_post`).
  4. `demo_view`: outbound clicks to theme live demos. Pass `theme_name`.
  5. `docs_view`: clicks to Owner Guides, `/changelog` or `/release-watch`. Pass `theme_name` and `doc_type`.
  6. `cta_click`: primary conversion buttons ("Tell me about your business", "Browse themes", "See custom websites", "See custom features"). Pass `cta_text` and `cta_location`.
  7. `faq_expand`: opening a `<details>`/`<summary>` FAQ item. Pass `question_text`.
  8. `affiliate_click`: outbound clicks on labeled affiliate tool links. Pass `link_url`, `tool_name`, `article_slug`.
  9. `social_or_repo_click`: clicks to GitHub, RSS, LinkedIn, DEV.to, Bluesky or Reddit. Pass `platform`.

## `dataLayer` pushes (custom JS and REST forms)
- Use `trackEvent()` and `trackEcommerce()` from `resources/js/track.js`. They push to `window.dataLayer` and keep GA4 ecommerce clean (`{ ecommerce: null }` first). Do not write a second helper.
- For async forms (`app/Rest/SubscribeController.php` newsletter, `/contact` AJAX), push only after a `200 OK` response, never on the button click alone:

```js
import { trackEvent } from './track'

// inside the fetch().then(response => ...) success branch
trackEvent('sign_up', { signup_source: source })
```
- Never put names, email addresses or any personal data in event parameters.
