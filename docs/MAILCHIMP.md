# Mailchimp signup setup (matthummel.com)

How newsletter signup is wired on the live site. Most of this lives in WordPress and Mailchimp, not in the theme, so this file is the record. Brand, tags and email rules: `.claude/rules/mailchimp.md`.

## Mailchimp

- **Data center / audience:** `us21`, audience **Matt Hummel**, id `922cd65ed4` (web id 318116).
- **From / reply-to:** Matt Hummel, `hello@matthummel.com`.
- **Footer (CAN-SPAM):** PO Box in Gettysburg, PA 17325. The box number is a placeholder (`PO Box 1`) until the real one is set in Audience, Settings, Required email footer content.
- **Opt-in:** double opt-in.
- **Merge fields:** `FNAME`, `COMPANY`, `WEBSITE`, `WORKING` (What are you working on).
- **Embedded form record:** "Website signup (custom styled)", `f_id` `00ca9ee6f0`. The live forms are custom HTML that post to the same endpoint.
- **System forms:** "Code system forms from scratch" is on. These pages are small redirects:
  - Signup thank you page redirects to `/subscribed/`
  - Confirmation thank you page redirects to `/welcome/`
  - Unsubscribe success page redirects to `/unsubscribed/`
  - To undo: Signup forms, Settings, Advanced settings, turn off "Code system forms from scratch" (Mailchimp restores its default pages).

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

## Known gaps

- The privacy page text still lists HubSpot and the Meta Pixel as carried over from the old template. Confirm they are in use.
- `template-privacy.blade.php` is no longer used by any page.
- The opt-in confirmation, final welcome and goodbye emails are branded (light mode, Inter and IBM Plex, one navy button). The confirmation and welcome emails keep Mailchimp's confirm and unsubscribe/update-profile links. The goodbye email links back to `/get-updates/#signup` to rejoin. The Mailchimp "Signup form" and "Update profile" pages are still Mailchimp defaults.
