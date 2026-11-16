# Publicación BAC 1.0.1 / 1.0.2 — 16 de noviembre de 2026

La versión activa en el directorio es **1.0.2**, publicada con interfaz inglesa y traducción española incluida. WordPress.org anuncia una demora de unas seis horas para distribuir la actualización automáticamente a las instalaciones; la descarga directa 1.0.2 ya estaba disponible y se verificó.

| Entrega | Evidencia |
| --- | --- |
| Código aprobado 1.0.1 y gráficos | [PR 1](https://github.com/Y2KWebs/y2kwebs-bac/pull/1), commit `681f2c2c14fa63dbd752a176fa79eff0c1d42e2d`, SVN r3805265 |
| Soporte bilingüe 1.0.2 | [PR 2](https://github.com/Y2KWebs/y2kwebs-bac/pull/2), commit `a5bc94d33758b5c318a87cfa2493ae6257c949ba`, SVN r3805290 |
| GitHub | [Release 1.0.1](https://github.com/Y2KWebs/y2kwebs-bac/releases/tag/v1.0.1), [release 1.0.2](https://github.com/Y2KWebs/y2kwebs-bac/releases/tag/v1.0.2) |
| WordPress.org | [Ficha pública](https://wordpress.org/plugins/y2kwebs-bac/), [SVN](https://plugins.svn.wordpress.org/y2kwebs-bac/) |
| CI 1.0.2 | [PR](https://github.com/Y2KWebs/y2kwebs-bac/actions/runs/43878622894), [push](https://github.com/Y2KWebs/y2kwebs-bac/actions/runs/43878552427), ambas completadas correctamente |

El ZIP 1.0.1 de CI/GitHub tiene SHA-256 `7cb6f39b61780863c18dfd011c1dad9278fef798a2c2c678c739f7e77a3cc5e4`. Sus archivos PHP, JS y CSS coinciden byte a byte con el ZIP aprobado por revisión (`1a0047ee86589a4a980f74a60151b0853cf28ce6d0353372db2401dd5f17dda7`); las diferencias corresponden a documentación. El ZIP 1.0.2 de CI/GitHub tiene SHA-256 `b3c391e660d31ba24b490bfd4f563228cb1a69c04cc49cf0f7f457a32f3dd05a`.

Se descargaron ambos ZIP desde WordPress.org y se confirmó la igualdad de nombres y contenido de todos los archivos respecto a los artefactos de CI y GitHub. La compresión de WordPress.org puede producir otro SHA-256 del contenedor ZIP. Se conservaron la release y etiqueta GitHub 1.0.0, el ZIP original local, tags/1.0.1 de SVN y los gráficos al publicar 1.0.2. Los PNG están en assets de SVN y quedan fuera del plugin instalable.

## Validación

- Sintaxis PHP 7.4, 8.2 y 8.4; contenido público; siete pruebas Python de empaquetado y traducciones; tres pruebas Node de scripts y Blocks.
- Matrices mínima (WordPress 6.9/WooCommerce 9/PHP 7.4) y moderna (WordPress 7.1.4/WooCommerce 11.2.1/PHP 8.2), con pedidos clásicos y HPOS. Smoke de pagos, 25 aserciones de credenciales, 78 de flujos financieros/seguridad, 62 de idiomas y callbacks HTTP en cada combinación.
- Las 62 aserciones incluyen seis locales, precedencia de un MO regional real, catálogo parcial, cambios/restauración de idioma de WordPress, idioma de tienda frente a preferencia de administrador y conservación de textos del comercio.
- Instalación/activación del ZIP extraído y Plugin Check 2.2.0 completo sin errores ni advertencias del plugin en CI.
- Comprobación visual de campos de pago clásico y Blocks en es_ES, es_CR, es_MX, es_AR, en_US y en_GB. Aprobación clásica con desafío visible en inglés y aprobación Blocks con retorno inmediato en español de Costa Rica, exclusivamente con banco simulado.
- Se mantienen documentados el aviso de carga temprana de traducciones del dominio WooCommerce en la matriz mínima y el aviso de su detector de dependencias `wc.wcBlocksData` al enviar Blocks. No se observaron errores de consola propios de BAC. No se silencian estos avisos externos.

No se hicieron cobros reales en esta publicación. La validación bancaria del proveedor en staging sigue pendiente.

## Traducciones oficiales

El proyecto de Translate WordPress tardó en aparecer tras el primer commit. Cuando se creó, solo ofrecía Development y Development Readme.

| Locale | Software Development | Readme Development | Stable / Stable Readme |
| --- | --- | --- | --- |
| es_ES | 84 Waiting, 0 avisos | 57 Waiting, 0 avisos | Proyectos todavía no disponibles |
| es_CR | 84 Waiting, 0 avisos | 57 Waiting, 0 avisos | Proyectos todavía no disponibles |

La importación de software es_ES añadió 0 propuestas porque WordPress ya había importado las 84 del PO incluido. Las otras importaciones añadieron 84 (software es_CR), 57 (readme es_ES) y 57 (readme es_CR). Se verificó ausencia de cadenas sin propuesta y de avisos en los cuatro destinos.

La cuenta puede importar propuestas con estado Waiting y no dispone de aprobación PTE para BAC. La API oficial de traducciones para 1.0.2 todavía devolvía `translations: []`. No se afirma disponibilidad de paquetes oficiales ni traducción oficial de la ficha.

Se conservan ocho archivos de importación reproducibles mediante `python scripts/prepare_translation_imports.py`; los cuatro de Stable quedan pendientes de que WordPress cree esos proyectos. Los originales del readme se extrajeron con el parser oficial de WordPress.org. Véanse los [catálogos y procedimiento](../translations/wordpress-org/README.md) y el [borrador PTE sin enviar](../translations/wordpress-org/pte-request.md).

Para completar la parte externa: comprobar la aparición de Stable, importar allí ambos catálogos para es_ES/es_CR, solicitar revisión/PTE con el borrador, y verificar las aprobaciones y la API de paquetes. El español incluido en el plugin funciona sin esperar esos pasos.
