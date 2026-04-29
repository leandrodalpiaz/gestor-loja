#!/usr/bin/env python3
"""
Generate tenant PWA icons for Gestor-Loja.

Priority per tenant:
1) logo.svg  -> logo-192.png + logo-512.png (requires cairosvg + cairo runtime)
2) logo.png  -> logo-192.png + logo-512.png (Pillow only)

Usage:
    python scripts/generate_pwa_icons.py
    python scripts/generate_pwa_icons.py --tenant loja-teste
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TENANTS_DIR = ROOT / "public" / "assets" / "tenants"
SIZES = (192, 512)


def resize_png(source: Path, out_path: Path, size: int) -> None:
    from PIL import Image

    with Image.open(source) as image:
        rgba = image.convert("RGBA")
        resized = rgba.resize((size, size), Image.LANCZOS)
        resized.save(out_path, format="PNG")


def render_svg(source: Path, out_path: Path, size: int) -> None:
    import cairosvg  # type: ignore

    cairosvg.svg2png(
        url=str(source),
        write_to=str(out_path),
        output_width=size,
        output_height=size,
    )


def generate_for_tenant(tenant_dir: Path) -> tuple[bool, str]:
    svg = tenant_dir / "logo.svg"
    png = tenant_dir / "logo.png"

    # First choice: SVG
    if svg.exists():
        try:
            for size in SIZES:
                render_svg(svg, tenant_dir / f"logo-{size}.png", size)
            return True, "ok (svg)"
        except Exception as exc:  # noqa: BLE001
            if png.exists():
                try:
                    for size in SIZES:
                        resize_png(png, tenant_dir / f"logo-{size}.png", size)
                    return True, f"ok (png fallback, svg failed: {exc.__class__.__name__})"
                except Exception as png_exc:  # noqa: BLE001
                    return False, f"erro png fallback: {png_exc}"
            return False, f"erro svg: {exc}"

    # Second choice: PNG only
    if png.exists():
        try:
            for size in SIZES:
                resize_png(png, tenant_dir / f"logo-{size}.png", size)
            return True, "ok (png)"
        except Exception as exc:  # noqa: BLE001
            return False, f"erro png: {exc}"

    return False, "ignorado (sem logo.svg e sem logo.png)"


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate PWA icons per tenant.")
    parser.add_argument("--tenant", help="Tenant slug (folder name under public/assets/tenants)")
    args = parser.parse_args()

    if not TENANTS_DIR.exists():
        print(f"diretorio nao encontrado: {TENANTS_DIR}")
        return 1

    if args.tenant:
        targets = [TENANTS_DIR / args.tenant]
    else:
        targets = [p for p in TENANTS_DIR.iterdir() if p.is_dir()]

    if not targets:
        print("nenhum tenant encontrado.")
        return 1

    ok_count = 0
    fail_count = 0

    for tenant in targets:
        if not tenant.exists():
            print(f"[FAIL] {tenant.name}: tenant nao encontrado")
            fail_count += 1
            continue

        ok, msg = generate_for_tenant(tenant)
        if ok:
            print(f"[OK]   {tenant.name}: {msg}")
            ok_count += 1
        else:
            print(f"[FAIL] {tenant.name}: {msg}")
            fail_count += 1

    print(f"\nresumo: ok={ok_count} fail={fail_count}")
    return 0 if fail_count == 0 else 2


if __name__ == "__main__":
    sys.exit(main())

