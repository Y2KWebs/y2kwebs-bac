"""Prepare local review material. This script never sends or publishes anything."""
import hashlib
import json
from pathlib import Path
from package import ROOT, FILES, SCREENSHOTS, SLUG, VERSION, package


def prepare(root=ROOT):
    root = Path(root).resolve()
    archive, digest = package(root)
    out = root / "dist" / "wordpress-org"
    expected = {"manifest.json"}
    entries = {}
    for prefix in ("svn/trunk", f"svn/tags/{VERSION}"):
        for name in FILES:
            entries[f"{prefix}/{name}"] = (root / name).read_bytes()
    for number, name in enumerate(SCREENSHOTS, 1):
        entries[f"svn/assets/screenshot-{number}{Path(name).suffix}"] = (root / name).read_bytes()
    expected.update(entries)
    if out.is_symlink() or (out.exists() and not out.is_dir()):
        raise ValueError("Unsafe preparation directory")
    if out.exists():
        for path in out.rglob("*"):
            if path.is_symlink() or (path.is_file() and path.relative_to(out).as_posix() not in expected):
                raise ValueError("Unexpected existing file in preparation directory")
    for name, data in entries.items():
        path = out / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(data)
    manifest = {"version": VERSION, "requested_slug": SLUG, "status": "prepared locally; not submitted", "archive": archive.name,
                "archive_sha256": digest, "files": {name: hashlib.sha256(data).hexdigest() for name, data in sorted(entries.items())}}
    (out / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")
    return out, digest


if __name__ == "__main__":
    out, digest = prepare()
    print(f"Prepared {out}; archive SHA-256 {digest}")
