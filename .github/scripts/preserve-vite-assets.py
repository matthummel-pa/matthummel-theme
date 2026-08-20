#!/usr/bin/env python3
"""Keep old Vite hashed files so SiteGround HTML cache does not 404 CSS.

FTP deploy deletes files that are not in the new `public/build/`. Cached HTML
still points at the previous `app-*.css` / `app-*.js` names, so the live site
renders with no theme styles until the proxy cache expires.

This script:
  1. Reads hashed names from cached live HTML
  2. Copies the current app CSS/JS to those names when missing
  3. Optionally downloads leftover remote assets over FTP
"""

from __future__ import annotations

import json
import os
import re
import shutil
import ssl
import sys
import urllib.request
from pathlib import Path

ASSET_RE = re.compile(r"/public/build/assets/(app-[A-Za-z0-9_-]+\.(?:css|js))")
PAGES = (
    "/",
    "/blog/",
    "/projects/",
    "/contact/",
    "/about/",
    "/code/",
    "/services/",
)


def log(msg: str) -> None:
    print(msg, flush=True)


def root() -> Path:
    return Path(os.environ.get("GITHUB_WORKSPACE") or Path(__file__).resolve().parents[2])


def load_manifest(base: Path) -> tuple[Path, Path]:
    manifest_path = base / "public/build/manifest.json"
    data = json.loads(manifest_path.read_text())
    css = base / "public/build" / data["resources/css/app.css"]["file"]
    js = base / "public/build" / data["resources/js/app.js"]["file"]
    if not css.is_file() or not js.is_file():
        raise SystemExit(f"Current Vite files missing: {css.name} {js.name}")
    return css, js


def fetch(url: str) -> str:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "matthummel-theme-preserve-vite/1.0"},
    )
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, timeout=20, context=ctx) as res:
        return res.read().decode("utf-8", "replace")


def names_from_live(origin: str) -> set[str]:
    found: set[str] = set()
    for path in PAGES:
        url = origin.rstrip("/") + path
        try:
            html = fetch(url)
        except Exception as exc:
            log(f"skip {url}: {type(exc).__name__}")
            continue
        found.update(ASSET_RE.findall(html))
    # Last known cached pair from the 2026-08-20 outage.
    found.update({"app-DCH7vjpa.css", "app-B6C9Ywvy.js"})
    return found


def alias_missing(assets: Path, css: Path, js: Path, names: set[str]) -> int:
    n = 0
    for name in sorted(names):
        dest = assets / name
        if dest.exists():
            continue
        src = css if name.endswith(".css") else js
        shutil.copyfile(src, dest)
        log(f"aliased {src.name} -> {name}")
        n += 1
    return n


def ftp_pull(assets: Path, deploy_dir: str) -> int:
    host = os.environ.get("FTP_HOST", "").strip()
    user = os.environ.get("FTP_USER", "").strip()
    password = os.environ.get("FTP_PASS", "").strip()
    if not host or not user or not password or not deploy_dir:
        return 0

    import ftplib
    import socket

    host = host.replace("ftp://", "").replace("ftps://", "").split("/")[0]
    port = 21
    if ":" in host:
        host, port_s = host.rsplit(":", 1)
        port = int(port_s)

    ftp = ftplib.FTP(timeout=30)
    ip = host
    try:
        ip = socket.getaddrinfo(host, port, socket.AF_INET, socket.SOCK_STREAM)[0][4][0]
    except Exception:
        pass
    ftp.connect(ip, port, timeout=30)
    ftp.login(user, password)
    ftp.set_pasv(True)

    remote = deploy_dir.rstrip("/") + "/public/build/assets"
    try:
        ftp.cwd(remote)
    except Exception:
        log(f"no remote assets dir {remote}")
        ftp.quit()
        return 0

    n = 0
    try:
        names = ftp.nlst()
    except Exception:
        names = []
    for name in names:
        if name in {".", ".."} or "/" in name:
            continue
        dest = assets / name
        if dest.exists():
            continue
        try:
            with dest.open("wb") as fh:
                ftp.retrbinary(f"RETR {name}", fh.write)
            log(f"pulled {name}")
            n += 1
        except Exception as exc:
            dest.unlink(missing_ok=True)
            log(f"skip pull {name}: {type(exc).__name__}")
    ftp.quit()
    return n


def main() -> int:
    base = root()
    assets = base / "public/build/assets"
    assets.mkdir(parents=True, exist_ok=True)
    css, js = load_manifest(base)
    origin = os.environ.get("LIVE_ORIGIN", "https://matthummel.com")
    names = names_from_live(origin)
    log("live hashed files: " + ", ".join(sorted(names)) if names else "no hashed files in HTML")
    aliased = alias_missing(assets, css, js, names)
    pulled = 0
    try:
        pulled = ftp_pull(assets, os.environ.get("DEPLOY_DIR", ""))
    except Exception as exc:
        log(f"ftp pull skipped: {type(exc).__name__}")
    log(f"preserve-vite-assets aliased={aliased} pulled={pulled}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
