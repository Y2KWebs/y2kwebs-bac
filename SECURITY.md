# Seguridad

Comunica vulnerabilidades en privado a **info@y2kwebs.com**, indicando versión, comportamiento afectado y pasos con datos ficticios. No envíes tarjetas, contraseñas, tokens bancarios, respuestas de producción ni datos de compradores. No publiques detalles explotables de una vulnerabilidad pendiente en una incidencia.

## Credenciales del comercio

Las contraseñas CRC y USD introducidas desde WooCommerce se cifran mediante AES-256-GCM en una opción separada sin autoload. Se utiliza una clave derivada del salt de autenticación de WordPress y un nonce aleatorio por cifrado. Los campos son de escritura: HTML y respuestas API nunca devuelven la contraseña ni su texto cifrado. Los identificadores de cuenta son ajustes administrativos visibles.

El formulario verifica permisos y nonce; la API usa la autorización estándar de WooCommerce. Dejar una contraseña vacía conserva la guardada. La eliminación explícita borra ambas y desactiva los pagos. La desinstalación elimina también el almacén cifrado. Al cambiar los salts hay que volver a introducir las contraseñas; conserva las copias de seguridad completas en privado. El cifrado en reposo no protege frente a quien controle simultáneamente WordPress, su base de datos y sus salts.

Las constantes y variables de entorno son alternativas opcionales para instalaciones administradas, no requisitos de uso. No incluyas valores reales en Git, capturas, incidencias, comandos públicos ni artefactos. La migración protege exclusivamente ajustes anteriores del propio candidato BAC y no importa otra pasarela.

## Datos de tarjeta y callback

La línea mantenida es 1.x. PowerTranz SPI directo transmite la tarjeta a través del servidor del comercio en memoria. El plugin no persiste PAN, CVV ni tokens SPI. Evita la captura de cuerpos HTTP en servidor, proxy, WAF y herramientas de monitorización, y confirma con el adquirente las obligaciones aplicables.

El callback comprueba clave de pedido, hash de estado aleatorio, intento y correlación de respuesta financiera. El banco no dispone de un nonce de navegador para ese POST. Nunca se construye el importe o la moneda de un cobro a partir de valores proporcionados por el callback.

Los envíos financieros se serializan mediante bloqueos de conexión a la base de datos. El intento se marca antes de enviar `/payment`. Un resultado incierto requiere conciliación manual; borrar metadatos para forzar otro cobro es inseguro.

## Revisión de contenido público

Se revisan código, documentación, capturas ficticias, candidatos Git y ZIP instalable. `scripts/check_public.py` detecta archivos privados y patrones conocidos de secretos; debe complementarse con una revisión del historial y de las referencias privadas disponibles para el mantenedor. Un resultado limpio no demuestra que un escáner pueda reconocer cualquier secreto futuro.

Las suites usan credenciales y tarjetas ficticias e interceptan el banco. No ejecutes fixtures en producción ni publiques exportaciones operativas. Los resultados bancarios se documentan sin comercios, compradores, tokens o referencias de transacciones reales.
