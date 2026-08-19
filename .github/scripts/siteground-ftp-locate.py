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
    if "sage 11" in lower or "portfolio 3.0" in lower:
        return "sage"
    if "full-site-editing" in lower or "block) theme" in lower:
        return "fse"
    if "theme name:" in lower:
        return "other"
    return None


def find_theme_styles(ftp: ftplib.FTP, roots: Iterable[str]) -> list[tuple[str, str]]:
    """Look for wp-content/themes/*/style.css under likely web roots only."""
    found: list[tuple[str, str]] = []
    checked: set[str] = set()

    def consider(style_path: str) -> None:
        if style_path in checked:
            return
        checked.add(style_path)
        kind = classify_style(download_text(ftp, style_path))
        if kind:
            log(f"Found {kind} theme at {style_path}")
            found.append((style_path, kind))

    for root in roots:
        if not is_dir(ftp, root) and root not in {ftp.pwd(), "/", "."}:
            continue
        consider(join_ftp(root, "style.css"))
        for themes in (
            join_ftp(root, "wp-content/themes"),
            join_ftp(root, "public_html/wp-content/themes"),
        ):
            if not is_dir(ftp, themes):
                continue
            for entry in nlst(ftp, themes):
                if is_dir(ftp, entry):
                    consider(join_ftp(entry, "style.css"))
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
    hint = os.environ.get("FTP_HINT", "").strip()
    pwd = ftp.pwd() or "/"
    yield pwd
    if hint:
        yield hint.rstrip("/")
    for rel in (
        "public_html",
        "www",
        "httpdocs",
        "wp-content/themes/matthummel",
        "public_html/wp-content/themes/matthummel",
    ):
        yield join_ftp(pwd, rel)


def main() -> int:
    ftp = connect()
    pwd = ftp.pwd() or "/"
    roots = list(prefer_hint_paths(ftp))
    log("Scanning likely web roots for theme style.css")
    hits = find_theme_styles(ftp, roots)

    fse = next((path for path, kind in hits if kind == "fse"), "")
    sage = next((path for path, kind in hits if kind == "sage"), "")
    fse_dir = fse.rsplit("/", 1)[0] if fse else ""
    sage_dir = sage.rsplit("/", 1)[0] if sage else ""

    if not fse_dir:
        log("Could not find the live FSE matthummel/style.css over FTP")
        write_output(pwd=pwd, fse_dir="", sage_dir=sage_dir, deploy_dir="", php="", mode="failed")
        return 1

    php = probe_php(ftp, fse_dir)
    log(f"Live PHP probe: {php or 'unknown'}")

    php_ok = False
    if php:
        try:
            parts = [int(p) for p in php.split(".")[:2]]
            php_ok = tuple(parts) >= (8, 3)
        except ValueError:
            php_ok = php.startswith("8.3") or php.startswith("8.4")

    themes_root = fse_dir.rsplit("/", 1)[0]
    sidecar = join_ftp(themes_root, "matthummel-sage")

    if php_ok:
        backup = join_ftp(themes_root, "matthummel-fse-backup")
        if not is_dir(ftp, backup):
            log(f"Renaming {fse_dir} -> {backup}")
            ftp.rename(fse_dir, backup)
        ensure_dir(ftp, fse_dir)
        deploy_dir = fse_dir.rstrip("/") + "/"
        mode = "sage-active"
    else:
        restyle_fse_palette(ftp, fse_dir)
        ensure_dir(ftp, sidecar)
        deploy_dir = sidecar.rstrip("/") + "/"
        mode = "sage-sidecar"

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
    if "themes/" not in remote_deploy.replace("\\", "/"):
        log(f"Refusing to deploy outside a themes folder: {remote_deploy}")
        write_output(pwd=pwd, fse_dir="", sage_dir="", deploy_dir="", php=php, mode="failed")
        return 1

    write_output(
        pwd=pwd,
        fse_dir=as_remote_dir(fse_dir),
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
