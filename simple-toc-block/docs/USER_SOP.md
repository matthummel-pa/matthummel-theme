# User SOP — Simple TOC Block

A step-by-step guide for **using** the Simple TOC Block plugin on your WordPress
site. No coding required.

Simple TOC Block adds one block — **Table of Contents** — that automatically
builds a linked outline from the headings in your post.

---

## 1. Install the plugin

**Option A — Upload the ZIP (most common)**

1. Download the latest `simple-toc-block.zip` from the
   [Releases page](https://github.com/matthummel/simple-toc-block/releases).
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP, click **Install Now**, then **Activate**.

**Option B — Manual install**

1. Unzip the file.
2. Copy the `simple-toc-block` folder into `wp-content/plugins/` on your server.
3. Go to **Plugins**, find **Simple TOC Block**, and click **Activate**.

---

## 2. Add a Table of Contents to a post

1. Edit (or create) a post that has some **Heading** blocks (H2, H3, H4).
2. Click the **+** (block inserter) where you want the TOC — usually right after
   your intro paragraph.
3. Search for **“Table of Contents”** and select it.
4. That's it — the outline is generated automatically from your headings.

> The list updates from your actual headings when the page loads, so you don't
> have to maintain it by hand.

---

## 3. Customize it

With the block selected, open the **Settings** sidebar (the gear icon) to adjust:

| Setting | What it does |
| --- | --- |
| **Title** | The heading shown above the list (default: “Table of Contents”). |
| **Show H2 / H3 / H4** | Choose which heading levels appear in the outline. |
| **Numbered list** | Toggle between a numbered (1, 2, 3) and bulleted list. |

You can also use the standard block controls for **color**, **spacing**, and
**typography** (font size, line height).

---

## 4. Verify the links work

1. Click **Preview** or **View Post**.
2. The Table of Contents appears with each heading as a link.
3. Click any link — the page should jump to that heading.

Because the TOC is built on the server, it's already in the page when it loads,
which is good for SEO and screen readers.

---

## 5. Troubleshooting

**The TOC is empty or missing headings**
- Make sure your headings are real **Heading blocks**, not bold text styled to
  look like headings.
- Check that the levels you used (e.g. H4) are enabled in the block settings.

**Links don't scroll to the right place**
- Simple TOC Block adds anchor IDs automatically. If a heading already had a
  custom HTML anchor set in its block settings, that one is respected — make sure
  it's unique.
- A sticky header/menu can cover the target. This is a known limitation; a
  scroll-offset option is on the roadmap.

**The block doesn't appear in the inserter**
- Confirm the plugin is **Activated** under *Plugins*.
- Make sure you're using the **block editor** (Gutenberg), not the Classic editor.

**Styling looks off with my theme**
- Use the block's color/spacing/typography controls to match your theme, or add
  custom CSS targeting the `.simple-toc` / `.simple-toc__sub` classes.

---

## 6. Common mistakes to avoid

- Adding the block to a post with **no headings** — there's nothing to list.
- Disabling **all** heading levels in settings — the list will be empty.
- Expecting it to read headings from **other** posts — each TOC reflects the
  post it lives in.

---

## 7. Frequently asked questions

**Does it work with the Classic Editor?**
No. It's a block for the WordPress block editor (Gutenberg).

**Will it slow down my site?**
No. The outline is rendered on the server as plain HTML — there's no heavy
JavaScript on the front end.

**Can I have more than one TOC on a page?**
It's designed for one per post. Multiple blocks will each list the same headings.

---

## 8. Getting help

Found a bug or have a feature request? Open an issue at
[github.com/matthummel/simple-toc-block/issues](https://github.com/matthummel/simple-toc-block/issues).
Please include your WordPress version, theme, and a screenshot if you can.
