#!/usr/bin/env python3
"""Locate the live WordPress theme folder over SiteGround FTP and choose a deploy path.

Prints GitHub Actions outputs:
  pwd, fse_dir, sage_dir, deploy_dir, php, mode

mode is ``sage-active`` (PHP >= 8.3, upload into the live ``matthummel`` folder after
renaming the FSE theme aside) or ``sage-sidecar`` (PHP < 8.3, upload into
``matthummel-sage`` and restyle the live FSE palette so the site stays up).
"""

from __future__ import annotations

import ftplib
import io
import json
import os
import sys
import uuid
from typing import Iterable

SAGE_PALETTE = [
    {"slug": "base", "color": "#F4F7FB", "name": "Base"},
    {"slug": "white", "color": "#FFFFFF", "name": "White"},
    {"slug": "contrast", "color": "#0F172A", "name": "Contrast"},
    {"slug": "body", "color": "#475569", "name": "Body"},
    {"slug": "muted", "color": "#64748B", "name": "Muted"},
    {"slug": "border", "color": "#DBE3EE", "name": "Border"},
    {"slug": "primary", "color": "#2563EB", "name": "Primary"},
    {"slug": "primary-hover", "color": "#1D4ED8", "name": "Primary Hover"},
    {"slug": "accent", "color": "#2563EB", "name": "Accent"},
    {"slug": "blue-50", "color": "#EEF2F7", "name": "Blue 50"},
    {"slug": "blue-100", "color": "#DBEAFE", "name": "Blue 100"},
    {"slug": "blue-300", "color": "#93C5FD", "name": "Blue 300"},
]

def log(msg: str) -> None:
    print(msg, flush=True)


def connect() -> ftplib.FTP:
    raw = os.environ["FTP_HOST"].strip()
    host = raw.replace("ftp://", "").replace("ftps://", "").split("/")[0]
    if ":" in host:
        host, port_s = host.rsplit(":", 1)
        port = int(port_s)
    else:
        port = 21
    user = os.environ["FTP_USER"]
    password = os.environ["FTP_PASS"]

    errors: list[str] = []
    for use_tls in (True, False):
        try:
            ftp: ftplib.FTP
            if use_tls:
                ftp = ftplib.FTP_TLS(timeout=60)
            else:
                ftp = ftplib.FTP(timeout=60)
            ftp.connect(host, port)
            ftp.login(user, password)
            if use_tls:
                try:
                    ftp.prot_p()
                except Exception:
                    pass
            ftp.set_pasv(True)
            log(f"FTP login ok ({'tls' if use_tls else 'plain'}), pwd={ftp.pwd()}")
            return ftp
        except Exception as exc:
            errors.append(f"{'tls' if use_tls else 'plain'}: {type(exc).__name__}")
    raise RuntimeError("FTP login failed (" + ", ".join(errors) + ")")


def nlst(ftp: ftplib.FTP, path: str) -> list[str]:
    try:
        names = ftp.nlst(path)
    except ftplib.error_perm:
        return []
    out = []
    for name in names:
        base = name.rstrip("/").split("/")[-1]
        if base in {".", ".."}:
            continue
        out.append(name if "/" in name else f"{path.rstrip('/')}/{base}")
    return out


def join_ftp(*parts: str) -> str:
    bits: list[str] = []
    for part in parts:
        if not part or part == ".":
            continue
        bits.extend(p for p in part.replace("\\", "/").split("/") if p and p != ".")
    return "/" + "/".join(bits)


def download_text(ftp: ftplib.FTP, path: str, limit: int = 4000) -> str:
    buf = io.BytesIO()
    try:
        ftp.retrbinary(f"RETR {path}", buf.write, blocksize=8192)
    except ftplib.error_perm:
        return ""
    return buf.getvalue()[:limit].decode("utf-8", errors="replace")


def upload_text(ftp: ftplib.FTP, path: str, body: str) -> None:
    ftp.storbinary(f"STOR {path}", io.BytesIO(body.encode("utf-8")))


def is_dir(ftp: ftplib.FTP, path: str) -> bool:
    current = ftp.pwd()
    try:
        ftp.cwd(path)
        ftp.cwd(current)
        return True
    except ftplib.error_perm:
        return False


def ensure_dir(ftp: ftplib.FTP, path: str) -> None:
    current = ftp.pwd()
    built = ""
    for part in path.strip("/").split("/"):
        built = f"{built}/{part}"
        try:
            ftp.cwd(built)
        except ftplib.error_perm:
            ftp.mkd(built)
            ftp.cwd(built)
    ftp.cwd(current)


def classify_style(text: str) -> str | None:
    lower = text.lower()
    if "sage 11" in lower or "portfolio 3.0" in lower or "text domain:        sage" in lower:
        return "sage"
    if "full-site-editing" in lower or "block) theme" in lower:
        return "fse"
    if "theme name:" in lower:
        return "other"
    return None


def folder_name(path: str) -> str:
    return path.rstrip("/").split("/")[-1]


def unique_paths(paths: Iterable[str]) -> list[str]:
    seen: set[str] = set()
    out: list[str] = []
    for path in paths:
        key = path.rstrip("/") or "/"
        if key in seen:
            continue
        seen.add(key)
        out.append(path)
    return out


def ancestors(path: str) -> list[str]:
    parts = [p for p in path.replace("\\", "/").strip("/").split("/") if p]
    acc = ["/"]
    built = ""
    for part in parts:
        built += "/" + part
        acc.append(built)
    return list(reversed(acc))


def child_web_roots(ftp: ftplib.FTP, path: str) -> list[str]:
    out: list[str] = []
    for name in nlst(ftp, path):
        base = folder_name(name).lower()
        if base in {"public_html", "www", "httpdocs", "wwwroot"}:
            out.append(name if "/" in name or name.startswith("/") else join_ftp(path, base))
    return out


def find_theme_styles(ftp: ftplib.FTP, roots: Iterable[str]) -> list[tuple[str, str]]:
    """Look for wp-content/themes/*/style.css under likely web roots and FTP ancestors."""
    found: list[tuple[str, str]] = []
    checked: set[str] = set()
    theme_dirs: set[str] = set()

    def consider(style_path: str) -> None:
        if style_path in checked:
            return
        checked.add(style_path)
        kind = classify_style(download_text(ftp, style_path))
        if kind:
            log(f"Found {kind} theme folder {folder_name(style_path.rsplit('/', 1)[0])}")
            found.append((style_path, kind))

    def scan_themes_dir(themes: str) -> None:
        if themes in theme_dirs or not is_dir(ftp, themes):
            return
        theme_dirs.add(themes)
        log(f"Listing themes in …/{folder_name(themes)}")
        for entry in nlst(ftp, themes):
            if is_dir(ftp, entry):
                consider(join_ftp(entry, "style.css"))

    for root in roots:
        if not is_dir(ftp, root) and root not in {ftp.pwd(), "/", "."}:
            continue
        consider(join_ftp(root, "style.css"))
        if folder_name(root) == "themes":
            scan_themes_dir(root)
        scan_themes_dir(join_ftp(root, "wp-content/themes"))
        scan_themes_dir(join_ftp(root, "public_html/wp-content/themes"))
        parent = root.rstrip("/").rsplit("/", 1)[0] or "/"
        if folder_name(parent) == "themes":
            scan_themes_dir(parent)
    return found


def write_output(**kwargs: str) -> None:
    path = os.environ.get("GITHUB_OUTPUT")
    if not path:
        for key, value in kwargs.items():
            log(f"{key}={value}")
        return
    with open(path, "a", encoding="utf-8") as handle:
        for key, value in kwargs.items():
            handle.write(f"{key}={value}\n")


def probe_php(ftp: ftplib.FTP, theme_dir: str) -> str:
    name = f"_mh_php_{uuid.uuid4().hex[:10]}.php"
    path = join_ftp(theme_dir, name)
    upload_text(ftp, path, "<?php header('Content-Type: text/plain; charset=UTF-8'); echo PHP_VERSION;")
    version = ""
    try:
        import urllib.request

        url = "https://matthummel.com/wp-content/themes/matthummel/" + name
        with urllib.request.urlopen(url, timeout=20) as resp:
            version = resp.read().decode("utf-8", errors="replace").strip()
        if not version or not version[0].isdigit():
            log(f"PHP probe returned non-version text ({version[:40]!r})")
            version = ""
    except Exception as exc:
        log(f"PHP probe HTTP failed ({type(exc).__name__})")
    try:
        ftp.delete(path)
    except Exception:
        log("Could not delete PHP probe file; delete it in File Manager if it remains.")
    return version


def restyle_fse_palette(ftp: ftplib.FTP, theme_dir: str) -> None:
    path = join_ftp(theme_dir, "theme.json")
    raw = download_text(ftp, path, limit=2_000_000)
    if not raw:
        log("No live theme.json to restyle")
        return
    data = json.loads(raw)
    palette = data.setdefault("settings", {}).setdefault("color", {}).setdefault("palette", [])
    by_slug = {item.get("slug"): item for item in palette if isinstance(item, dict)}
    for item in SAGE_PALETTE:
        existing = by_slug.get(item["slug"])
        if existing:
            existing["color"] = item["color"]
        else:
            palette.append(item)
    upload_text(ftp, path, json.dumps(data, indent=2) + "\n")
    log("Updated live FSE theme.json to Sage blue-gray palette")


def prefer_hint_paths(ftp: ftplib.FTP) -> Iterable[str]:
    hint = os.environ.get("FTP_HINT", "").strip().rstrip("/")
    pwd = ftp.pwd() or "/"
    seeds = [pwd, "/", hint] if hint else [pwd, "/"]
    expanded: list[str] = []
    for seed in seeds:
        if not seed:
            continue
        expanded.append(seed)
        expanded.extend(ancestors(seed))
        expanded.extend(child_web_roots(ftp, seed))
        for rel in (
            "public_html",
            "www",
            "httpdocs",
            "wp-content/themes",
            "wp-content/themes/matthummel",
            "public_html/wp-content/themes",
            "public_html/wp-content/themes/matthummel",
        ):
            expanded.append(join_ftp(seed, rel))
    return unique_paths(expanded)


def live_score(style_path: str, kind: str) -> int:
    folder = folder_name(style_path.rsplit("/", 1)[0])
    path = style_path.replace("\\", "/").lower()
    score = 0
    if folder == "matthummel":
        score += 20
    elif folder.startswith("matthummel"):
        score += 2
    if "/public_html/" in path or path.startswith("/public_html/"):
        score += 8
    if kind == "fse":
        score += 6
    elif kind == "other":
        score += 4
    elif kind == "sage":
        score += 1
    return score


def parse_php_ok(php: str) -> bool:
    if not php:
        return False
    try:
        parts = [int(p) for p in php.split(".")[:2]]
        return tuple(parts) >= (8, 3)
    except ValueError:
        return php.startswith("8.3") or php.startswith("8.4") or php.startswith("8.5")


def main() -> int:
    ftp = connect()
    pwd = ftp.pwd() or "/"
    roots = list(prefer_hint_paths(ftp))
    log("Scanning likely web roots for theme style.css")
    hits = find_theme_styles(ftp, roots)

    sage = next((path for path, kind in hits if kind == "sage"), "")
    sage_dir = sage.rsplit("/", 1)[0] if sage else ""

    live_hits = [(path, kind) for path, kind in hits if folder_name(path.rsplit("/", 1)[0]) == "matthummel"]
    if not live_hits:
        # A dump folder may not be named matthummel; still try siblings of Sage.
        if sage_dir:
            sibling = join_ftp(sage_dir.rsplit("/", 1)[0], "matthummel")
            if is_dir(ftp, sibling):
                kind = classify_style(download_text(ftp, join_ftp(sibling, "style.css"))) or "other"
                live_hits = [(join_ftp(sibling, "style.css"), kind)]
    if not live_hits:
        log("Could not find wp-content/themes/matthummel/style.css over FTP")
        log("Saw " + ", ".join(f"{folder_name(p.rsplit('/', 1)[0])}={k}" for p, k in hits) if hits else "Saw no theme style.css files")
        write_output(pwd=pwd, fse_dir="", sage_dir=sage_dir, deploy_dir="", php="", mode="failed")
        return 1

    live_hits.sort(key=lambda item: live_score(*item), reverse=True)
    live_style, live_kind = live_hits[0]
    live_dir = live_style.rsplit("/", 1)[0]
    log(f"Live theme folder slug=matthummel kind={live_kind}")

    php = probe_php(ftp, live_dir)
    log(f"Live PHP probe: {php or 'unknown'}")
    php_ok = parse_php_ok(php)

    themes_root = live_dir.rsplit("/", 1)[0]
    sidecar = join_ftp(themes_root, "matthummel-sage")

    if live_kind == "sage":
        deploy_dir = live_dir.rstrip("/") + "/"
        mode = "sage-active"
        if not php_ok:
            log("Live folder is already Sage; uploading there even though PHP probe is not 8.3+")
    elif php_ok:
        backup = join_ftp(themes_root, "matthummel-fse-backup")
        if not is_dir(ftp, backup) and live_kind != "sage":
            log(f"Renaming live theme folder -> matthummel-fse-backup")
            ftp.rename(live_dir, backup)
        ensure_dir(ftp, live_dir)
        deploy_dir = live_dir.rstrip("/") + "/"
        mode = "sage-active"
    else:
        if live_kind in {"fse", "other"}:
            restyle_fse_palette(ftp, live_dir)
        ensure_dir(ftp, sidecar)
        deploy_dir = sidecar.rstrip("/") + "/"
        mode = "sage-sidecar"
        log("PHP is below 8.3 or unknown; Sage goes to matthummel-sage so the live site stays up")

    def as_remote_dir(path: str) -> str:
        """Path for FTP-Deploy-Action, relative to the login directory when possible."""
        abs_path = path if path.startswith("/") else join_ftp(pwd, path)
        pwd_norm = pwd.rstrip("/") or ""
        rel = abs_path
        if pwd_norm and abs_path.startswith(pwd_norm + "/"):
            rel = abs_path[len(pwd_norm) + 1 :]
        elif pwd_norm and abs_path == pwd_norm:
            rel = ""
        rel = rel.strip("/")
        return (rel + "/") if rel else "./"

    remote_deploy = as_remote_dir(deploy_dir)
    rel_deploy = remote_deploy.replace("\\", "/").strip("/")
    allowed_slugs = {"matthummel", "matthummel-sage"}
    ok_target = (
        "themes/" in remote_deploy.replace("\\", "/")
        or rel_deploy in allowed_slugs
        or (rel_deploy in {"", "."} and folder_name(live_dir.rstrip("/")) in allowed_slugs)
    )
    if not ok_target:
        log(f"Refusing to deploy outside a WordPress theme folder: {remote_deploy}")
        write_output(pwd=pwd, fse_dir="", sage_dir="", deploy_dir="", php=php, mode="failed")
        return 1

    write_output(
        pwd=pwd,
        fse_dir=as_remote_dir(live_dir),
        sage_dir=as_remote_dir(sage_dir) if sage_dir else "",
        deploy_dir=remote_deploy,
        php=php,
        mode=mode,
    )
    log(f"mode={mode} deploy_dir={remote_deploy}")
    ftp.quit()
    return 0


if __name__ == "__main__":
    sys.exit(main())
