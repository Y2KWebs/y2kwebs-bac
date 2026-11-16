"""Prepare eight WordPress.org PO uploads; never transmit or approve translations."""
from pathlib import Path
import hashlib
import json
import re
from build_translations import ROOT, read_po, build
from package import VERSION


def prepare(root=ROOT):
    root = Path(root)
    build(root, check=True)
    software = (root / 'languages/y2kwebs-bac-es_ES.po').read_text(encoding='utf-8')
    out = root / 'dist' / f'translations-{VERSION}'
    files = {}
    for locale in ('es_ES', 'es_CR'):
        readme = (root / f'translations/wordpress-org/readme-{locale}.po').read_text(encoding='utf-8')
        entries = read_po(readme)
        if len(entries) != 58 or any(not value for key, value in entries.items() if key):
            raise ValueError('Incomplete readme proposal')
        for original, translation in entries.items():
            if not original:
                continue
            for pattern in (r'https?://[^\s<>\"]+', r'</?[a-z][^>]*>'):
                if sorted(re.findall(pattern, original)) != sorted(re.findall(pattern, translation)):
                    raise ValueError('Readme proposal changed URLs or HTML tags')
        for branch in ('dev', 'stable'):
            for kind, text in (('software', software.replace('Language: es_ES', f'Language: {locale}')), ('readme', readme)):
                path = out / branch / locale / f'{kind}.po'
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(text, encoding='utf-8', newline='\n')
                files[path.relative_to(out).as_posix()] = hashlib.sha256(path.read_bytes()).hexdigest()
    (out / 'manifest.json').write_text(json.dumps({'version': VERSION, 'status': 'prepared; consult publication record for actual imports and approval', 'files': files}, indent=2)+'\n', encoding='utf-8')
    return out


if __name__ == '__main__':
    print(f'Prepared eight PO files: {prepare()}')
