#!/usr/bin/env python3
"""Replace stale Acorn package manifests over SiteGround FTP.

Theme zip / FTP deploys leave wp-content/cache/acorn alone. A packages.php that
still lists a removed Composer provider fatals every request. Deleting the
manifests is not enough when PHP cannot recreate them (permissions / open_basedir).

This script uploads known-good packages.php + services.php from
.github/fixtures/acorn-cache/ (no Blade Heroicons).
"""

from __future__ import annotations

import ftplib
import io
import os
import socket
import sys
from pathlib import Path


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
    path = theme_dir.rstrip("/")
    needle = "wp-content/themes/"
    idx = path.find(needle)
    if idx >= 0:
        return path[:idx] + "wp-content"
    if path.endswith("/themes") or "/themes/" in path:
        return path.rsplit("/themes", 1)[0]
    raise RuntimeError(f"Could not derive wp-content from theme dir: {theme_dir}")


def try_delete(ftp: ftplib.FTP, path: str) -> None:
    try:
        ftp.delete(path)
        log(f"Deleted {path}")
    except ftplib.error_perm as exc:
        msg = str(exc)
        if "550" in msg or "not found" in msg.lower() or "no such" in msg.lower():
            log(f"Skip delete (missing): {path}")
        else:
            log(f"Could not delete {path}: {exc}")


def upload(ftp: ftplib.FTP, remote: str, data: bytes) -> None:
    ftp.storbinary(f"STOR {remote}", io.BytesIO(data))
    log(f"Uploaded {remote} ({len(data)} bytes)")


def main() -> int:
    deploy_dir = (os.environ.get("DEPLOY_DIR") or os.environ.get("FTP_HINT") or "").strip()
    if not deploy_dir:
        log("DEPLOY_DIR / FTP_HINT empty — nothing to seed")
        return 0

    fixtures = Path(os.environ.get("ACORN_FIXTURE_DIR") or ".github/fixtures/acorn-cache")
    packages = fixtures / "packages.php"
    services = fixtures / "services.php"
    if not packages.is_file() or not services.is_file():
        log(f"Missing fixtures in {fixtures}")
        return 1

    ftp = connect()
    try:
        wp_content = wp_content_from_theme_dir(deploy_dir)
        cache_dir = join_ftp(wp_content, "cache/acorn/framework/cache")
        log(f"Acorn cache dir: {cache_dir}")

        for name in ("packages.php", "services.php"):
            try_delete(ftp, join_ftp(cache_dir, name))

        upload(ftp, join_ftp(cache_dir, "packages.php"), packages.read_bytes())
        upload(ftp, join_ftp(cache_dir, "services.php"), services.read_bytes())
        log("Seeded known-good Acorn manifests")
    finally:
        try:
            ftp.quit()
        except Exception:  # noqa: BLE001
            ftp.close()

    # Verify the front end after seeding
    import time
    import urllib.request

    url = os.environ.get("LIVE_URL", "https://matthummel.com/")
    for i in range(1, 4):
        try:
            req = urllib.request.Request(url + f"?mh_seed={i}", headers={"User-Agent": "mh-acorn-seed/1.0"})
            with urllib.request.urlopen(req, timeout=45) as resp:
                log(f"hit {url} attempt {i}: HTTP {resp.status}")
                if resp.status == 200:
                    break
        except Exception as exc:  # noqa: BLE001
            log(f"hit {url} attempt {i}: {type(exc).__name__}: {exc}")
        time.sleep(2)

    return 0


if __name__ == "__main__":
    sys.exit(main())
