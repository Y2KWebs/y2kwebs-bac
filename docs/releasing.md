# Preparación de una release

La primera versión prevista es `1.0.0`. Mientras staging siga pendiente, el repositorio y la release candidata permanecen privados. El mantenedor ha aplazado expresamente el envío a WordPress.org.

1. Revisa las versiones de la cabecera, `readme.txt` y `scripts/package.py`. Comprueba que la documentación describe la cobertura real en [validación](validation.md).
2. Ejecuta las regresiones de pagos y credenciales indicadas en [desarrollo](development.md), con banco simulado y ambos almacenamientos. Revisa también el contenido y el historial de Git; los escáneres no reconocen todos los secretos posibles.
3. Ejecuta `python scripts/check_public.py` y `python -m unittest discover -s tests -p 'test_*.py'`.
4. Ejecuta `python scripts/prepare_wordpress_org.py`. El ZIP instalable utiliza una lista explícita y metadatos reproducibles. Se generan SHA-256 y material SVN local con manifiesto de integridad.
5. Extrae ese ZIP y ejecuta activación, smoke y Plugin Check sobre el contenido extraído, sin excluir categorías. Revisa las capturas: deben contener solo datos ficticios.
6. Comprueba la CI de los mínimos y la matriz actual. El ZIP de CI, el local y el adjunto al borrador deben coincidir byte a byte.
7. Cuando se cierren los requisitos de validación, publica el repositorio y la release `v1.0.0` con el ZIP instalable y su SHA-256. El ZIP automático de código fuente de GitHub no sustituye ese artefacto.

La preparación genera `trunk`, `tags/1.0.0` y capturas para revisión; no envía nada a WordPress.org. El mantenedor decide cuándo retomar la [guía de envío](wordpress-org.md) y realiza personalmente la solicitud.

Mantén credenciales, respaldos, respuestas bancarias originales, capturas operativas y datos de compradores fuera de Git, los artefactos de CI y las releases. En la publicación solo se incluyen resultados saneados.
