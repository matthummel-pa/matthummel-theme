# Newsletter and funnel review (October 2026)

What the signup-to-client path looked like at 3.7.14, what 3.7.15 / plugin 1.12.0 changed, what still needs a decision, and which pages would keep readers coming back. No invented numbers; the live site was not reachable from the build environment, so the "live" column is what the repo says ships.

## Where the funnel stood

| Step | At 3.7.14 | Gap |
| --- | --- | --- |
| Find | Journal, DEV.to cross-posts, GitHub, Bluesky | Fine. Content calendar lives in `docs/GROWTH-PLAN.md`. |
| Capture | Footer form (Hostinger Reach widget since 3.7.11), Get updates page, end-of-post form, signup block | Footer addresses go to Reach; every other form goes to the self-hosted plugin. Two lists, two confirm emails, no shared count. |
| Confirm | Double opt-in, confirm page collects a name | Confirm landed on a bare page with one "Back to the site" link. |
| Deliver the promise | End-of-post form says "free WordPress Handoff Checklist" | Nothing sent it. The Welcome layout and the checklist link existed in Settings, but no code sent a welcome letter after Confirm. |
| Nurture | Blog update / digest / custom letters, auto-draft on publish, send and schedule | Good. No welcome, no "start here", no way to see which form a reader came from. |
| Measure | Opens and clicks (off by default), delivered/failed counts | Letter links carried no UTM tags, so site analytics could not separate email visits from search. |
| Convert | "Say hello" on every page, Start (project brief) form, thank-you page | Thank-you page did not offer the list. A contact lead who is not ready to hire had no second step. |
| Return | RSS, related posts, popular posts | Journal page's subscribe section still said "There is no email list." No public archive. |

## What 1.12.0 / 3.7.15 changed

1. **Welcome letter sends itself** after Confirm, with the checklist button and three latest posts. Setting: After signup → first box (on). "Send me the welcome letter" mails a test copy.
2. **Confirm lands on `/get-updates/?signup=confirmed`**: thanks, checklist link, three posts, "reply and tell me what you are building."
3. **UTM tags** on links back to this site in every letter (`utm_campaign` = issue slug, or `welcome`).
4. **Past issues** public archive at `/newsletter/`, off by default. Each issue has its own URL and a canonical tag.
5. **Forms where readers are**: post sidebar, journal page, contact thank-you page. Each reports its source; Subscribers shows a Source column.

## Decision needed: Reach or the plugin for the footer

The footer is the most-seen form on the site and it currently feeds Hostinger Reach, while posts, the journal page, the sidebar, and the thank-you page feed the self-hosted list. Pick one.

**Keep the plugin everywhere (recommended if deliverability holds).** Put the plugin's Newsletter signup block in Appearance → Widgets → Footer: Get updates, or empty that widget area so the theme's fallback form prints. One list, one welcome letter, one archive, one Source column, addresses stay on the site, and the checklist promise is kept by code you own. Cost: you own deliverability. Before relying on it, set the From address to a mailbox on matthummel.com, publish SPF, DKIM, and DMARC, and install an SMTP plugin if Hostinger's `wp_mail` is not delivering. Send yourself the welcome letter and check it lands in Primary, not Spam.

**Move everything to Reach.** Reach sends from Hostinger's infrastructure and has its own automations (Hostinger says Reach offers single or double opt-in and drip sequences; see [hostinger.com/reach](https://www.hostinger.com/reach) and the [week 8, 2026 product update](https://www.hostinger.com/blog/?p=10329)). Cost: the welcome letter, the confirmed page, UTM tags, the archive, and the Source column in this repo stop applying, the addresses leave the site, and the privacy line "I keep the address on this site" has to change on every form and in `docs/NEWSLETTER.md`.

Running both is the worst option. A reader who signs up in the footer never gets the checklist the post form promised, and you cannot send one letter to everyone.

## Still open (not built, in rough priority)

- **Deliverability check.** Nothing in the repo proves `wp_mail` delivers from Hostinger. One test send to a Gmail and an Outlook address settles it. Headers to confirm: SPF pass, DKIM pass, `List-Unsubscribe` present (the plugin already sets it).
- **Second welcome email.** Experts send a short follow-up two to four days after the welcome ("the one post most people start with" plus a reply prompt). The plugin has no delayed sends per subscriber. Simplest path: a `welcome_followup` setting plus a daily cron that mails subscribers confirmed 3 days ago who have no `welcome_2` event. Worth doing once the first letter is proven to land.
- **Re-engagement and sunset.** With opens on, a yearly "still want these?" to anyone with no open or click in 12 months, then unsubscribe silence. Keeps the list honest and protects sender reputation. Needs tracking on, which is a privacy call.
- **Bounce handling.** `failed` events are logged, but a hard bounce from the receiving server arrives later and is not read. A sunset rule on repeated `failed` events (3 in a row → unsubscribed, note in Subscribers) is cheap.
- **Resend to non-openers.** Only sensible with opens on and a list large enough to matter. Skip for now.
- **A/B subject lines.** Same. Skip until the list is in the thousands.

## Page options that keep readers coming back

Ranked by effort against what each one does for the funnel. All of them are block pages (no new `mh_f_*` fields), written in wp-admin.

| Page | What it does | Effort | Notes |
| --- | --- | --- | --- |
| **Start here** (`/start-here/`) | One page a new subscriber or first-time visitor can read in five minutes: who the site is for, the five posts to read first, the checklist, and one Say hello. Link it from the welcome letter and the confirmed panel. | Low | Blocks: Hero, Section, Journal list (hand-picked), CTA band. Replaces the "Start with these" list on the confirmed panel once it exists. |
| **Past issues** (`/newsletter/`) | Proof before the address. One URL per letter for search. | Done, off by default | Turn on under After signup. |
| **WordPress Handoff Checklist** (`/wordpress-handoff-checklist/`) | The lead magnet as a real page, not only a PDF: the checklist in HTML, printable, with the form at the bottom. The footer already links it when published. | Low | Point Settings → Checklist link at this page (or a PDF on it). Set it noindex only if you want the email to be the sole way in; indexing it usually wins more signups. |
| **Topic hubs** (`/blog/sage/`, `/blog/gutenberg-blocks/`) | Category pages with a written intro, the best three posts pinned, and the form. Categories exist; the intro and pinned posts do not. | Medium | Theme change: a category description block and a "start with" meta on `archive.blade.php`. Good search targets ("Sage 11 WordPress theme"). |
| **Uses** (already on Resources `#uses`) | Developers return to see what changed in the stack. | Done | Add a "last updated" line (Last updated block exists). |
| **Now** (on About) | Already ships. | Done | Link it from the welcome letter's sign-off. |
| **Office hours / Book a call** | A monthly 20-minute slot for shops and agencies. Converts warm readers who will not write a brief. | Medium | `partials/booking-link.blade.php` exists; needs a calendar link in Settings and a page. Only if you want the calls. |
| **Changelog** (`/changelog/`) | Ships. Add it to the digest template as a one-line "what changed on the site" so the letter has something new even without a post. | Low | Plugin: a Site changes block in the digest starter. |
| **Reader questions** (`/ask/`) | A short form: "what are you stuck on in WordPress?" Each answer becomes a post and a letter. The best source of post ideas and a reply channel that is not the contact form. | Medium | Reuses the contact handler with a `who=learning` default and a different thank-you. |
| **Free starters index** (`/starters/`) | Resources already lists them; a dedicated index with a form after each starter converts developers. | Low | Blocks: Resource catalog filtered. |

Order to do them: Start here, then the checklist page, then topic hubs. The first two are an afternoon each and both feed the welcome letter directly.

## Checks run for 3.7.15

- `php -l` on every changed PHP file, `pint --test` on `plugins/matthummel-newsletter/includes` and `app` (clean).
- `php plugins/matthummel-newsletter/bin/check-email.php` (all pass).
- `npm run build` (clean).
- `wp-review` on the diff: 0 errors; 3 warnings for `{!! do_shortcode('[mhn_signup …]') !!}`, the same pattern `content-single.blade.php` already uses. The plugin escapes that markup.
- Not run: PHPCS (Composer could not reach GitHub from the build container) and a live WordPress (no WordPress download available there). A manual pass is still needed: sign up, confirm, check the welcome letter on a phone, turn on Past issues and open one issue, and tab through the sidebar and thank-you forms.
