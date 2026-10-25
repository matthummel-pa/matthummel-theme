---
paths:
  - "resources/views/**"
  - "resources/blocks/**"
  - "resources/views/blocks/**"
  - "resources/js/blocks/**"
  - "app/page-fields.php"
  - "app/blocks.php"
  - "docs/**/*.md"
---

# Content: where copy lives, and how it sounds

Single source for copy location and voice. Other rules point here; do not restate this in them.

## Where copy lives

- **Visitor-facing copy is block content in `post_content`.** Core blocks (paragraph, heading, list, image, buttons, columns, group, cover) sit inside the theme's page blocks.
- Theme blocks: `resources/blocks/<name>/block.json` (apiVersion 3, dynamic) → `resources/views/blocks/<name>.blade.php` (PHP render, escape everything) → `resources/js/blocks/page/<name>.js` (editor: InnerBlocks, `allowedBlocks`, a default `template`). Theme blocks own layout and design tokens; core blocks own the words.
- Blade page templates render chrome (`layouts/`, `sections/`, `partials/`) and `the_content()`. No prose in Blade except UI labels (`Say hello`, `View code`, `Journal`).
- Default copy for a new block goes in the block's editor `template` or `example` in `block.json`, not in PHP arrays.
- No Custom HTML blocks, block patterns, page builders, ACF, or Kadence.

## Legacy fields (frozen)

Pages not yet converted (3.7.0 migration, see `docs/FEATURES.md`) still read **Page content (theme)** through `\App\field()` and `app/page-fields.php`. As of this rule, these templates still call `field()`: `template-get-updates`, `template-portfolio`, `template-services`, `template-start`, `template-support`, `template-woocommerce`, plus a few partials and block views.

- **Do not add new `mh_f_*` keys or new `\App\field()` calls.** Fix existing copy in place only until that page's conversion PR lands.
- Converting a page deletes its fields in the same PR. Follow the 3.7.0 notes in `docs/FEATURES.md`.

## Editing copy

- A copy change is an edit in the block editor (staging first), not a PR. A PR touches copy only to seed a page, change a block's default template, or fix UI chrome.
- Posts: block editor. Put the language class on `<code>` (`language-php`, `language-js`) so highlight.js runs.
- Titles and meta come from the WordPress document title and Rank Math. Title under 60 characters, `Topic | Matt Hummel` (drop the suffix if the title already says Matt Hummel). Meta under 155 characters, first person, says what the page offers. Never a second `<title>` stack in Blade.

## Voice

- First person singular (I / my / me). Never we / our, except quoting a team or WordPress itself.
- Glossary: shops, agencies, developers, learners. Not customers, users, or clients unless that wording is already in the text.
- UI labels in sentence case. Page H1s are short titles (`About`, `Work`). H2 may be Title Case as a label; H3 and body in sentence case.
- Paragraphs: 3 sentences max on landing pages, 4 in posts. Mix short punches with medium sentences. Active voice.
- One primary CTA: `Say hello` → `/contact/`. No second funnel.
- No fake proof: no invented metrics, testimonials, client names, or AggregateRating. Proof is GitHub, live demos, and writing.
- Oxford comma. Periods inside quotation marks. Bold sparingly. Grade 7–8 reading level; keep real technical nouns.
- Skill-first keywords (WordPress, plugins, web apps). No city stuffing; one Gettysburg mention on About is enough.

## When asked to edit Matt's writing

Return the rewrite plus **Editor's Notes**: 3–6 bullets on what changed and why. Edit block content, not Blade.

## Done when

- The diff adds no `mh_f_*` key and no new `\App\field()` call. A converted page has zero theme fields.
- `python3 .github/scripts/wp-review` is clean on changed render files, and the editor opens the page without console errors.
- `docs/FEATURES.md` and `CHANGELOG.md` are updated when a page converts or a block is added.
