#!/usr/bin/env python3
"""Pull live fatal clues over SiteGround FTP (debug.log + theme markers)."""

from __future__ import annotations

import ftplib
import io
import os
import socket
import sys


def log(msg: str) -> None:
    print(msg, flush=True)


def connect() -> ftplib.FTP:
    raw = os.environ["FTP_HOST"].strip()
    host = raw.replace("ftp://", "").replace("ftps://", "").split("/")[0]
    port = 21
    if ":" in host:
        host, _, port_s = host.rpartition(":")
        port = int(port_s)
    user = os.environ["FTP_USER"]
    password = os.environ["FTP_PASS"]
    infos = socket.getaddrinfo(host, port, socket.AF_INET, socket.SOCK_STREAM)
    target = infos[0][4][0] if infos else host
    ftp = ftplib.FTP(timeout=45)
    ftp.connect(target, port, timeout=45)
    ftp.login(user, password)
    ftp.set_pasv(True)
    log(f"FTP login ok ({target})")
    return ftp


def join_ftp(*parts: str) -> str:
    out = ""
    for part in parts:
        part = (part or "").strip().replace("\\", "/")
        if not part:
            continue
        if out and not out.endswith("/"):
            out += "/"
        out += part.lstrip("/") if out else part
    return out or "/"


def wp_content_from_theme_dir(theme_dir: str) -> str:
    path = theme_dir.rstrip("/")
    needle = "wp-content/themes/"
    idx = path.find(needle)
    if idx >= 0:
        return path[:idx] + "wp-content"
    raise RuntimeError(f"bad theme dir: {theme_dir}")


def download(ftp: ftplib.FTP, path: str, limit: int = 200_000) -> bytes:
    buf = io.BytesIO()
    try:
        ftp.retrbinary(f"RETR {path}", buf.write, blocksize=8192)
    except ftplib.error_perm as exc:
        log(f"RETR failed {path}: {exc}")
        return b""
    data = buf.getvalue()
    if len(data) > limit:
        data = data[-limit:]
    return data


def nlst(ftp: ftplib.FTP, path: str) -> list[str]:
    try:
        return ftp.nlst(path)
    except ftplib.error_perm:
        return []


def main() -> int:
    deploy_dir = (os.environ.get("DEPLOY_DIR") or os.environ.get("FTP_HINT") or "").strip()
    if not deploy_dir:
        # Fall back to locate output style path used in last deploy
        deploy_dir = "matthummel.com/public_html/wp-content/themes/matthummel"

    ftp = connect()
    try:
        wp_content = wp_content_from_theme_dir(deploy_dir)
        theme = deploy_dir.rstrip("/")
        log(f"theme={theme}")
        log(f"wp_content={wp_content}")

        fn = download(ftp, join_ftp(theme, "functions.php"), limit=80_000).decode("utf-8", "replace")
        log(f"functions.php bytes={len(fn)}")
        log(f"functions has self-heal marker: {'Drop stale Acorn package manifests' in fn}")
        log(f"functions Version comment / shop load: shop in collect={'''shop''' in fn}")

        style = download(ftp, join_ftp(theme, "style.css"), limit=4000).decode("utf-8", "replace")
        for line in style.splitlines()[:12]:
            if "Version" in line or "Theme Name" in line:
                log(f"style: {line.strip()}")

        cache = join_ftp(wp_content, "cache/acorn/framework/cache")
        log(f"acorn cache listing for {cache}:")
        for name in nlst(ftp, cache):
            log(f"  - {name}")

        packages = download(ftp, join_ftp(cache, "packages.php"), limit=20_000).decode("utf-8", "replace")
        if packages:
            log("--- packages.php ---")
            log(packages[:3000])
            log(f"packages mentions Heroicons: {'Heroicons' in packages}")
        else:
            log("packages.php missing/empty")

        services = download(ftp, join_ftp(cache, "services.php"), limit=20_000).decode("utf-8", "replace")
        if services:
            log("--- services.php (head) ---")
            log(services[:2000])
            log(f"services mentions Heroicons: {'Heroicons' in services}")
        else:
            log("services.php missing/empty")

        for rel in ("debug.log", "uploads/debug.log"):
            path = join_ftp(wp_content, rel)
            raw = download(ftp, path, limit=40_000)
            if not raw:
                log(f"{path}: empty/missing")
                continue
            text = raw.decode("utf-8", "replace")
            log(f"=== {path} (tail) ===")
            # Prefer fatal lines
            lines = text.splitlines()
            fatals = [ln for ln in lines if "Fatal" in ln or "Heroicons" in ln or "Uncaught" in ln]
            if fatals:
                log("fatal lines:")
                for ln in fatals[-15:]:
                    log(ln)
            log("last 30 lines:")
            for ln in lines[-30:]:
                log(ln)
    finally:
        try:
            ftp.quit()
        except Exception:  # noqa: BLE001
            ftp.close()
    return 0


if __name__ == "__main__":
    sys.exit(main())
