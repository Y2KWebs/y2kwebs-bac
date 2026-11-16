# Desarrollo y pruebas

## Publicación 1.0.2 y traducciones

La versión aprobada 1.0.1 se publicó el 16 de noviembre de 2026 en GitHub y WordPress.org (SVN r3805265), con cabecera e iconos Y2KWebs. Sus etiquetas y paquetes se conservan.

El candidato 1.0.2 usa cadenas fuente en inglés e incluye `languages/y2kwebs-bac-es_ES.po` y `.mo` como respaldo para todos los locales españoles. Los paquetes oficiales regionales tienen prioridad por cadena. Los ajustes siguen el idioma del administrador; el checkout y sus peticiones AJAX siguen el idioma de la tienda. Los títulos y descripciones guardados por el comercio se conservan sin traducirlos ni sobrescribirlos. Las guías de usuario permanecen en español.

Las traducciones del directorio y los paquetes oficiales se gestionan en Translate WordPress y requieren revisión de la comunidad. Su importación no implica aprobación ni disponibilidad de paquetes oficiales. La validación bancaria en staging continúa pendiente; estas comprobaciones utilizan exclusivamente banco simulado.

Para preparar el candidato: `python scripts/build_translations.py --check`, `python scripts/check_public.py`, pruebas y `python scripts/prepare_wordpress_org.py`. El material SVN se genera en `dist/wordpress-org-1.0.2`; los PNG del directorio se publican en `/assets`, fuera del ZIP instalable.


Requiere Docker Compose y Python 3.9+. El proyecto de pruebas es independiente y publica solo `127.0.0.1:8088`. Todos los accesos locales son ficticios.

```text
docker compose -f tests/compose.yml up -d wordpress db
docker compose -f tests/compose.yml run --rm cli wp core install --url=http://localhost:8088 --title=Local --admin_user=localtester --admin_password=local-only-test-password --admin_email=test@example.invalid --skip-email
docker compose -f tests/compose.yml run --rm cli wp plugin install woocommerce --version=9.0.0 --activate
docker compose -f tests/compose.yml run --rm cli wp plugin activate y2kwebs-bac
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/y2kwebs-bac/tests/integration/smoke.php
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/y2kwebs-bac/tests/flows.php
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/y2kwebs-bac/tests/settings.php
```

Las suites interceptan el transporte bancario con respuestas ficticias. Nunca ejecutarlas en una tienda remota. Repita con HPOS desactivado y activado mediante las herramientas de sincronización de WooCommerce. La matriz mínima usa WordPress 6.9, WooCommerce 9.0 y PHP 7.4; la matriz moderna usa las versiones documentadas en validación. `BAC_WP_IMAGE` y `BAC_CLI_IMAGE` permiten seleccionar las imágenes PHP.

`tests/setup-ui.php` crea catálogo y páginas locales ficticias; `tests/ui-fixture.php` es un banco simulado exclusivo del entorno local. Ninguno se distribuye en el ZIP. Para inspección visual use el flujo checkout > formulario de tarjeta > banco simulado > pedido confirmado.

```text
python scripts/check_public.py
python -m unittest discover -s tests -p "test_*.py"
node --test tests/scripts.test.js
python scripts/prepare_wordpress_org.py
```

El empaquetador usa una lista explícita; rechaza rutas inseguras, symlinks y patrones conocidos de secretos. Compruebe también manualmente el contenido de Git y del ZIP. El escáner no garantiza detectar todos los secretos posibles.

El Compose de pruebas activa `WP_DEBUG`, `WP_DEBUG_LOG` y oculta errores al comprador. Para validar una instalación limpia, use un nombre de proyecto Compose nuevo, sin reutilizar los volúmenes de otra prueba. `tests/message-fixture.html` permite comprobar en navegador ventanas, orígenes, pedidos y destinos manipulados; utiliza el puerto local 8098 y queda fuera de la distribución. El banco simulado admite el ajuste local `y2kwebs_bac_local_bank_mode` con `challenge`, `immediate` y `decline`; no modifica ni se instala en tiendas remotas.

La integración permite añadir una política de comercio mediante `y2kwebs_bac_can_start_payment`: recibe `true` y un `WC_Order`; devuelve `true` para continuar o un `WP_Error` para impedir el intento antes del HTTP. No modifica validaciones bancarias. La acción `y2kwebs_bac_callback_rejected` recibe un `WP_Error` genérico sin datos bancarios.

Registre esa política con **dos argumentos aceptados**: `add_filter( 'y2kwebs_bac_can_start_payment', $callback, 10, 2 )`. El valor predeterminado de WordPress es uno; omitir el último parámetro provoca un error si el callback exige el pedido.

Extraiga el ZIP en un directorio descartable y móntelo sobre la ruta del plugin local para ejecutar `tests/release-smoke.php` y Plugin Check. Monte el smoke fuera de la carpeta distribuida. Ejecute `wp --require=/var/www/html/wp-content/plugins/plugin-check/cli.php plugin check y2kwebs-bac --format=csv` sin omitir categorías. La CI utiliza el ZIP extraído, bloquea HTTP bancario y rechaza errores y avisos.
