#!/usr/bin/env python3
"""Import journal posts from docs/posts/*.meta.json into local WordPress.

Companion to import-live-posts.py (which scrapes matthummel.com). This path is
for posts authored in the theme repo so they can be previewed locally and
pasted on production. It never deletes existing posts.
"""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

REPO = Path(__file__).resolve().parents[2]
POSTS_DIR = REPO / "docs" / "posts"
WP_PATH = Path.home() / "wp-site"


def wp_cli(args: list[str], input_text: str | None = None) -> tuple[str, str, int]:
    cmd = ["wp", f"--path={WP_PATH}"] + args
    result = subprocess.run(cmd, capture_output=True, text=True, input=input_text)
    return result.stdout.strip(), result.stderr.strip(), result.returncode


def ensure_term(taxonomy: str, name: str) -> int:
    slug = name.lower().replace(" ", "-").replace("&", "").replace("--", "-")
    out, _, rc = wp_cli(["term", "get", taxonomy, slug, "--by=slug", "--field=term_id"])
    if rc == 0 and out.isdigit():
        return int(out)
    out, err, rc = wp_cli(
        ["term", "create", taxonomy, name, f"--slug={slug}", "--porcelain"]
    )
    if rc == 0 and out.isdigit():
        return int(out)
    print(f"  Warning: could not create {taxonomy} '{name}': {err or out}")
    return 0


def post_exists(slug: str) -> bool:
    out, _, rc = wp_cli(
        [
            "post",
            "list",
            f"--name={slug}",
            "--post_type=post",
            "--post_status=any",
            "--field=ID",
            "--format=ids",
        ]
    )
    return rc == 0 and out.strip() not in ("", "0")


def import_meta(meta_path: Path) -> None:
    data = json.loads(meta_path.read_text())
    slug = data["slug"]
    title = data["title"]
    print(f"\n→ {title}")

    if post_exists(slug):
        print(f"  SKIP — post '{slug}' already exists locally")
        return

    content_name = data.get("content_file") or meta_path.with_suffix(".html").name
    content_path = POSTS_DIR / content_name
    if not content_path.is_file():
        print(f"  ERROR — missing {content_path}")
        return

    content = content_path.read_text()
    cat_id = ensure_term("category", data.get("category") or "Web Development")
    tag_ids = []
    for tag in data.get("tags") or []:
        tid = ensure_term("post_tag", tag)
        if tid:
            tag_ids.append(str(tid))

    date = data.get("date") or "2026-09-01 10:00:00"
    excerpt = (data.get("excerpt") or "")[:250]
    status = data.get("status") or "publish"

    args = [
        "post",
        "create",
        f"--post_title={title}",
        f"--post_name={slug}",
        f"--post_status={status}",
        f"--post_date={date}",
        f"--post_excerpt={excerpt}",
        "--porcelain",
    ]
    if cat_id:
        args.append(f"--post_category={cat_id}")
    if tag_ids:
        args.append(f"--tags_input={','.join(tag_ids)}")

    out, err, rc = wp_cli(args)
    if rc != 0 or not out.isdigit():
        print(f"  ERROR creating post: {err or out}")
        return

    post_id = int(out)
    tmp = Path(f"/tmp/mh_docs_post_{slug}.html")
    tmp.write_text(content)
    php = (
        "$id = "
        + str(post_id)
        + "; $file = "
        + json.dumps(str(tmp))
        + "; $html = file_get_contents($file); "
        + "wp_update_post(['ID' => $id, 'post_content' => $html]);"
    )
    _, err2, rc2 = wp_cli(["eval", php])
    if rc2 != 0:
        print(f"  Warning updating content: {err2}")
        return

    print(f"  ✓ Imported locally as post ID {post_id} ({slug})")


def main() -> int:
    if not WP_PATH.is_dir():
        print(f"No WordPress at {WP_PATH}. Paste files from docs/posts/ in wp-admin instead.")
        return 1

    metas = sorted(POSTS_DIR.glob("*.meta.json"))
    if not metas:
        print(f"No *.meta.json files in {POSTS_DIR}")
        return 1

    print(f"Importing {len(metas)} docs post(s) into {WP_PATH}…")
    for meta in metas:
        import_meta(meta)

    out, _, _ = wp_cli(
        ["post", "list", "--post_type=post", "--post_status=publish", "--format=count"]
    )
    print(f"\nPublished posts: {out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
