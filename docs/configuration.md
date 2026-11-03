# Configuración desde WooCommerce

1. Instala y activa el ZIP del plugin.
2. Abre **WooCommerce → Ajustes → Pagos → BAC — Y2KWebs**.
3. Introduce **PowerTranz ID — CRC** y **Contraseña — CRC**, **PowerTranz ID — USD** y **Contraseña — USD**, o únicamente el par correspondiente a la moneda que utilizarás. Cada cuenta debe estar habilitada por BAC/PowerTranz para esa moneda.
4. Selecciona **Pruebas** para credenciales de staging o **Producción** para credenciales productivas. Cambia entorno y credenciales conjuntamente; el selector no transforma credenciales productivas en credenciales de prueba.
5. Mantén **Sólo administradores**, guarda y realiza una prueba coordinada con el titular. **Producción realiza cobros reales**; la visibilidad no impone un límite de importe.
6. Habilita el método para todos los clientes después de verificar la configuración y los resultados.

**No es necesario editar `wp-config.php` ni crear archivos de credenciales para la instalación normal.** La instalación comienza desactivada, en staging y visible solo para usuarios con permiso `manage_woocommerce`. CRC y USD se seleccionan según el pedido; no hay conversión de moneda.

## Contraseñas guardadas

Las contraseñas CRC y USD se guardan cifradas mediante AES-256-GCM en una opción separada que no se precarga. Los campos del formulario se muestran vacíos y la API de WooCommerce no devuelve el secreto ni su texto cifrado. Dejar un campo vacío conserva el valor existente; escribir otro lo sustituye sin alterar la contraseña de la otra moneda.

**Eliminar contraseñas guardadas** borra ambos secretos al guardar y desactiva BAC. Los identificadores del comercio se conservan. Para cambiar de comercio, sustituye también esos identificadores. No cambies de cuenta o entorno mientras haya intentos activos o resultados pendientes de conciliación.

El candidato migra automáticamente sus propios ajustes anteriores al almacenamiento cifrado, sin cambiar las credenciales. No importa datos de otra pasarela. El cifrado utiliza los salts de autenticación de WordPress: si se sustituyen, hay que eliminar las contraseñas guardadas y volver a introducirlas. Conserva copias privadas de seguridad de la instalación y no publiques sus opciones o archivos de configuración.

## Servidor y autenticación 3DS

Requiere HTTPS, OpenSSL con AES-256-GCM y MySQL/MariaDB con `GET_LOCK`. Excluye checkout, pago de pedidos y `wc-api/y2kwebs_bac_callback` de caché. El WAF debe admitir el POST bancario. No apliques filtros HTML que alteren los formularios o scripts del desafío SPI recibido del procesador.

La política permite `Y` y `A`. `U` permanece desactivado salvo decisión expresa del comercio acordada con el adquirente. `N`, `R` y los estados desconocidos impiden completar el pago. No actives `U` para resolver un rechazo de otra naturaleza. La aprobación financiera y la correlación de transacción, referencia, importe y moneda siguen siendo obligatorias.

## API administrativa de WooCommerce

La API nativa `/wp-json/wc/v3/payment_gateways/y2kwebs_bac` utiliza los permisos y la autenticación estándar de WooCommerce. Los campos `crc_password` y `usd_password` admiten sustitución mediante una escritura autorizada; sus valores se devuelven siempre vacíos. Un valor vacío conserva la contraseña. `clear_secrets: yes` elimina ambas y desactiva el método. Nunca pongas credenciales en URLs, incidencias ni ejemplos públicos.

## Alternativa opcional para instalaciones administradas

Las constantes o variables de entorno permiten administrar las credenciales fuera de la base de datos. **Son opcionales** y prevalecen sobre los valores introducidos en WooCommerce. Retíralas o actualízalas antes de cambiar de comercio desde el panel.

```php
define( 'Y2KWEBS_BAC_CRC_ID', 'YOUR_STAGING_CRC_ID' );
define( 'Y2KWEBS_BAC_CRC_PASSWORD', 'YOUR_STAGING_CRC_PASSWORD' );
define( 'Y2KWEBS_BAC_USD_ID', 'YOUR_STAGING_USD_ID' );
define( 'Y2KWEBS_BAC_USD_PASSWORD', 'YOUR_STAGING_USD_PASSWORD' );
```

Precedencia: constante, variable de entorno no vacía, ajuste de WooCommerce. Una constante vacía deshabilita esa credencial. Estos ejemplos son ficticios. No copies credenciales de otra instalación ni las incluyas en el repositorio.

## Endpoint avanzado

Deja **Raíz del API** vacío para usar el destino oficial del entorno seleccionado:

- Staging: `https://staging.ptranz.com/api/spi`.
- Producción: `https://gateway.ptranz.com/api/spi`.

`Y2KWEBS_BAC_API_ROOT` también permite una raíz personalizada mediante constante o variable de entorno. Es una alternativa avanzada, no un requisito de instalación. Ese destino recibe datos de pago: utiliza únicamente el autorizado por el procesador. HTTP, destinos privados y redirecciones HTTP se rechazan.

La desinstalación elimina los ajustes y el almacén cifrado de contraseñas de BAC. Conserva pedidos y referencias financieras; no modifica LAFISE ni otras pasarelas.
