# Desarrollo y pruebas

Requiere Docker Compose y Python 3.9+. El proyecto de pruebas es independiente y publica solo `127.0.0.1:8088`. Todos los accesos locales son ficticios.

```text
docker compose -f tests/compose.yml up -d wordpress db
docker compose -f tests/compose.yml run --rm cli wp core install --url=http://localhost:8088 --title=Local --admin_user=localtester --admin_password=local-only-test-password --admin_email=test@example.invalid --skip-email
docker compose -f tests/compose.yml run --rm cli wp plugin install woocommerce --version=9.0.0 --activate
docker compose -f tests/compose.yml run --rm cli wp plugin activate y2kwebs-bac
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/y2kwebs-bac/tests/integration/smoke.php
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/y2kwebs-bac/tests/flows.php
```

Las suites interceptan el transporte bancario con respuestas ficticias. Nunca ejecutarlas en una tienda remota. Repita con HPOS desactivado y activado mediante las herramientas de sincronización de WooCommerce. La matriz mínima usa WordPress 6.9, WooCommerce 9.0 y PHP 7.4; la matriz moderna usa las versiones documentadas en validación. `BAC_WP_IMAGE` y `BAC_CLI_IMAGE` permiten seleccionar las imágenes PHP.

`tests/setup-ui.php` crea catálogo y páginas locales ficticias; `tests/ui-fixture.php` es un banco simulado exclusivo del entorno local. Ninguno se distribuye en el ZIP. Para inspección visual use el flujo checkout > formulario de tarjeta > banco simulado > pedido confirmado.

```text
python scripts/check_public.py
python -m unittest discover -s tests -p "test_*.py"
python scripts/prepare_wordpress_org.py
```

El empaquetador usa una lista explícita; rechaza rutas inseguras, symlinks y patrones conocidos de secretos. Compruebe también manualmente el contenido de Git y del ZIP. El escáner no garantiza detectar todos los secretos posibles.

La integración permite añadir una política de comercio mediante `y2kwebs_bac_can_start_payment`: recibe `true` y un `WC_Order`; devuelve `true` para continuar o un `WP_Error` para impedir el intento antes del HTTP. No modifica validaciones bancarias. La acción `y2kwebs_bac_callback_rejected` recibe un `WP_Error` genérico sin datos bancarios.

Registre esa política con **dos argumentos aceptados**: `add_filter( 'y2kwebs_bac_can_start_payment', $callback, 10, 2 )`. El valor predeterminado de WordPress es uno; omitir el último parámetro provoca un error si el callback exige el pedido.

Extraiga el ZIP en un directorio descartable y móntelo sobre la ruta del plugin local para ejecutar `tests/release-smoke.php` y Plugin Check. Monte el smoke fuera de la carpeta distribuida. Ejecute `wp --require=/var/www/html/wp-content/plugins/plugin-check/cli.php plugin check y2kwebs-bac --format=csv` sin omitir categorías. La CI utiliza el ZIP extraído, bloquea HTTP bancario y rechaza errores y avisos.
