#!/usr/bin/env python3
"""Build the GitHub Pages-only CHITS Modern mock demo."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "index.html"
OUT = ROOT / "_site" / "index.html"

html = SOURCE.read_text(encoding="utf-8")
old = """  // Patient registry and consultation workflows use the authenticated persistent pilot.
  // The legacy dashboard remains a demo for modules not yet migrated.
  if(['patients','patient-record','consult'].includes(pg)){
    window.location.href='/pilot/';
    return;
  }
"""
new = """  // GitHub Pages demo: keep navigation inside this static, synthetic-data mock.
  // The PHP-backed pilot remains available only through the local PHP router.
"""
if old not in html:
    raise SystemExit("Could not find the expected clinical navigation block; refusing to build a misleading demo.")
html = html.replace(old, new, 1)

notice = """
<style>
.pages-demo-notice{position:fixed;right:12px;bottom:12px;z-index:99999;max-width:min(440px,calc(100vw - 24px));padding:10px 13px;border:1px solid #c9a227;border-radius:8px;background:#fff8db;color:#5f4b00;font:12px/1.45 'IBM Plex Sans',sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.12)}
.pages-demo-notice strong{font-weight:600}
</style>
<div class="pages-demo-notice" role="note"><strong>PUBLIC MOCK DEMO</strong> · Synthetic sample data only. Changes are not saved to a server. Do not enter real patient information or use for clinical care or official reporting.</div>
"""
if "</body>" not in html:
    raise SystemExit("Could not find the document body.")
html = html.replace("</body>", notice + "\\n</body>", 1)
OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text(html, encoding="utf-8")
print(f"Built {OUT.relative_to(ROOT)} ({len(html)} characters)")
