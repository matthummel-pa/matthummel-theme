# TOCflow — SEO Package & Social Repurposing

Companion to `tocflow-blog-post.md`. Everything below is ready to paste into
Rank Math and your social channels.

---

## 1. SEO package (Rank Math)

| Field | Value |
| --- | --- |
| **Primary focus keyword** | WordPress table of contents block |
| **Secondary keywords** | free table of contents plugin, Gutenberg table of contents, server-rendered TOC, accessible table of contents WordPress, auto table of contents |
| **Meta title** (58 chars) | TOCflow: A Free WordPress Table of Contents Block (2026) |
| **Permalink / slug** (46 chars) | `free-wordpress-table-of-contents-block-tocflow` |
| **Meta description** (156 chars) | Meet TOCflow, a free, server-rendered WordPress table of contents block. Auto-build an accessible, SEO-friendly outline from your headings—no setup needed. |
| **Tags** | WordPress, Gutenberg, Table of Contents, WordPress Plugins, SEO, Accessibility, Block Editor |
| **Categories** | WordPress, Web Development |

**Post excerpt / summary**
> TOCflow is a free, open-source WordPress block that builds a linked table of
> contents from your headings automatically. It's server-rendered—so the outline
> is in your HTML on load—making it fast, accessible, and SEO-friendly. Here's how
> to use it and the mistakes to avoid.

### Featured image
- **File:** `free-wordpress-table-of-contents-block-tocflow.png` (1200×630)
- **Alt text:** "TOCflow, a free WordPress Table of Contents block, showing an auto-generated nested outline"
- **Image title:** "TOCflow — free WordPress Table of Contents block"
- **Style:** modern flat design, green gradient background, bold white typography, UI-inspired TOC preview card.

---

## 2. Reddit post

**Suggested subreddits:** r/WordPress, r/ProWordPress, r/Wordpress_Development

**Title:** I built a free, server-rendered Table of Contents block for WordPress (no JS on the front end)

**Body:**
> I kept reaching for a table-of-contents plugin and finding them either bloated
> or JS-heavy, so I made a small one: **TOCflow**.
>
> It's a single Gutenberg block. Add it to a post and it builds a linked outline
> from your H2/H3/H4 headings automatically. The key difference is it's
> **server-rendered**—the list is in the page HTML on load, so it's friendlier to
> SEO and screen readers, and there's no front-end JavaScript.
>
> - Pick which heading levels show
> - Numbered or bulleted
> - Adds matching anchor IDs to your headings for you
> - Accessible `<nav>` landmark
>
> It's free and GPL. Download: <release link> · Docs: <support page link>
>
> It's an early v0.1.0—happy to hear feedback or feature requests. What would you
> want a TOC block to do?

*(Reddit tip: reply in comments rather than stuffing links in the post; lead with the problem, not the promo.)*

---

## 3. dev.to post

**Title:** Building a Server-Rendered Table of Contents Block for WordPress

**Tags:** `#wordpress` `#php` `#webdev` `#opensource`

**Intro:**
> Most WordPress table-of-contents plugins build their list with JavaScript after
> the page loads. I wanted one that renders on the server, so the outline is in the
> HTML immediately—better for SEO and accessibility. Here's TOCflow, and the design
> decisions behind it.

**Outline to expand (or paste the full blog post):**
1. The problem with JS-built TOCs (SEO + a11y).
2. One block, done well—why not a block library.
3. How it works: `parse_blocks()` → one slug-stamped heading map → nested `<ul>/<ol>`.
4. Keeping anchors in sync via a `render_block` filter.
5. Accessibility: a real `<nav>` landmark.
6. Try it / contribute.

**Canonical URL:** point dev.to's canonical field at the matthummel.com post to avoid duplicate-content dilution.

**CTA:** Free download <release link> · Source <repo link>

---

## 4. Facebook post

> 🧭 New free WordPress plugin: **TOCflow**
>
> Add one block and get a clean, linked Table of Contents built automatically from
> your post's headings. It's server-rendered, so it's fast, accessible, and
> SEO-friendly—no setup required.
>
> ✅ Auto-built from H2/H3/H4
> ✅ Numbered or bulleted
> ✅ Free & open source
>
> Grab it free and read the guide 👇
> <support page link>

---

## 5. Bluesky post (≤300 chars)

> Built a tiny free WordPress plugin: TOCflow 🧭
>
> One Gutenberg block → an auto-built, server-rendered Table of Contents from your
> headings. Fast, accessible, SEO-friendly, no front-end JS.
>
> Free download + docs: <support page link>

---

## 6. LinkedIn carousel (8 slides)

Design note: reuse the featured-image palette—green gradient, bold white type, one
idea per slide.

1. **Cover** — "TOCflow: a free, server-rendered Table of Contents block for WordPress." Subtitle: "One block. No setup. Better SEO." + your name/handle.
2. **The problem** — "Most TOC plugins build the list with JavaScript. Search engines and screen readers don't always see it—and it slows your page."
3. **The idea** — "What if the table of contents was already in your HTML when the page loads?"
4. **How it works** — "TOCflow renders the outline on the server from your H2/H3/H4 headings. It's there on load—no front-end JS."
5. **It stays accurate** — "It injects matching anchor IDs into your headings automatically, so every link scrolls to the right place."
6. **You're in control** — "Choose which heading levels show. Numbered or bulleted. Match your theme's colors and spacing."
7. **Accessible by default** — "Output is a proper <nav> landmark—screen readers announce it correctly."
8. **CTA** — "Free & open source. Download + docs at matthummel-pa.github.io/tocflow. Feedback welcome." 

**Post caption:**
> I built a free WordPress Table of Contents block called TOCflow. The twist: it's
> server-rendered, so the outline is in your page HTML on load—better for SEO and
> accessibility, with no front-end JavaScript. Swipe through how it works, and grab
> it free 👉 <support page link> #WordPress #WebDevelopment #SEO #Accessibility

---

## 7. Content distribution checklist

- [ ] Publish the post on matthummel.com (set focus keyword, meta, slug, excerpt, featured image, alt text in Rank Math).
- [ ] Confirm the Rank Math score is green and the featured image / Open Graph preview looks right.
- [ ] Internally link the post from a related article and from the site's WordPress/Resources section.
- [ ] Submit the URL in Google Search Console (Request indexing).
- [ ] Post to dev.to with the canonical URL pointing back to matthummel.com.
- [ ] Post to Reddit (r/WordPress); reply to comments, don't drop and run.
- [ ] Share the Facebook post.
- [ ] Share the Bluesky post.
- [ ] Upload the LinkedIn carousel (PDF or images) with the caption.
- [ ] Add a link to the post from the GitHub repo README / release notes.
- [ ] Pin or feature the post for a week; check GSC impressions after ~10–14 days.
- [ ] (Optional) Email it to your newsletter / dev.to followers.

---

## Links to drop in
- **Free download:** https://github.com/matthummel-pa/tocflow/releases/latest
- **Support & docs:** https://matthummel-pa.github.io/tocflow/
- **Source code:** https://github.com/matthummel-pa/tocflow
