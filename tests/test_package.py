from pathlib import Path
import hashlib
import importlib.util
import sys
import tempfile
import unittest
import zipfile

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / "scripts"))
from package import ROOT, FILES, SLUG, package
from prepare_wordpress_org import prepare


class PackageTests(unittest.TestCase):
    def fixture(self, root):
        for name in FILES:
            path = root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes((ROOT / name).read_bytes())

    def test_allowlist_reproducible(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            self.fixture(root)
            (root / ".env").write_text("not-public")
            archive, digest = package(root)
            first = archive.read_bytes()
            self.assertEqual(digest, package(root)[1])
            self.assertEqual(first, archive.read_bytes())
            with zipfile.ZipFile(archive) as source:
                self.assertEqual(set(source.namelist()), {f"{SLUG}/{name}" for name in FILES})
                for name in FILES:
                    self.assertEqual((root / name).read_bytes(), source.read(f"{SLUG}/{name}"))

    def test_missing_file_fails(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory); self.fixture(root)
            (root / FILES[0]).unlink()
            with self.assertRaises(ValueError): package(root)

    def test_version_mismatch_fails(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory); self.fixture(root)
            path = root / "readme.txt"; path.write_text(path.read_text().replace("Stable tag: 1.0.0", "Stable tag: 9.9.9"))
            with self.assertRaises(ValueError): package(root)

    def test_svn_matches_archive(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory); self.fixture(root)
            out, digest = prepare(root)
            archive = root / "dist" / "y2kwebs-bac-1.0.0.zip"
            self.assertEqual(hashlib.sha256(archive.read_bytes()).hexdigest(), digest)
            with zipfile.ZipFile(archive) as source:
                for name in FILES:
                    self.assertEqual(source.read(f"{SLUG}/{name}"), (out / "svn/trunk" / name).read_bytes())
                    self.assertEqual(source.read(f"{SLUG}/{name}"), (out / "svn/tags/1.0.0" / name).read_bytes())
            (out / "unexpected.txt").write_text("stale")
            with self.assertRaises(ValueError): prepare(root)


if __name__ == "__main__": unittest.main()
