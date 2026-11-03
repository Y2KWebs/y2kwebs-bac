# Validación de 1.0.0

Estado: candidato 1.0.0 con aprobaciones reales Visa y Mastercard en USD y CRC, confirmadas por el mantenedor también en el portal bancario. Sesiones productivas cerradas y BAC desactivado en la demo. Staging pendiente. El mantenedor ha aplazado expresamente el envío a WordPress.org; no hay release pública ni solicitud enviada. Registro actualizado el 3 de noviembre de 2026.

El flujo bancario parte de una implementación mantenida con experiencia operativa previa. Ese antecedente no acredita pruebas de todas las combinaciones del paquete público.

## Pruebas locales

| Matriz | Versiones | Resultado |
|---|---|---|
| Mínima | WordPress 6.9.4, WooCommerce 9.0.0, PHP 7.4.33 | Smoke, 78 aserciones de flujos/seguridad y callback HTTP superados en CI, con HPOS desactivado y activado |
| Actual | WordPress 7.1, WooCommerce 11.2.1, PHP 8.2.35 | Smoke, flujos/seguridad y callback HTTP superados con ambos almacenamientos; la última ejecución con HPOS incluye 78 aserciones |
| ZIP extraído | WordPress 7.1, WooCommerce 11.2.1, PHP 8.2.35 | 11 aserciones de paquete superadas; HTTP externo bloqueado |
| Empaquetado | Python 3.11 | 4 pruebas: lista explícita, reproducibilidad, archivos/versiones y coincidencia SVN/ZIP |
| Plugin Check | 2.1.0, todas las categorías, ZIP extraído | Sin errores ni avisos reportados |

Los flujos automatizados cubren selección CRC/USD, decimales, Y/A/U/N/R, correlación manipulada, tokens alterados, concurrencia, duplicados, caducidad, rechazo financiero y resultados inciertos. El transporte bancario se simula. Las capturas públicas contienen exclusivamente datos ficticios.

La CI del commit inicial superó sus cinco trabajos: sintaxis con PHP 7.4/8.2/8.4, las matrices mínima y moderna (esta última con WordPress 7.1.4 y WooCommerce 11.2.1), ambos almacenamientos, callback HTTP, paquete extraído y Plugin Check. El empaquetado fija además los metadatos de plataforma del ZIP para generar el mismo archivo en Windows y Linux. Se comprobó igualdad byte a byte con el artefacto descargado de esa ejecución. La comprobación local de convivencia registró BAC, LAFISE y la pasarela de referencia con identificadores independientes; el callback HTTP continuó funcionando.

La corrección posterior de diagnósticos también superó los cinco trabajos de CI, con 78 aserciones de flujos y seguridad en ambas matrices y almacenamientos, y Plugin Check sobre el ZIP extraído. El ZIP local, el artefacto de CI y el borrador privado de release coincidieron byte a byte.

La configuración administrativa se revisó siguiendo el proyecto LAFISE: el README y las guías operativas están en español y la instalación normal utiliza el panel WooCommerce, sin editar archivos. Las contraseñas se cifran en una opción separada sin autoload; formulario y API no las devuelven. La batería específica superó 23 aserciones locales de cifrado, conservación de campos vacíos, sustitución por moneda, permisos, nonce, API, migración, manipulación del texto cifrado, cambio de salts y eliminación explícita. Los 78 flujos de pagos continuaron pasando con el almacenamiento cifrado y banco simulado. El formulario real del administrador local confirmó el guardado con contraseñas vacías y su conservación, sin errores de consola. La captura de ajustes se renovó con datos ficticios.

Se compararon los candidatos públicos, todos los objetos locales de Git —incluidos los no referenciados— y el ZIP con las credenciales bancarias activas y anteriores conocidas y con los accesos privados de la demo. No se encontraron coincidencias. Esa comprobación se complementa con el escáner de patrones y la revisión manual; no garantiza reconocer secretos futuros desconocidos. Los valores y el registro detallado de comparación permanecen fuera del repositorio.

El navegador local completó un pedido invitado desde Checkout Blocks y un pedido registrado desde el checkout clásico, en ambos casos pasando por el desafío simulado hasta la confirmación. El clásico se verificó también en vista móvil de 375–390 px, sin desbordamiento horizontal. Las capturas y los pedidos contienen únicamente datos ficticios; no son pruebas bancarias reales.

La revisión detectó y corrigió pérdida de entidades HTML al anidar el contenido bancario en `srcdoc`; una regresión comprueba que se conserva byte a byte. El callback se registra desde el arranque, independiente de una sesión de checkout.

## Tienda demo y comprobaciones pendientes

La demo usa WordPress 7.1.4, WooCommerce 11.2.1, PHP 8.3.36 y HPOS. Se verificó el respaldo completo antes de instalar: 13.460 archivos y 53 tablas. La activación registra BAC y LAFISE conjuntamente. Para la sesión CRC se cambió temporalmente la moneda a CRC y se creó un producto virtual sin impuestos por CRC 100; al cerrar esa sesión se restauró USD, se dejó el producto en borrador y se desactivó BAC de producción. LAFISE permanece activo y la página de checkout compartida no se modificó.

Por decisión del mantenedor, staging queda pendiente. Se configura una prueba productiva privada, visible solo para administradores, con una política externa al plugin distribuido: máximo CRC 100 o USD 1 por pedido y reservas acumuladas de CRC 500 o USD 5. Las reservas no se liberan automáticamente tras rechazos; el límite es conservador.

El 3 de noviembre se comprobaron resultados reales Visa en ambas monedas: rechazos financieros ISO 61 y, tras reintentar con otra tarjeta autorizada, aprobaciones de USD 1 y CRC 100 con AuthenticationStatus `Y`, ISO `00`, identificador financiero registrado y pedido pagado. El mantenedor confirmó haber completado el desafío visible de USD y confirmó ambas aprobaciones también en el portal de PowerTranz/BAC. Esto acredita la concordancia de las aprobaciones, no una liquidación bancaria posterior. Se comprobó además la eliminación del HTML de redirección y del estado de autenticación tras la aprobación. Los registros de los rechazos no permiten determinar si hubo autenticación sin desafío o autenticación intentada; no se presentan como desafíos completados.

Se verificaron después aprobaciones Mastercard de USD 1 y CRC 100, ambas con AuthenticationStatus `Y`, ISO `00`, identificador financiero y pedido pagado. El mantenedor confirmó también las dos operaciones en el portal bancario. El piloto incluyó un retorno 3DS `N`, rechazado por la política del plugin antes de completar el pago. Los pedidos aprobados con la corrección ya no conservan mensajes de rechazos anteriores.

El piloto reveló que los metadatos conservaban el mensaje de un rechazo anterior cuando el mismo pedido se reintentaba y se aprobaba. El nuevo intento elimina únicamente sus diagnósticos anteriores, después de validar tarjeta y política del comercio; las notas históricas se conservan. Una regresión reproduce el rechazo ISO 61, un intento nuevo, la invalidación del callback anterior y la aprobación posterior.

El mantenedor reportó posteriormente un desafío visible en una tarjeta Visa antes rechazada, sin completar esa operación. Se registra como observación, no como aprobación ni como prueba de que la limpieza de diagnósticos cambiara la decisión del emisor: la solicitud SPI, las preferencias 3DS y su presentación no cambiaron en esa corrección. Al cerrar las pruebas no se encontraron pedidos BAC con intento activo o resultado incierto; se restauró USD, se dejó el producto Mastercard en borrador y se desactivó BAC. LAFISE permanece activo.

La primera prueba de la demo se detuvo antes de enviar una solicitud bancaria: la política privada estaba registrada con un argumento y exigía dos. Se reprodujo y corrigió fuera del paquete público. La política corregida superó 24 comprobaciones locales de permisos, importes, presupuesto, reintentos y pedidos sin guardar; el preflight del servidor confirmó el registro con dos argumentos y cero HTTP bancarios. La regresión pública comprueba también la entrega del pedido al filtro; la batería actual contiene 78 aserciones de flujos y seguridad, incluidos los reintentos posteriores a ISO 61.

La matriz productiva comprobada incluye Visa y Mastercard, cada una con aprobación en USD y CRC; se observaron también rechazos financieros y de autenticación. Staging sigue pendiente por decisión del mantenedor y el envío al directorio está aplazado expresamente. La concordancia de aprobaciones en el portal no acredita liquidación posterior ni compatibilidad con otras marcas. La release estable permanece pendiente de cerrar los requisitos de validación. Credenciales, respaldo y detalles operativos permanecen fuera de la distribución pública.

No se presentan simulaciones como cobros reales ni una prueba en una moneda como cobertura de otra.
