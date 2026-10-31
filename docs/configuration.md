# Configuración

Requiere una cuenta BAC Costa Rica habilitada para PowerTranz/FAC SPI. CRC y USD pueden tener credenciales diferentes. No existe conversión de moneda; se elige la cuenta según el pedido.

En WooCommerce > Ajustes > Pagos > BAC — Y2KWebs configure entorno, visibilidad y cuentas. La instalación comienza desactivada, en staging y solo para administradores con permiso `manage_woocommerce`.

Para configuración privada de servidor, defina estas constantes en `wp-config.php` o variables de entorno con los mismos nombres:

```php
define( 'Y2KWEBS_BAC_CRC_ID', 'YOUR_STAGING_CRC_ID' );
define( 'Y2KWEBS_BAC_CRC_PASSWORD', 'YOUR_STAGING_CRC_PASSWORD' );
define( 'Y2KWEBS_BAC_USD_ID', 'YOUR_STAGING_USD_ID' );
define( 'Y2KWEBS_BAC_USD_PASSWORD', 'YOUR_STAGING_USD_PASSWORD' );
```

Precedencia: constante, variable de entorno no vacía, ajuste de WooCommerce. Una constante vacía deshabilita esa credencial. Los ajustes no deben compartirse en exportaciones o capturas. Mantenga credenciales separadas por entorno y cambie entorno y credenciales conjuntamente.

Los endpoints predeterminados son `https://staging.ptranz.com/api/spi` y `https://gateway.ptranz.com/api/spi`. La raíz personalizada, constante o variable `Y2KWEBS_BAC_API_ROOT`, es una configuración avanzada que recibe datos de pago: úsela únicamente con un destino autorizado. HTTP, destinos privados y redirecciones HTTP se rechazan.

La política 3DS permite Y/A. U permanece desactivado salvo decisión expresa del comercio acordada con el adquirente. No active esa opción para resolver un rechazo de otra naturaleza.

Use HTTPS y excluya checkout, pago de pedidos y `wc-api/y2kwebs_bac_callback` de caché. El WAF debe admitir el POST bancario. El desafío SPI utiliza el HTML recibido por HTTPS del procesador; no modifique sus formularios o scripts con filtros de HTML genéricos que impidan el 3DS.

La desinstalación elimina los ajustes de esta pasarela; conserva los pedidos y referencias financieras. No se importan datos de otros gateways.
