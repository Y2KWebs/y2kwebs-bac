from pathlib import Path
import sys, unittest, re, io, gettext
sys.path.insert(0,str(Path(__file__).resolve().parent.parent/'scripts'))
from build_translations import ROOT, build, read_po, compile_mo
class TranslationTests(unittest.TestCase):
    def test_complete_reproducible_and_placeholders(self):
        self.assertGreater(build(check=True),80)
        entries=read_po((ROOT/'languages/y2kwebs-bac-es_ES.po').read_text(encoding='utf8'))
        pattern=r'%(?:\d+\$)?[sdf]'
        for source,translation in entries.items():
            if source:self.assertEqual(sorted(re.findall(pattern,source)),sorted(re.findall(pattern,translation)),source)
        catalog=gettext.GNUTranslations(io.BytesIO(compile_mo(entries)))
        self.assertEqual(catalog.gettext('Cardholder name'),'Nombre del titular')
        self.assertEqual(catalog.gettext('MM/YY'),'MM/AA')
    def test_reject_fuzzy_or_duplicate(self):
        for source in ['#, fuzzy\nmsgid "A"\nmsgstr "B"','msgid "A"\nmsgstr "B"\n\nmsgid "A"\nmsgstr "C"']:
            with self.assertRaises(ValueError):read_po(source)
