# Arquitectura y flujo de pago

El arranque registra la pasarela WooCommerce, el callback independiente de la sesión de compra y la integración de bloques. Las clases usan `Y2KWEBS_BAC_`, la pasarela `y2kwebs_bac` y los metadatos `_y2kwebs_bac_*` para convivir con otras integraciones.

1. El checkout clásico o de bloques recoge la tarjeta mediante HTTPS. El servidor valida los campos y construye una solicitud SPI `/sale` con la cuenta CRC o USD del pedido.
2. Un bloqueo de base de datos serializa los intentos del pedido. Un pedido pagado, un intento activo o un resultado financiero incierto no puede iniciar otro envío financiero.
3. Una respuesta SPI `SP4` correlacionada permite presentar el HTML 3DS del procesador. El callback exige la clave del pedido, un estado aleatorio de un solo uso y los identificadores del intento.
4. Solo los estados 3DS permitidos pueden llegar a `/payment`. El cuerpo es el token SPI exacto entre comillas JSON; esta llamada no lleva las cabeceras de credenciales del comercio.
5. Antes de `/payment` se persiste `payment_sending`. Un fallo de transporte o una respuesta contradictoria deja el pedido en espera de revisión e impide repetir el cobro.
6. Solo `Approved: true`, ISO `00` y coincidencia de transacción, referencia, importe y moneda completan el pedido. Los rechazos definitivos se conservan en sus notas.

Un reintento después de un rechazo definitivo crea otro estado y otra referencia. Limpia los diagnósticos del intento anterior y conserva las notas históricas. Un callback anterior no puede completar el intento nuevo.

## Credenciales y datos

`Y2KWEBS_BAC_Settings` protege las escrituras del formulario nativo y de la API WooCommerce. Las contraseñas se cifran en `y2kwebs_bac_secrets`, sin autoload; el ajuste general conserva los identificadores y campos de configuración, con contraseñas vacías. `Y2KWEBS_BAC_Currency_Config` resuelve la precedencia opcional constante → entorno → administrador y selecciona la cuenta por moneda.

`Y2KWEBS_BAC_API_Client` envía los datos bancarios. `Y2KWEBS_BAC_Diagnostics` admite únicamente campos saneados para las notas y registros. El plugin no persiste PAN, CVV ni tokens SPI; el HTML 3DS y el hash del estado se eliminan al finalizar el intento.

El callback no utiliza un nonce de navegador porque procede del banco. Su autorización combina el estado, el pedido, el intento y la respuesta financiera. Los ajustes sí utilizan los permisos y nonces de WooCommerce; el guardado directo del formulario verifica además `manage_woocommerce` y `woocommerce-settings`.

SPI directo transmite la tarjeta a través del servidor del comercio en memoria. El cifrado de contraseñas guardadas no convierte ese flujo en cifrado de tarjeta en el navegador. La tienda debe acordar sus obligaciones PCI DSS con el adquirente.
