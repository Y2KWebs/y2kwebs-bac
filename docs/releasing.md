# Preparación de una release

## Publicación 1.0.2 y traducciones

La versión aprobada 1.0.1 se publicó el 16 de noviembre de 2026 en GitHub y WordPress.org (SVN r3805265), con cabecera e iconos Y2KWebs. Sus etiquetas y paquetes se conservan.

El candidato 1.0.2 usa cadenas fuente en inglés e incluye `languages/y2kwebs-bac-es_ES.po` y `.mo` como respaldo para todos los locales españoles. Los paquetes oficiales regionales tienen prioridad por cadena. Los ajustes siguen el idioma del administrador; el checkout y sus peticiones AJAX siguen el idioma de la tienda. Los títulos y descripciones guardados por el comercio se conservan sin traducirlos ni sobrescribirlos. Las guías de usuario permanecen en español.

Las traducciones del directorio y los paquetes oficiales se gestionan en Translate WordPress y requieren revisión de la comunidad. Su importación no implica aprobación ni disponibilidad de paquetes oficiales. La validación bancaria en staging continúa pendiente; estas comprobaciones utilizan exclusivamente banco simulado.

Para preparar el candidato: `python scripts/build_translations.py --check`, `python scripts/check_public.py`, pruebas y `python scripts/prepare_wordpress_org.py`. El material SVN se genera en `dist/wordpress-org-1.0.2`; los PNG del directorio se publican en `/assets`, fuera del ZIP instalable.


### Antecedentes de la solicitud 1.0.0 / 1.0.1

La primera versión pública es `1.0.0`, publicada en GitHub el 3 de noviembre de 2026. Se retoma la preparación para WordPress.org por indicación del mantenedor, manteniendo staging como limitación documentada. La solicitud al directorio se presentó el 3 de noviembre de 2026, con el slug asignado `y2kwebs-bac`. El revisor pidió correcciones el 6 de noviembre; 1.0.1 se subió el 11 de noviembre a esa misma solicitud y queda pendiente la respuesta del mantenedor al correo de revisión.

1. Revisa las versiones de la cabecera, `readme.txt` y `scripts/package.py`. Comprueba que la documentación describe la cobertura real en [validación](validation.md).
2. Ejecuta las regresiones de pagos y credenciales indicadas en [desarrollo](development.md), con banco simulado y ambos almacenamientos. Revisa también el contenido y el historial de Git; los escáneres no reconocen todos los secretos posibles.
3. Ejecuta `python scripts/check_public.py` y `python -m unittest discover -s tests -p 'test_*.py'`.
4. Ejecuta `python scripts/prepare_wordpress_org.py`. El ZIP instalable utiliza una lista explícita y metadatos reproducibles. Se generan SHA-256 y material SVN local con manifiesto de integridad.
5. Extrae ese ZIP y ejecuta activación, smoke y Plugin Check sobre el contenido extraído, sin excluir categorías. Revisa las capturas: deben utilizar datos ficticios o contenido aportado y autorizado expresamente por su titular; nunca credenciales ni datos de tarjeta.
6. Comprueba la CI de los mínimos y la matriz actual. El ZIP de CI, el local y el adjunto al borrador deben coincidir byte a byte.
7. La release `v1.0.0` de GitHub ya está publicada: conserva su etiqueta y sus adjuntos. El candidato 1.0.1 incorpora cambios de recursos 3DS y documentación, con código y SHA-256 distintos. Antes del envío registra esa diferencia; no sustituyas silenciosamente la release existente. El ZIP automático de código fuente de GitHub no sustituye el paquete instalable.

La preparación genera `dist/wordpress-org-1.0.1` con `trunk`, `tags/1.0.1` y capturas para revisión; no envía nada a WordPress.org. Sigue la [guía de envío](wordpress-org.md) cuando se vaya a presentar la solicitud. Se mantiene la interfaz en español y el `readme.txt` en inglés para revisión, sin catálogos de traducción ni solicitudes PTE en esta etapa.

Mantén credenciales, respaldos, respuestas bancarias originales, capturas operativas no autorizadas y datos de compradores no autorizados fuera de Git, los artefactos de CI y las releases. En la publicación solo se incluyen resultados saneados.
