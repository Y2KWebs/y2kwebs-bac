"""Build an explicitly selected, reproducible installable plugin archive."""
from pathlib import Path
import hashlib
import re
import zipfile

ROOT = Path(__file__).resolve().parent.parent
VERSION = "1.0.0"
SLUG = "y2kwebs-bac"
IMAGES = frozenset(f"docs/images/{name}.jpg" for name in ("checkout", "settings", "blocks"))
FILES = (
    "y2kwebs-bac.php", "index.php", "uninstall.php", "LICENSE", "readme.txt", "README.md", "SECURITY.md", "CONTRIBUTING.md",
    "includes/class-powertranz-api-client.php", "includes/class-powertranz-authentication-policy.php",
    "includes/class-powertranz-secrets.php", "includes/class-powertranz-settings.php",
    "includes/class-powertranz-country-codes.php", "includes/class-powertranz-currency-config.php",
    "includes/class-powertranz-diagnostics.php", "includes/class-powertranz-gateway.php",
    "blocks/class-y2kwebs-bac-blocks-support.php", "templates/payment-fields.php",
    "assets/css/checkout.css", "assets/js/checkout.js", "assets/js/blocks.js",
    "docs/configuration.md", "docs/development.md", "docs/pilot.md", "docs/validation.md", "docs/wordpress-org.md",
    "docs/architecture.md", "docs/releasing.md",
    *sorted(IMAGES),
)
SECRET_PATTERN = re.compile(
    r"-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----"
    r"|gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{40,}"
    r"|AKIA[0-9A-Z]{16}|sk_live_[A-Za-z0-9]{16,}"
)


def package(root=ROOT):
    root = Path(root).resolve()
    contents = {}
    for name in FILES:
        path = root / name
        if path.is_symlink() or not path.resolve().is_relative_to(root) or not path.is_file():
            raise ValueError(f"Missing or unsafe package file: {name}")
        data = path.read_bytes()
        if SECRET_PATTERN.search(data.decode("utf-8", errors="ignore")):
            raise ValueError(f"Possible secret in {name}; value withheld")
        contents[name] = data
    header = contents[f"{SLUG}.php"].decode("utf-8")
    readme = contents["readme.txt"].decode("utf-8")
    if not re.search(r"Version:\s*" + re.escape(VERSION) + r"\s", header) or not re.search(r"^Stable tag:\s*" + re.escape(VERSION) + r"\s*$", readme, re.M):
        raise ValueError("Version and stable tag must match the package version")
    if "Update URI:" in header or f"Text Domain: {SLUG}" not in header:
        raise ValueError("WordPress.org identity mismatch")
    out = root / "dist"
    if out.is_symlink():
        raise ValueError("Unsafe output directory")
    out.mkdir(exist_ok=True)
    archive = out / f"{SLUG}-{VERSION}.zip"
    if archive.is_symlink():
        raise ValueError("Unsafe archive path")
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED) as target:
        for name, data in sorted(contents.items()):
            info = zipfile.ZipInfo(f"{SLUG}/{name}", date_time=(2026, 1, 1, 0, 0, 0))
            info.create_system = 3  # Stable Unix metadata on Windows and Linux alike.
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            target.writestr(info, data)
    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    archive.with_suffix(".zip.sha256").write_text(f"{digest}  {archive.name}\n", encoding="utf-8")
    return archive, digest


if __name__ == "__main__":
    archive, digest = package()
    print(f"Prepared {archive.name}; SHA-256 {digest}")
