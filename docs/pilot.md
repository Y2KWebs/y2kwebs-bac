# Operación y pruebas bancarias

Mantenga staging y visibilidad para administradores durante la preparación. Use catálogo ficticio y credenciales guardadas fuera de Git. Las pruebas automatizadas modifican datos: ejecútelas únicamente en el entorno local descartable.

En la tienda demo, respalde archivos y base de datos antes de instalar. Registre moneda, páginas de checkout, visibilidad y ajustes compartidos; restaure cualquier cambio al terminar. La pasarela puede convivir con otros métodos gracias a sus identificadores propios.

Pruebe aprobaciones, rechazos y desafío 3DS en cada moneda/marca habilitada por el banco. Registre pedido de prueba, fecha, moneda, importe, resultado y códigos saneados. No publique referencias de comercios, datos de compradores, PAN, CVV, tokens o respuestas bancarias originales.

Un resultado financiero incierto queda en espera y bloquea nuevos envíos. Revise el portal bancario y la referencia de transacción. Si hubo cobro, registre la confirmación manual en WooCommerce; si necesita devolverlo, use el banco y registre una devolución manual. Si el banco confirma que no hubo cobro, cancele el pedido incierto y utilice un nuevo pedido; no borre metadatos para reabrir el intento. No empareje operaciones únicamente por importe y fecha.

Las pruebas reales requieren tarjeta autorizada, límites acordados, cuenta productiva y registro privado de los importes acumulados. No se ejecutan desde CI. Al terminar, deje la demo en staging o desactive BAC si no están disponibles credenciales de pruebas.
