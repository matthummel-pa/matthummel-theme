# Mailchimp signup setup (matthummel.com)

How newsletter signup is wired on the live site. Most of this lives in WordPress and Mailchimp, not in the theme, so this file is the record. Brand, tags and email rules: `.claude/rules/mailchimp.md`.

## Mailchimp

- **Data center / audience:** `us21`, audience **Matt Hummel**, id `922cd65ed4` (web id 318116).
- **From / reply-to:** Matt Hummel, `matt@matthummel.com`.
- **Footer (CAN-SPAM):** PO Box in Gettysburg, PA 17325. The box number is a placeholder (`PO Box 1`) until the real one is set in Audience, Settings, Required email footer content.
- **Opt-in:** double opt-in.
- **Merge fields:** `FNAME`, `COMPANY`, `WEBSITE`, `WORKING` (What are you working on).
- **Embedded form record:** "Website signup (custom styled)", `f_id` `00ca9ee6f0`. The live forms are custom HTML that post to the same endpoint.
- **System forms:** "Code system forms from scratch" is **off**, so Mailchimp uses its standard forms styled by the visual designer (Signup forms, Settings, Form builder, Design it). The code written earlier is saved by Mailchimp and returns if you turn code mode back on (see "Custom-coded mode" below).

## Tag ids

Tags are sent as one comma-separated hidden `tags` value.

| Tag | Id |
| --- | --- |
| audience:shop-owner | 3017691 |
| audience:agency | 3017692 |
| audience:developer | 3017693 |
| product:cobble-and-candle | 3017694 |
| product:walkridge | 3017695 |
| product:acreline | 3017696 |
| product:tocguide | 3017697 |
| interest:release-watch | 3017698 |
| interest:journal | 3017699 |
| source:footer | 3017700 |
| source:get-updates | 3017701 |
| source:shop-waitlist | 3017702 |
| source:freemius-buyer | 3017703 |

## Live WordPress

| Where | What |
| --- | --- |
| Appearance, Widgets, **Footer updates** area, widget `block-12` | Shared form CSS (`.mhmc*`), the footer email form, the Get updates pop-up (`<dialog id="mhmc-pop">`), and the submit script. |
| `/newsletter-profile/` (page 1556) | Full questionnaire form. |
| `/get-updates/` (page 1475) | Basic form in the `#signup` section. |
| `/subscribed/`, `/welcome/`, `/unsubscribed/` | Check inbox, confirmed (welcome), and unsubscribed pages. `/unsubscribed/` has a rejoin form and pre-filled reason links. |
| `/privacy/` (page 25) | Block content (not the theme template). Names Mailchimp as the email provider. |

Page forms rely on the CSS in the footer widget. If that widget is removed, the forms lose their styling.

## How submit works

1. The script adds the chosen role and interest tags to the hidden `tags` field.
2. It sends the form to Mailchimp's `post-json` endpoint (JSONP) so the visitor stays on the site, then redirects to `/subscribed/`.
3. If Mailchimp answers `captcha`, or the request fails or times out, the script submits the form normally. Mailchimp then shows its own "Confirm Humanity" page.

The header **Get updates** link opens the pop-up on click. Without JavaScript it goes to `/get-updates/`.

## Email subject lines (standard mode)

Set in the form builder (Signup forms, Settings, Form builder, pick the email, Build it). From name and from/reply-to follow the audience defaults (`matt@matthummel.com`).

- Opt-in confirmation: "Confirm your email to join the list"
- Goodbye: "You are unsubscribed"
- Welcome: Mailchimp's standard mode has no final welcome email in the form builder. The welcome message is an automation: "Welcome new contacts" (flow id 6321), trigger "Contact signs up to Matt Hummel", one email "Welcome email" sent as soon as possible from Matt Hummel <matt@matthummel.com>. Subject "You are on the list: your update checklist", preview text "Your WordPress release-prep checklist, plus three posts worth reading first." Body is custom-pasted HTML (light mode, 600px): Bluesky cover banner, blue/cyan/lime stripe, free-PDF card, three numbered posts, "Meet Matt" with the family photo, what-to-expect box, social icon chips (GitHub, LinkedIn, DEV.to, Bluesky; PNGs in the media library as `mh-email-icon-*`, ids 2176-2179, from Bootstrap Icons (MIT) and Simple Icons (CC0)), and a footer with `*|UPDATE_PROFILE|*`, `*|UNSUB|*` and `*|LIST:ADDRESSLINE|*`. Images are the site WebP files (Outlook desktop will not show them; alt text covers it). Source copy: scratchpad `welcome-email.html`. To edit it, pause the flow first (a live flow locks its email).  The older dark "Journal - Welcome email" template is unused.

(The custom-coded subjects were "You are on the list: your update checklist" for the welcome email, and the same two as above.)

## Branded Mailchimp-hosted pages

The visual designer styles every hosted page at once (global, colors and basic fonts only; no gradients, no custom CSS). Current values: page background `#f1f5f9`, card `#0b1b35`, body text `#b7c6dc`, links `#67e8f9`, buttons lime `#a3e635` with navy `#0a1a33` text (hover `#bef264`), labels `#f1f5fb`, field text `#0f172a`, required `#22d3ee`, help `#9fb2cf`, errors `#fbbf24`.

- The page header ("MattHummel.com") has no color setting and renders black, so the page background stays light so it can be read. A dark page background makes it invisible.
- Applies to the "Confirm Humanity" CAPTCHA page, the unsubscribe page, the confirmation thank-you page, and the signup form.

### Custom-coded mode (turned off)

Earlier, "Code system forms from scratch" was on. That gave the site look with gradients, plus three redirects: signup thank-you to `/subscribed/`, confirmation thank-you to `/welcome/`, unsubscribe success to `/unsubscribed/`. It also carried branded HTML versions of the confirmation, welcome and goodbye emails. Mailchimp says the code is saved. Turning it back on restores that, but it cannot style the "Confirm Humanity" page, so the two are a trade-off. The `/subscribed/` redirect after a normal footer signup still works either way, because the site script handles it.

## Known gaps

- The privacy page text still lists HubSpot and the Meta Pixel as carried over from the old template. Confirm they are in use.
- `template-privacy.blade.php` is no longer used by any page.
- With code mode off, the opt-in confirmation, welcome and goodbye emails use Mailchimp standard text, not the branded HTML. The audience from/reply-to is `matt@matthummel.com`. After a confirm click or an unsubscribe, visitors see Mailchimp pages, not `/welcome/` or `/unsubscribed/`.

## Site newsletter pages

The Bluesky cover (media id 1613) sits under the hero on `/newsletter-profile/`, `/get-updates/`, `/subscribed/`, `/welcome/` and `/unsubscribed/`. `/welcome/` also has a "Meet Matt" section with the family photo (media id 1566).

## Journal email template

Saved template "Journal - Light update" (template id 10574778) is the light-mode base for journal-post emails. It matches the welcome email: Bluesky cover banner, blue/cyan/lime stripe, icon chips, profile-photo sign-off. Reader aids: date and "View in browser" bar, a "Short version" box (what changed, who it affects, what to do), a reading-time eyebrow, "Try this week", three numbered "More to read" rows, a fixed "Keep these handy" list (release-prep checklist PDF, handoff checklist, release tracker), an "Ask me" reply prompt, and a footer with forward, update-preferences, unsubscribe and address. Editable regions (`mc:edit`): eyebrow, headline, body, short_version, button, try_this_week, post1-3, signoff. "Journal - Newsletter" (template id 10574777) is now the same light design, built around Issue 01 (WordPress 7.2 release prep): date bar, reading-time eyebrow, short version, a release schedule table (RC3 Nov 24, RC4 Dec 1, dry run Dec 7, release Dec 8, taken from the earlier draft and the site tracker, so confirm before sending), the checklist PDF card, "Try this week", three recent posts with thumbnails, resources, an "Ask me" question with the `*|FNAME|*` merge, and a footer with forward, preferences, unsubscribe, privacy, terms and accessibility links. Editable regions: eyebrow, headline, body, short_version, schedule, button, try_this_week, posts, question, signoff. Only "Journal - Welcome email" is still the older dark design and is unused.
