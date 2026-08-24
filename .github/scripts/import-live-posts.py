#!/usr/bin/env python3
"""Scrape live matthummel.com posts and import them into the local WordPress."""

import re
import subprocess
import sys
import time
import urllib.request
import urllib.parse
import html as html_module
import json

POSTS = [
    ("AI Website Redesign: 5 Lessons From a Solo Developer",
     "https://matthummel.com/web-development/ai-website-redesign-solo-developer/",
     "2026-06-22"),
    ("Git and Deployment for Beginners: Ship Your Portfolio with Confidence",
     "https://matthummel.com/web-development/git-and-deployment-for-beginners/",
     "2026-06-22"),
    ("Getting Started with React and Next.js: A Beginner's Roadmap",
     "https://matthummel.com/tutorials/getting-started-react-nextjs/",
     "2026-06-22"),
    ("CSS Flexbox vs Grid: When to Use Each (With Examples)",
     "https://matthummel.com/web-development/css-flexbox-vs-grid/",
     "2026-06-22"),
    ("Core Web Vitals Explained: A Beginner's Guide to a Faster Site",
     "https://matthummel.com/tutorials/core-web-vitals-explained/",
     "2026-06-22"),
    ("Web Accessibility for Beginners: 9 WCAG Basics to Start With",
     "https://matthummel.com/accessibility/web-accessibility-for-beginners/",
     "2026-06-22"),
    ("Power Apps Project Planning: 7 Simple Steps to Build Better Apps",
     "https://matthummel.com/power-apps/power-apps-project-planning-7-steps/",
     "2026-05-22"),
    ("Powerful Fixes for Power Apps Gallery Not Showing Items: 7 Easy Solutions",
     "https://matthummel.com/power-apps/power-apps-gallery-not-showing-items/",
     "2026-05-20"),
    ("7 Power Apps Filter, LookUp & Collection Examples That Actually Work",
     "https://matthummel.com/power-apps/power-apps-filter-lookup-collection/",
     "2026-05-18"),
    ("7 Power Apps Performance Tips for Beginners (Fix Slow Apps Fast)",
     "https://matthummel.com/power-apps/power-apps-performance-tips/",
     "2026-05-15"),
]


def fetch(url):
    result = subprocess.run(
        ["curl", "-s", "--max-time", "15",
         "-A", "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36",
         url],
        capture_output=True, text=True,
    )
    if result.returncode != 0:
        raise RuntimeError(f"curl failed: {result.stderr}")
    return result.stdout


def extract_post(html, url):
    # Slug from URL
    slug = url.rstrip("/").split("/")[-1]

    # Meta description → excerpt
    m = re.search(r'<meta name="description"\s+content="([^"]+)"', html)
    excerpt = html_module.unescape(m.group(1)) if m else ""

    # Published date
    m = re.search(r'published_time"\s+content="([^"T"]+)', html)
    pub_date = m.group(1).strip() if m else "2026-06-22"

    # Category from breadcrumb or URL path
    path = urllib.parse.urlparse(url).path.strip("/").split("/")
    raw_cat = path[0] if len(path) >= 2 else "uncategorized"
    cat_map = {
        "web-development": "Web Development",
        "tutorials": "Tutorials",
        "accessibility": "Accessibility",
        "power-apps": "Power Apps",
    }
    category = cat_map.get(raw_cat, raw_cat.replace("-", " ").title())

    # Main content — everything inside .post-prose div
    m = re.search(
        r'id="post-prose"[^>]*>(.*?)</div>\s*\n\s*</div>\s*\n\s*</div>',
        html,
        re.DOTALL,
    )
    if not m:
        # Fallback: grab everything between the id and the next closing pattern
        m = re.search(r'id="post-prose"[^>]*>(.*?)(?=<div class="post-tags|<div class="post-share|$)', html, re.DOTALL)

    content = m.group(1).strip() if m else ""

    # Strip the reading progress bar and other non-content elements that bleed in
    content = re.sub(r'<div class="mh-progress[^"]*"[^>]*/>', '', content)

    return {
        "slug": slug,
        "excerpt": excerpt,
        "pub_date": pub_date,
        "category": category,
        "content": content,
    }


def wp_cli(args, input_text=None):
    cmd = ["wp", "--path=/home/ubuntu/wp-site"] + args
    result = subprocess.run(
        cmd,
        capture_output=True,
        text=True,
        input=input_text,
    )
    return result.stdout.strip(), result.stderr.strip(), result.returncode


def ensure_category(name):
    slug = name.lower().replace(" ", "-")
    out, _, rc = wp_cli(["term", "get", "category", slug, "--by=slug", "--field=term_id"])
    if rc == 0 and out.isdigit():
        return int(out)
    out, err, rc = wp_cli(["term", "create", "category", name, f"--slug={slug}", "--porcelain"])
    if rc == 0 and out.isdigit():
        return int(out)
    print(f"  Warning: could not create category '{name}': {err}")
    return 1


def post_exists(slug):
    out, _, rc = wp_cli(["post", "list", f"--name={slug}", "--post_type=post",
                          "--post_status=any", "--field=ID", "--format=ids"])
    return rc == 0 and out.strip() not in ("", "0")


def import_post(title, url, date_hint):
    print(f"\n→ Fetching: {title}")
    try:
        html = fetch(url)
    except Exception as e:
        print(f"  SKIP — fetch failed: {e}")
        return

    data = extract_post(html, url)
    slug = data["slug"]

    if post_exists(slug):
        print(f"  SKIP — post '{slug}' already exists locally")
        return

    cat_id = ensure_category(data["category"])
    print(f"  Category: {data['category']} (id {cat_id})")

    # Write content to temp file to avoid shell quoting issues
    content_file = f"/tmp/post_content_{slug}.html"
    with open(content_file, "w") as f:
        f.write(data["content"])

    # Build WP-CLI command
    pub_date = data["pub_date"] + " 12:00:00" if len(data["pub_date"]) == 10 else data["pub_date"]

    out, err, rc = wp_cli([
        "post", "create",
        f"--post_title={title}",
        f"--post_name={slug}",
        f"--post_status=publish",
        f"--post_date={pub_date}",
        f"--post_category={cat_id}",
        f"--post_excerpt={data['excerpt'][:250]}",
        "--porcelain",
    ])

    if rc != 0 or not out.isdigit():
        print(f"  ERROR creating post: {err or out}")
        return

    post_id = int(out)
    print(f"  Created post ID {post_id}")

    # Set content separately to avoid shell length limits
    with open(content_file) as f:
        content = f.read()
    _, err2, rc2 = wp_cli(["post", "update", str(post_id),
                            f"--post_content={content}"])
    if rc2 != 0:
        print(f"  Warning updating content: {err2[:200]}")

    print(f"  ✓ Imported: {title}")
    time.sleep(0.5)


def main():
    print(f"Importing {len(POSTS)} live posts into local WordPress...\n")
    for title, url, date in POSTS:
        import_post(title, url, date)

    # Summary
    print("\n\nPost count after import:")
    out, _, _ = wp_cli(["post", "list", "--post_type=post", "--post_status=publish",
                          "--format=count"])
    print(f"  {out} published posts")


if __name__ == "__main__":
    main()
