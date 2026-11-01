# Validación de 1.0.0

Estado: candidato 1.0.0 instalado en la demo para una prueba bancaria manual; sin release pública ni solicitud a WordPress.org. Registro actualizado el 1 de noviembre de 2026.

El flujo bancario parte de una implementación mantenida con experiencia operativa previa. Ese antecedente no acredita pruebas de todas las combinaciones del paquete público.

## Pruebas locales

| Matriz | Versiones | Resultado |
|---|---|---|
| Mínima | WordPress 6.9.4, WooCommerce 9.0.0, PHP 7.4.33 | Smoke, 70 aserciones de flujos/seguridad y callback HTTP superados, con HPOS desactivado y activado |
| Actual | WordPress 7.1, WooCommerce 11.2.1, PHP 8.2.35 | Smoke, flujos/seguridad y callback HTTP superados con ambos almacenamientos; la última ejecución con HPOS incluye 70 aserciones |
| ZIP extraído | WordPress 7.1, WooCommerce 11.2.1, PHP 8.2.35 | 11 aserciones de paquete superadas; HTTP externo bloqueado |
| Empaquetado | Python 3.11 | 4 pruebas: lista explícita, reproducibilidad, archivos/versiones y coincidencia SVN/ZIP |
| Plugin Check | 2.1.0, todas las categorías, ZIP extraído | Sin errores ni avisos reportados |

Los flujos automatizados cubren selección CRC/USD, decimales, Y/A/U/N/R, correlación manipulada, tokens alterados, concurrencia, duplicados, caducidad, rechazo financiero y resultados inciertos. El transporte bancario se simula. Las capturas públicas contienen exclusivamente datos ficticios.

La CI del commit inicial superó sus cinco trabajos: sintaxis con PHP 7.4/8.2/8.4, las matrices mínima y moderna (esta última con WordPress 7.1.4 y WooCommerce 11.2.1), ambos almacenamientos, callback HTTP, paquete extraído y Plugin Check. El empaquetado fija además los metadatos de plataforma del ZIP para generar el mismo archivo en Windows y Linux. Se comprobó igualdad byte a byte con el artefacto descargado de esa ejecución. La comprobación local de convivencia registró BAC, LAFISE y la pasarela de referencia con identificadores independientes; el callback HTTP continuó funcionando.

El navegador local completó un pedido invitado desde Checkout Blocks y un pedido registrado desde el checkout clásico, en ambos casos pasando por el desafío simulado hasta la confirmación. El clásico se verificó también en vista móvil de 375–390 px, sin desbordamiento horizontal. Las capturas y los pedidos contienen únicamente datos ficticios; no son pruebas bancarias reales.

La revisión detectó y corrigió pérdida de entidades HTML al anidar el contenido bancario en `srcdoc`; una regresión comprueba que se conserva byte a byte. El callback se registra desde el arranque, independiente de una sesión de checkout.

## Tienda demo y comprobaciones pendientes

La demo usa WordPress 7.1.4, WooCommerce 11.2.1, PHP 8.3.36 y HPOS. Se verificó el respaldo completo antes de instalar: 13.460 archivos y 53 tablas. La activación registra BAC y LAFISE conjuntamente. La instalación no modifica moneda USD ni página de checkout compartida.

Por decisión del mantenedor, staging queda pendiente. Se configura una prueba productiva privada, visible solo para administradores, con una política externa al plugin distribuido: máximo CRC 100 o USD 1 por pedido y reservas acumuladas de CRC 500 o USD 5. Las reservas no se liberan automáticamente tras rechazos; el límite es conservador. Todavía no se han ejecutado cobros productivos con el candidato.

La primera prueba de la demo se detuvo antes de enviar una solicitud bancaria: la política privada estaba registrada con un argumento y exigía dos. Se reprodujo y corrigió fuera del paquete público. La política corregida superó 24 comprobaciones locales de permisos, importes, presupuesto, reintentos y pedidos sin guardar; el preflight del servidor confirmó el registro con dos argumentos y cero HTTP bancarios. La regresión pública comprueba también la entrega del pedido al filtro; la batería actual contiene 71 aserciones de flujos y seguridad.

Queda pendiente cerrar el piloto bancario: aprobación/rechazo reales, desafío del emisor, conciliación y cobertura de las monedas/marcas habilitadas. No publicar una release estable ni enviar al directorio hasta resolver y documentar estos puntos. Credenciales, respaldo y detalles operativos permanecen fuera de la distribución pública.

No se presentan simulaciones como cobros reales ni una prueba en una moneda como cobertura de otra.
