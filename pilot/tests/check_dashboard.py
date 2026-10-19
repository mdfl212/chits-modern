#!/usr/bin/env python3
"""Validate the legacy dashboard's inline JavaScript syntax without executing it."""
from pathlib import Path
import re
import subprocess
import tempfile

html = Path("index.html").read_text(encoding="utf-8")
scripts = re.findall(r"<script\b(?![^>]*\bsrc=)[^>]*>(.*?)</script\s*>", html, flags=re.I | re.S)
if not scripts:
    raise SystemExit("FAIL: no inline scripts found in index.html")

with tempfile.TemporaryDirectory(prefix="chits-dashboard-check-") as tmp:
    checked = 0
    for i, script in enumerate(scripts, start=1):
        if not script.strip():
            continue
        path = Path(tmp) / f"dashboard-inline-{i}.js"
        path.write_text(script, encoding="utf-8")
        result = subprocess.run(["node", "--check", str(path)], text=True, capture_output=True)
        if result.returncode:
            print(result.stdout, end="")
            print(result.stderr, end="")
            raise SystemExit(f"FAIL: inline dashboard script {i} has a JavaScript syntax error")
        checked += 1
    if not checked:
        raise SystemExit("FAIL: inline scripts were found but contained no JavaScript")
print(f"PASS: syntax checked {checked} inline dashboard script(s)")
