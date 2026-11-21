---
paths:
  - "**/*mailchimp*"
  - "**/*newsletter*"
  - "emails/**"
  - "docs/mailchimp*"
---

# Mailchimp branding, copy and template rules (matthummel.com)

Applies to any Mailchimp template, campaign copy, signup form, automation or API script. Site copy rules stay in `content.md`; this file covers email only.

## 1. Brand identity and design tokens

- **Brand:** Matt Hummel (`matthummel.com`). **Sender name:** Matt Hummel.
- **Reply-to / support:** `hello@matthummel.com`.
- **Footer address:** Gettysburg, PA (required for CAN-SPAM).
- **Light mode only.** Never generate a dark-background email shell.
- **Type stacks (email-safe fallbacks):**
  - Headings: `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif`. Weights 600-800, tracking `-0.02em`.
  - Body: `'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif`. Weight 400/600, `16px` base, `1.6` line-height.
  - Code, metadata, eyebrows: `'IBM Plex Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace`. `13px`-`14px`.
- **Colors (WCAG 2.2 AA):**

| Role | Hex | Note |
| --- | --- | --- |
| Outer canvas | `#F8FAFC` | Slate 50 |
| Email card surface | `#FFFFFF` | |
| Primary ink (headings, body) | `#0F172A` | Slate 900 |
| Secondary / muted text | `#475569` | Slate 600, at least 4.63:1 on `#FFFFFF` |
| Accent and CTA fill | `#0F172A` (white text) or `#1D4ED8` | Blue 700, at least 6.7:1 with `#FFFFFF` |
| Hairlines, dividers | `#E2E8F0` | Slate 200 |
| Code / callout surface | `#F1F5F9` | Slate 100 |

## 2. Accessibility and HTML email constraints

- **Contrast:** all text above 4.5:1. Body links keep `text-decoration: underline` (never color-only).
- **Touch targets:** CTA buttons and footer links at least `44px` tall (`padding: 12px 24px`).
- **Layout:** single column, `600px` max, table-based with `role="presentation"` for Outlook and Gmail. Fluid on mobile (`width: 100%`).
- **Mailchimp tags and regions:**
  - Always include the footer tags `*|UNSUB|*`, `*|UPDATE_PROFILE|*`, `*|LIST:ADDRESSLINE|*` and `*|CURRENT_YEAR|*`.
  - Wrap editable blocks in `mc:edit="section_name"` and repeatable blocks in `mc:repeatable`, so the template works in the Classic and Code editors.
  - Inline all CSS, or embed styles in `<head>` for Mailchimp's inliner.

## 3. Editorial voice and copy

- **Quality gate:** never write copy Matt would not forward to a paying client without edits.
- **No hype, no jargon.** Plain words, short sentences, active voice. Explain WordPress concepts (block themes, PHP 8.3, caching) as business outcomes for shop owners.
- **Standard journal / update email, in order:**
  1. **Eyebrow** (monospace) naming the reader: `FOR: SHOPS & SITE OWNERS`, `FOR: AGENCIES`, `FOR: DEVELOPERS` or `FOR: EVERYONE`.
  2. **Headline.** Sentence case, concrete, 8-10 words max.
  3. **Body.** 3-5 short paragraphs of 2-3 sentences.
  4. **Primary link.** One clear link (underlined in text) plus one primary button.
  5. **"Try this week."** One concrete, same-day takeaway.
- **Sign-off:** `— Matt` / `Matt Hummel · WordPress Developer in Gettysburg, PA`.
- No invented metrics, testimonials or client names (core rule).

## 4. Audience architecture and tags

Use **one master audience, `Matt Hummel`**, segmented by **tags**. Never split into separate audiences. Apply this to API scripts, signup forms and automations.

- **Role:** `audience:shop-owner`, `audience:agency`, `audience:developer`
- **Product / interest:** `product:cobble-and-candle`, `product:walkridge`, `product:acreline`, `product:tocguide`, `interest:release-watch`, `interest:journal`
- **Source:** `source:footer`, `source:get-updates`, `source:shop-waitlist`, `source:freemius-buyer`

Keep these tag strings exact. A new tag is a change to this file first.
