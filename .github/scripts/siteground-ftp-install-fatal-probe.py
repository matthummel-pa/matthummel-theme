#!/usr/bin/env python3
"""Install theme fatal probe into wp-content/mu-plugins over FTP, then hit the site."""

from __future__ import annotations

import ftplib
import io
import os
import socket
import sys
import time
import urllib.request


def log(msg: str) -> None:
    print(msg, flush=True)


def connect() -> ftplib.FTP:
    raw = os.environ["FTP_HOST"].strip()
    host = raw.replace("ftp://", "").replace("ftps://", "").split("/")[0]
    port = 21
    if ":" in host:
        host, _, port_s = host.rpartition(":")
        port = int(port_s)
    infos = socket.getaddrinfo(host, port, socket.AF_INET, socket.SOCK_STREAM)
    target = infos[0][4][0] if infos else host
    ftp = ftplib.FTP(timeout=45)
    ftp.connect(target, port, timeout=45)
    ftp.login(os.environ["FTP_USER"], os.environ["FTP_PASS"])
    ftp.set_pasv(True)
    log("FTP login ok")
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
    idx = path.find("wp-content/themes/")
    if idx < 0:
        raise RuntimeError(f"bad theme dir: {theme_dir}")
    return path[:idx] + "wp-content"


def ensure_dir(ftp: ftplib.FTP, path: str) -> None:
    current = ftp.pwd()
    built = ""
    for part in path.strip("/").split("/"):
        built = join_ftp(built, part) if built else "/"+part if path.startswith("/") else part
        if path.startswith("/") and not built.startswith("/"):
            built = "/" + built
        try:
            ftp.cwd(built)
        except ftplib.error_perm:
            ftp.mkd(built)
            ftp.cwd(built)
    ftp.cwd(current)


def main() -> int:
    deploy_dir = (os.environ.get("DEPLOY_DIR") or "").strip()
    if not deploy_dir:
        log("DEPLOY_DIR empty")
        return 0

    local = os.path.join(os.getcwd(), "mu-plugins", "mh-fatal-probe.php")
    if not os.path.isfile(local):
        log(f"missing {local}")
        return 1

    body = open(local, "rb").read()
    wp_content = wp_content_from_theme_dir(deploy_dir)
    mu_dir = join_ftp(wp_content, "mu-plugins")
    remote = join_ftp(mu_dir, "mh-fatal-probe.php")

    ftp = connect()
    try:
        ensure_dir(ftp, mu_dir)
        ftp.storbinary(f"STOR {remote}", io.BytesIO(body))
        log(f"Uploaded {remote} ({len(body)} bytes)")
    finally:
        try:
            ftp.quit()
        except Exception:  # noqa: BLE001
            ftp.close()

    # Trigger PHP so the shutdown handler can write mh-last-fatal.txt
    url = os.environ.get("LIVE_URL", "https://matthummel.com/")
    for i in range(1, 4):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "mh-fatal-probe/1.0"})
            with urllib.request.urlopen(req, timeout=30) as resp:
                log(f"hit {url} attempt {i}: HTTP {resp.status}")
        except Exception as exc:  # noqa: BLE001
            log(f"hit {url} attempt {i}: {type(exc).__name__}: {exc}")
        time.sleep(1)

    return 0


if __name__ == "__main__":
    sys.exit(main())
