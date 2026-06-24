# TOCflow: A Free WordPress Table of Contents Block That Just Works

*Add one block and get a clean, linked, SEO-friendly table of contents — built automatically from your headings, with no setup.*

If you write long posts, you already know the problem: readers land on a 2,000-word article, can't tell what's inside, and bounce. A **table of contents** fixes that — it gives people a map, helps them jump to what they need, and can even earn you jump-to links in Google search results. The catch is that most table-of-contents tools are either bloated, fiddly, or rely on JavaScript that hurts performance.

**TOCflow** is a free, open-source WordPress block that takes a different approach. You add one **Table of Contents** block to a post, and it builds a linked outline from your headings automatically. Because it's *server-rendered*, the list is in your page's HTML the moment it loads — which is great for SEO and screen readers. This post walks through what it does, how to use it, and the mistakes to avoid.

## What TOCflow does

TOCflow adds a single, focused block — nothing else to configure site-wide. Here's the short version:

- **Auto-generates** the outline from your H2, H3, and H4 headings.
- **Server-rendered**, so it appears in the initial HTML (no heavy front-end JavaScript).
- **Accurate anchors** — it injects matching IDs into your headings so every link scrolls to the right spot.
- **Accessible** — the output is a proper `<nav>` landmark that screen readers announce correctly.
- **Flexible** — pick which heading levels to include and choose a numbered or bulleted list.

It's built on the modern [WordPress block editor](https://wordpress.org/documentation/article/wordpress-block-editor/) (Gutenberg), so it fits naturally into how you already write.

## Why "server-rendered" matters

A lot of table-of-contents plugins build their list with JavaScript *after* the page loads. That works visually, but it has two downsides: search engines and assistive tech may not see the list reliably, and it adds front-end weight. Google's own [SEO guidance](https://developers.google.com/search/docs/appearance/structured-data) and accessibility best practices both favor content that exists in the HTML on load.

TOCflow renders the outline in PHP on the server, so it's already there in the page source. That's better for [crawlability](https://developers.google.com/search/docs/fundamentals/seo-starter-guide) and for users who rely on a [screen reader's landmark navigation](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/nav).

## How to add a table of contents (step by step)

1. **Install the plugin.** [Download `tocflow.zip` free](https://github.com/matthummel-pa/tocflow/releases/latest), then go to **Plugins → Add New → Upload Plugin**, choose the ZIP, and click **Install Now → Activate**.
2. **Open a post with headings.** Make sure you're using real **Heading** blocks (H2/H3/H4), not bold text styled to look like headings.
3. **Insert the block.** Click the **+** inserter where you want the TOC — usually right after your intro — search **"Table of Contents,"** and select it.
4. **That's it.** The outline is generated from your headings automatically. There's nothing to maintain by hand.

## Customizing the output

Select the block and open the **Settings** sidebar (the gear icon) to fine-tune it:

| Setting | What it does |
| --- | --- |
| **Heading text** | The title above the list (default: *Table of Contents*). Leave it blank to hide the title. |
| **Include H2 / H3 / H4** | Choose which heading levels appear in the outline. |
| **Use a numbered list** | Switch between a numbered (1, 2, 3) and a bulleted list. |

You also get the standard block controls for **color**, **spacing**, and **typography**, so the TOC can match your theme without custom CSS.

## Real-world use cases

- **Long-form guides and tutorials** — let readers jump straight to the step they're stuck on.
- **Documentation and knowledge bases** — give every article a consistent, scannable outline.
- **Listicles and roundups** — a numbered TOC doubles as a preview of what's coming.
- **Recipe or how-to posts** — readers skip the backstory and go right to the instructions.

## Troubleshooting

**The TOC is empty or missing headings.** Confirm your headings are real Heading blocks, and that the levels you used (for example H4) are enabled in the block settings.

**A link doesn't scroll to the right place.** TOCflow adds anchor IDs automatically, but if a heading already has a custom HTML anchor, that one wins — make sure it's unique. A tall sticky header can also cover the target; a scroll-offset option is on the roadmap.

**The block isn't in the inserter.** Make sure the plugin is **Activated** under *Plugins*, and that you're using the block editor, not the Classic Editor.

## Common mistakes to avoid

- Adding the block to a post with **no headings** — there's nothing to list.
- Turning **off every heading level** — the list comes out empty.
- Expecting it to pull headings from **other** posts — each TOC reflects the post it lives in.
- Using **bold paragraphs** instead of Heading blocks — TOCflow only reads real headings.

## Quick-start checklist

- [ ] Download and activate TOCflow.
- [ ] Open a post that uses H2/H3/H4 Heading blocks.
- [ ] Insert the **Table of Contents** block after your intro.
- [ ] Pick your heading levels and list style in **Settings**.
- [ ] Preview the post and click a link to confirm it scrolls.

## The bottom line

TOCflow is intentionally small: one block, done well. It builds your outline automatically, keeps it accessible, and renders on the server so it helps rather than hurts your SEO and performance. If you've been putting off adding a table of contents because every option felt like overkill, this is the lightweight one to try.

**Try TOCflow free** → [Download the latest release](https://github.com/matthummel-pa/tocflow/releases/latest) and read the full [support & setup guide](https://matthummel-pa.github.io/tocflow/). Found a bug or want a feature? [Open an issue on GitHub](https://github.com/matthummel-pa/tocflow/issues) — I'd love your feedback.

---

### Sources & further reading
- WordPress Block Editor documentation — https://wordpress.org/documentation/article/wordpress-block-editor/
- Google Search — SEO Starter Guide — https://developers.google.com/search/docs/fundamentals/seo-starter-guide
- MDN — The `<nav>` element — https://developer.mozilla.org/en-US/docs/Web/HTML/Element/nav
