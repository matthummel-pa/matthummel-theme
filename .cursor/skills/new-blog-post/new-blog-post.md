# Skill: Write and Publish a New Blog Post

Use this skill when Matt asks to draft, review, or publish a blog post on matthummel.com/journal.

## Overview

Posts live as WordPress Gutenberg posts at `/journal/`. Source HTML drafts go in `docs/posts/` so they can be version-controlled. The post body is pasted into the WP editor on the live site.

Voice: first person, plain, specific. No fake metrics. "I" not "we."
Audience: shops/agencies first, developers second, learners third.

---

## Step 1 — Confirm the topic and focus keyword

Before writing, answer these three questions:

1. **What is the reader's search intent?** (e.g. "WordPress table of contents plugin" → informational/navigational)
2. **Who is the primary audience?** (shop/agency owner vs. developer)
3. **What is the one action you want them to take after reading?** (say hello, buy a product, follow on GitHub)

Set the focus keyword now. Check `app/rank-math-fields.php` → `mh_page_focus_keyword_defaults()` for keyword patterns already in use on other pages, and pick something different.

---

## Step 2 — Post structure

Every post follows this outline. Skip any section that does not apply.

```
H1: [Focus keyword phrase] — specific claim or question (60 chars max)

Intro paragraph (BLUF — bottom line up front):
  One sentence stating the answer or main finding. Then 2-3 sentences of context.
  No "In this post I will cover…" openers.

H2: [Problem or context section]
  2-3 paragraphs, max 3 sentences each.
  First paragraph mentions the focus keyword naturally.

H2: [How or implementation section]
  Code block when applicable (language class on <code>).
  Use existing .snippet CSS for code windows.

H2: [Results or summary]
  Plain facts. No "3x revenue" or fake before/after unless Matt supplies a real number.

H2: FAQ (optional, 3-5 questions)
  Use the FAQ block for FAQPage schema → Rank Math rich result.

CTA paragraph:
  One sentence linking to a related product or /contact/. Match audience.
```

---

## Step 3 — On-page SEO checklist

Rank Math target: **75+**. Check each before publishing:

- [ ] Focus keyword in H1 (exact or close variant)
- [ ] Focus keyword in first paragraph (within first 100 words)
- [ ] Focus keyword in at least one H2
- [ ] Focus keyword in meta description (under 155 chars)
- [ ] Meta description ends with a CTA (`Say hello` or `See the demo`)
- [ ] Post has at least one internal link to a product page or other post
- [ ] Featured image has descriptive alt text that includes the focus keyword
- [ ] Post is at least 600 words (Rank Math content length check)
- [ ] No two consecutive paragraphs over 3 sentences

---

## Step 4 — Code snippets

Wrap every code block in:

```html
<pre class="snippet"><code class="language-php">
  // your code here
</code></pre>
```

highlight.js picks up the `language-*` class and adds syntax highlighting + the copy button automatically.

Approved languages: `language-php`, `language-js`, `language-bash`, `language-html`, `language-css`.

---

## Step 5 — Save to docs/posts/

Create the file at `docs/posts/YYYY-MM-DD-post-slug.html`. This is the canonical source for the post body HTML. It does NOT include `<html>`, `<head>`, or `<body>` tags — just the Gutenberg-compatible HTML content.

Use standard Gutenberg block comments around major sections:

```html
<!-- wp:paragraph -->
<p>Your paragraph here.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Your H2 here.</h2>
<!-- /wp:heading -->

<!-- wp:code -->
<pre class="snippet"><code class="language-php">your_code();</code></pre>
<!-- /wp:code -->
```

---

## Step 6 — Publish on the live site

Options (choose one):

**A) Manual paste (most reliable):**
1. Go to wp-admin → Posts → Add New.
2. Set title, slug, category (use existing categories — do not create new ones without Matt's approval).
3. Switch to Code Editor and paste the HTML from `docs/posts/`.
4. Set Rank Math focus keyphrase and meta description.
5. Add tags (3-5 max) and a featured image (1200×630 px).
6. Publish.

**B) WP-CLI import:**
Use the import script: `python3 .github/scripts/import-docs-posts.py` — check that script for current usage before running.

---

## Step 7 — Cross-post distribution

After publishing:

| Channel | Action | When |
|---|---|---|
| DEV.to | Create new post, paste canonical URL in the canonical URL field (not the content) | Same day |
| Bluesky | Short thread: hook line + demo/screenshot + link | Same day |
| LinkedIn | 2-3 sentence update + link (professional dev audience) | Within 2 days |
| internal links | Add a link from at least one existing post or page | Within 1 week |

---

## Reference files

| File | What it does |
|---|---|
| `docs/posts/` | Source HTML for all posts |
| `resources/views/single.blade.php` | Single post template |
| `resources/views/partials/content-single.blade.php` | Post body render |
| `app/rank-math-fields.php` | Rank Math integration |
| `.cursor/rules/master-content-writer.mdc` | Voice and editing rules |
| `.cursor/rules/portfolio-seo-playbook.mdc` | Keyword and content layout rules |
