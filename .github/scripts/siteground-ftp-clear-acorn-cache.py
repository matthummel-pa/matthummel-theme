#!/usr/bin/env python3
"""Delete stale Acorn package manifests over SiteGround FTP.

Theme zip / FTP deploys overwrite wp-content/themes/matthummel/ but leave
wp-content/cache/acorn alone. A packages.php that still lists a removed
Composer provider (e.g. Blade Heroicons) fatals every front-end request.

Usage (CI): set FTP_HOST, FTP_USER, FTP_PASS, and DEPLOY_DIR (theme path).
Deletes:
  <wp-content>/cache/acorn/framework/cache/packages.php
  <wp-content>/cache/acorn/framework/cache/services.php
"""

from __future__ import annotations

import ftplib
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
    errors: list[str] = []

    for attempt in range(1, 4):
        try:
            infos = socket.getaddrinfo(host, port, socket.AF_INET, socket.SOCK_STREAM)
            target = infos[0][4][0] if infos else host
            ftp = ftplib.FTP(timeout=30)
            ftp.connect(target, port, timeout=30)
            ftp.login(user, password)
            ftp.set_pasv(True)
            log(f"FTP login ok (attempt {attempt})")
            return ftp
        except Exception as exc:  # noqa: BLE001
            errors.append(f"{type(exc).__name__}: {exc}")
            log(f"FTP login attempt {attempt} failed; retrying")

    raise RuntimeError("FTP login failed (" + ", ".join(errors) + ")")


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
    """themes/matthummel → wp-content (absolute FTP path)."""
    path = theme_dir.rstrip("/")
    needle = "wp-content/themes/"
    idx = path.find(needle)
    if idx >= 0:
        return path[:idx] + "wp-content"
    if path.endswith("/themes") or "/themes/" in path:
        return path.rsplit("/themes", 1)[0]
    raise RuntimeError(f"Could not derive wp-content from theme dir: {theme_dir}")


def try_delete(ftp: ftplib.FTP, path: str) -> bool:
    try:
        ftp.delete(path)
        log(f"Deleted {path}")
        return True
    except ftplib.error_perm as exc:
        msg = str(exc)
        if "550" in msg or "not found" in msg.lower() or "no such" in msg.lower():
            log(f"Skip (missing): {path}")
            return False
        log(f"Could not delete {path}: {exc}")
        return False


def main() -> int:
    deploy_dir = (os.environ.get("DEPLOY_DIR") or os.environ.get("FTP_HINT") or "").strip()
    if not deploy_dir:
        log("DEPLOY_DIR / FTP_HINT empty — nothing to clear")
        return 0

    ftp = connect()
    try:
        wp_content = wp_content_from_theme_dir(deploy_dir)
        cache_dir = join_ftp(wp_content, "cache/acorn/framework/cache")
        log(f"Acorn cache dir: {cache_dir}")
        deleted = 0
        for name in ("packages.php", "services.php"):
            if try_delete(ftp, join_ftp(cache_dir, name)):
                deleted += 1
        log(f"Cleared {deleted} Acorn manifest file(s)")
    finally:
        try:
            ftp.quit()
        except Exception:  # noqa: BLE001
            ftp.close()

    return 0


if __name__ == "__main__":
    sys.exit(main())
