# Journal posts (source)

Visitor-facing journal posts live in **WordPress**, not only this theme. Files here are the source of truth for new posts so they can be pasted, reviewed, and imported without scraping the live site.

Source `*.html` bodies are **Gutenberg block markup** (`<!-- wp:paragraph -->`, headings, groups, lists, details). Do not wrap a whole post in a Custom HTML block.

## Paste into wp-admin

1. Open **Posts → Add Post**.
2. Set the title, permalink slug, excerpt, category, and date from the matching `*.meta.json`.
3. In the block editor, open the **Code editor** (three dots → Code editor) and paste the `*.html` body as-is.
4. Switch back to the visual editor and confirm paragraphs, headings, and groups validate (no “Attempt recovery” / Custom HTML dump).
5. Publish. Suggested live URL after permalinks: `/what-actually-gets-faster-with-ai/` (or `/web-development/what-actually-gets-faster-with-ai/` if the site uses `/%category%/%postname%/`).

This Cloud environment cannot publish to matthummel.com. Do not treat a local preview URL as live.

## Import locally (Cursor Cloud / `~/wp-site`)

```bash
python3 .github/scripts/import-docs-posts.py
```

Requires WP-CLI against `~/wp-site`. Skips a slug that already exists. Does not delete posts. Imports the block markup verbatim.

Live-site scrape of already-published posts is still `.github/scripts/import-live-posts.py`.
