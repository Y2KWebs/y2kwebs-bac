# Revisión de WordPress.org

## Publicación 1.0.2 y traducciones

La versión aprobada 1.0.1 se publicó el 16 de noviembre de 2026 en GitHub y WordPress.org (SVN r3805265), con cabecera e iconos Y2KWebs. Sus etiquetas y paquetes se conservan.

El candidato 1.0.2 usa cadenas fuente en inglés e incluye `languages/y2kwebs-bac-es_ES.po` y `.mo` como respaldo para todos los locales españoles. Los paquetes oficiales regionales tienen prioridad por cadena. Los ajustes siguen el idioma del administrador; el checkout y sus peticiones AJAX siguen el idioma de la tienda. Los títulos y descripciones guardados por el comercio se conservan sin traducirlos ni sobrescribirlos. Las guías de usuario permanecen en español.

Las traducciones del directorio y los paquetes oficiales se gestionan en Translate WordPress y requieren revisión de la comunidad. Su importación no implica aprobación ni disponibilidad de paquetes oficiales. La validación bancaria en staging continúa pendiente; estas comprobaciones utilizan exclusivamente banco simulado.

Para preparar el candidato: `python scripts/build_translations.py --check`, `python scripts/check_public.py`, pruebas y `python scripts/prepare_wordpress_org.py`. El material SVN se genera en `dist/wordpress-org-1.0.2`; los PNG del directorio se publican en `/assets`, fuera del ZIP instalable.


Estado a 16 de noviembre de 2026: plugin aprobado por WordPress.org, con el SVN asignado `https://plugins.svn.wordpress.org/y2kwebs-bac/`. Se prepara la publicación inicial 1.0.1 con el código aprobado; el repositorio SVN estaba vacío al comenzar esta etapa. La validación bancaria en staging continúa pendiente.

Identidad: **Y2KWebs Gateway for BAC**, slug asignado `y2kwebs-bac`, contributor `y2kwebs`, licencia GPL-2.0-or-later. El repositorio es https://github.com/Y2KWebs/y2kwebs-bac.

## Registro del envío original 1.0.0

Se envió la versión `1.0.0`, SHA-256 `54f6e14a0b786b6ab9a8ea8531712f515c6a663cccdc4a120dba7e24a2cb2542`. El escaneo automático mostró **Pass**, con avisos de dominio de texto por el slug generado inicialmente; después se confirmó el slug `y2kwebs-bac`, coincidente con el paquete. Esto no sustituye la revisión manual. La página indicó el envío de un correo de confirmación y que la revisión se comunicará a `info@y2kwebs.com`.

La documentación del repositorio se actualizó después del envío. El ZIP enviado y su material SVN se conservan como registro de ese candidato; no se ha creado otra release ni publicado en SVN.

## Corrección 1.0.1

El correo del 6 de noviembre de 2026 puso la solicitud en espera de correcciones e identificó dos scripts propios impresos directamente en la pasarela. El candidato 1.0.1 los convierte en recursos externos cargados mediante WordPress, conserva el flujo SPI y refuerza la validación del mensaje entre iframe y ventana principal. La aprobación continúa pendiente.

El material de 1.0.1 se prepara en `dist/wordpress-org-1.0.1`, con `trunk`, `tags/1.0.1` y capturas. El ZIP original 1.0.0 se conserva. Tras las comprobaciones del ZIP extraído se actualizó esta misma solicitud, sin crear otra solicitud ni una release de GitHub. La página confirmó el archivo `y2kwebs-bac-1.0.1.zip`, versión 1.0.1 y fecha 11 de noviembre de 2026. SHA-256 enviado: `1a0047ee86589a4a980f74a60151b0853cf28ce6d0353372db2401dd5f17dda7`. El mantenedor responderá al mismo hilo del correo de revisión con el borrador entregado; ese correo no se ha enviado. La documentación del repositorio se actualiza después del envío y no debe confundirse con el contenido inmutable de ese ZIP.

## Idiomas de esta primera publicación

La interfaz, los mensajes de pago y la documentación de uso permanecen en español. Se conservan las llamadas de internacionalización existentes, sin convertir las cadenas fuente al inglés, añadir catálogos ni tramitar permisos PTE en esta etapa.

El `readme.txt` permanece en inglés: desde julio de 2025 el equipo de plugins lo [requiere para la revisión y la ficha base](https://make.wordpress.org/plugins/2025/07/28/requiring-the-readme-to-be-written-in-english/). Esa ficha explica expresamente que la interfaz está en español. El `README.md` de GitHub y las guías continúan en español.

El español de la interfaz no acredita una traducción oficial en Translate WordPress. El directorio puede mostrar un aviso de traducción pendiente. Una ficha oficial localizada y una interfaz inglesa se abordarán por separado si el mantenedor las solicita.

## Paquete y solicitud

1. Ejecutar pruebas y revisión de contenido público; registrar versiones y resultados en [validación](validation.md).
2. Ejecutar `python scripts/prepare_wordpress_org.py`. Produce ZIP instalable, SHA-256 y material local SVN en `dist/wordpress-org/svn` con `trunk`, `tags/1.0.0` y capturas en `assets`. El manifiesto registra hashes; esta carpeta todavía no es un checkout SVN. Ejecutar Plugin Check sobre el ZIP extraído, sin excluir categorías.
3. Revisar los puntos pendientes de [validación](validation.md). Staging continúa pendiente; no presentarlo como verificado. El candidato mantiene el código de pagos de la release pública, con documentación revisada para el directorio. Su hash puede diferir del ZIP de GitHub; conservar la release existente y registrar la comparación antes del envío.
4. Solicitud presentada con la cuenta propietaria `y2kwebs` en [Add your Plugin](https://wordpress.org/plugins/developers/add/), con el archivo `y2kwebs-bac-1.0.0.zip`. Conservar ese archivo: regenerar el paquete con documentación posterior cambiaría su hash y no actualizaría automáticamente el envío.
5. El formulario asignó inicialmente `y2kwebs-gateway-for-bac`. Se solicitó el cambio y la página confirmó `y2kwebs-bac` como slug actual. Conservar el expediente y responder a las observaciones en esa solicitud; no duplicar el envío.
6. Tras la aprobación, utilizar el repositorio SVN exacto comunicado por WordPress.org. Copiar el contenido revisado a trunk, el tag de versión y las capturas a assets. Revisar `svn diff` y el manifiesto antes de publicar.

Las dos capturas preparadas son opcionales para la revisión inicial y están incluidas en el material SVN. Cabecera e icono personalizados se pueden añadir después; no son un requisito para presentar el ZIP. No incorporar logos de terceros sin autorización.

## Nota para el equipo de revisión

Texto preparado para el campo de información adicional, si está disponible, o para responder al revisor:

> Independent, free and open-source WooCommerce gateway for BAC Costa Rica merchant accounts using PowerTranz/FAC SPI 3DS. Requested slug: y2kwebs-bac. The gateway interface and user documentation are currently in Spanish; readme.txt is in English for directory review and explicitly describes this language scope. The readme discloses the required external service, data transmitted, provider terms/privacy links and direct card-data processing through the merchant server. Live Visa and Mastercard approvals were verified in CRC and USD; automated tests use a simulated bank, and provider staging validation remains pending. This is not an official BAC, PowerTranz or WooCommerce plugin. No Y2KWebs account, subscription or paid support is required.

La cabecera no incluye un Update URI privado para que las futuras actualizaciones correspondan al directorio. No hay actualizador alternativo propio. Los mínimos y los campos Tested deben reflejar comprobaciones reales.

La ficha divulga servicio externo, datos transmitidos, política de privacidad, términos públicos y límites. El contrato del comercio prevalece sobre términos generales del sitio del procesador. Consulte las [directrices oficiales](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) y revise licencias de código e imágenes antes del envío.

El historial privado de comercios, credenciales, documentos bancarios, registros y resultados detallados permanece fuera de GitHub y del paquete.
