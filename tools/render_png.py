#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Render SVG -> PNG tanpa crop: bungkus SVG landscape jadi portrait (padding atas/bawah),
render via qlmanage, lalu crop bounding-box non-putih dengan PIL."""
import re, subprocess, sys, tempfile, os
from PIL import Image, ImageChops

def render(svg_path, png_path, size=2600):
    s = open(svg_path).read()
    m = re.search(r'width="(\d+)" height="(\d+)"', s)
    w, h = int(m.group(1)), int(m.group(2))
    pad = max(0, (w - h) // 2 + 20)
    h2 = h + 2 * pad
    m = re.search(r'<svg[^>]*>', s)
    head = s[:m.end()]
    body = s[m.end():].rsplit('</svg>', 1)[0]
    head = re.sub(r'width="\d+" height="\d+"', f'width="{w}" height="{h2}"', head, count=1)
    head = re.sub(r'viewBox="[^"]*"', f'viewBox="0 0 {w} {h2}"', head, count=1)
    s3 = (head + f'<rect width="{w}" height="{h2}" fill="#FFFFFF"/>\n'
           f'<g transform="translate(0,{pad})">\n{body}</g>\n</svg>\n')
    with tempfile.TemporaryDirectory() as td:
        tall = os.path.join(td, "tall.svg")
        open(tall, "w").write(s3)
        subprocess.run(["qlmanage", "-t", "-s", str(size), "-o", td, tall],
                       capture_output=True)
        rendered = os.path.join(td, "tall.svg.png")
        im = Image.open(rendered).convert("RGB")
        # bbox non-putih
        diff = ImageChops.difference(im, Image.new("RGB", im.size, (255, 255, 255)))
        bbox = diff.getbbox()
        if bbox:
            x0, y0, x1, y1 = bbox
            mx, my = 8, 8
            im = im.crop((max(0, x0 - mx), max(0, y0 - my),
                          min(im.width, x1 + mx), min(im.height, y1 + my)))
        os.makedirs(os.path.dirname(png_path), exist_ok=True)
        im.save(png_path)
        print(f"{svg_path} -> {png_path} ({im.size[0]}x{im.size[1]})")

if __name__ == "__main__":
    base = os.path.join(os.path.dirname(__file__), "..", "lampiran")
    render(os.path.join(base, "erd", "ERD.svg"), os.path.join(base, "erd", "png", "ERD.svg.png"))
    for f in sorted(os.listdir(os.path.join(base, "wireframes", "svg"))):
        if f.endswith(".svg"):
            render(os.path.join(base, "wireframes", "svg", f),
                   os.path.join(base, "wireframes", "png", f.replace(".svg", ".png")))
