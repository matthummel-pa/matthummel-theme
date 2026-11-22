---
name: gtm-setup
description: Build or audit the Google Tag Manager container (GTM-5H3KSG9X) and GA4 tracking for matthummel.com using Claude in Chrome. Use when asked to set up tags, triggers, variables, GA4 events or conversions, or to verify tracking.
---

# GTM and GA4 setup (Claude in Chrome)

Read `.claude/rules/gtm-ga4-analytics.md` first. It owns event names, parameters and the Blade/JS side. This skill is the container side.

## Ground rules
- Work in the Default Workspace of **GTM-5H3KSG9X** (matthummel.com, account "Matt Hummel") at tagmanager.google.com. Not `matthummel.co` (GTM-M4PLTQH), not the other accounts.
- Drafting tags, triggers and variables in the workspace is fine. **Do not click Submit / Publish** without Matt's yes, and say what it will make live.
- Never edit GA4 property data streams, filters or account access without asking. Enabling Google signals, ads linking and data retention are Matt's calls.
- The container only does something once its snippet is on the site. Check the site for it (`google_tag_manager` in the console, or Tag Assistant) before calling tracking verified.

## Build order
1. **Constant variable** `GA4 Measurement ID` = the property's `G-XXXXXXX` (GA Admin, Data streams). Do not guess it.
2. **Data layer variables** (Version 2), names exactly as pushed: `user_role`, `page_location`, `signup_source`, `theme_name`, `doc_type`, `cta_text`, `cta_location`, `question_text`, `link_url`, `tool_name`, `article_slug`, `platform`, `plan_tier`, `currency`, `value`, `item_name`, `ecommerce`.
3. **Google tag** (type: Google tag) with the Measurement ID, trigger Initialization - All Pages. Consent settings: require `analytics_storage`.
4. **Custom Event triggers**, one per event in the rule (`generate_lead`, `sign_up`, `begin_checkout`, `purchase`, `view_item`, `cta_click`, `demo_view`, `docs_view`, `faq_expand`, `affiliate_click`, `social_or_repo_click`). Exact match on the event name. Click triggers use `data-track` attributes, never CSS classes.
5. **GA4 Event tags**, one per trigger, named `GA4 - <event_name>`, with event parameters mapped from the data layer variables. Ecommerce events use "Send Ecommerce data" from the data layer.
6. **Preview** (Tag Assistant) against the staging site: `https://matthummel-com-623249.hostingersite.com/`. Confirm each tag fires once and carries its parameters.
7. In GA4, mark `generate_lead`, `sign_up` and `purchase` as key events (Admin, Events). Register custom dimensions for `user_role`, `signup_source`, `theme_name`, `cta_location` (event scope).
8. Confirm in GA4 DebugView, then ask Matt before Submit.

## Checks
- No double pageviews: Site Kit's GA4 snippet is off.
- Admins are excluded by the PHP injection (log in as admin: no container in source).
- Tag names are `GA4 - <event_name>`, triggers `CE - <event_name>`, variables `DLV - <key>`. Keep the container tidy; no unused items.
- Export the container JSON after a publish and note the version in `CHANGELOG.md`.
