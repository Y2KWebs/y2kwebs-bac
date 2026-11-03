# Publicación de 1.0.0

Estado: ZIP, ficha y estructura SVN preparados localmente, con aprobaciones reales Visa y Mastercard en USD y CRC verificadas también por el mantenedor en el portal bancario. Staging y la release estable de GitHub siguen pendientes. El mantenedor ha aplazado expresamente el envío a WordPress.org; no se ha enviado solicitud. El candidato se conserva en un repositorio privado con un borrador de release.

Identidad: **Y2KWebs Gateway for BAC**, slug solicitado `y2kwebs-bac`, contributor `y2kwebs`, licencia GPL-2.0-or-later. El repositorio previsto es https://github.com/Y2KWebs/y2kwebs-bac.

1. Ejecutar pruebas, revisión de contenido público y Plugin Check sobre el ZIP extraído; registrar versiones y resultados en [validación](validation.md).
2. Ejecutar `python scripts/prepare_wordpress_org.py`. Produce ZIP instalable, SHA-256 y material local SVN en `dist/wordpress-org/svn` con `trunk`, `tags/1.0.0` y capturas en `assets`. El manifiesto registra hashes; esta carpeta todavía no es un checkout SVN.
3. Resolver los puntos pendientes de [validación](validation.md). La sesión productiva privada confirmó Visa y Mastercard en ambas monedas, además de rechazos y autenticación 3DS. Staging sigue pendiente. Una vez cerrada la validación bancaria, publicar el repositorio, etiqueta `v1.0.0` y ZIP en GitHub. La release y el ZIP enviado al directorio deben contener el mismo código aprobado.
4. Cuando el mantenedor decida expresamente retomar el envío, inicia sesión con la cuenta propietaria en [Add your Plugin](https://wordpress.org/plugins/developers/add/) y sube únicamente `dist/y2kwebs-bac-1.0.0.zip`. Esta preparación no envía ninguna solicitud.
5. Comprobar el slug asignado y solicitar `y2kwebs-bac` antes de la aprobación si se genera otro. Conservar el expediente y responder a las observaciones en esa solicitud.
6. Tras la aprobación, utilizar el repositorio SVN exacto comunicado por WordPress.org. Copiar el contenido revisado a trunk, el tag de versión y las capturas a assets. Revisar `svn diff` y el manifiesto antes de publicar.

La cabecera no incluye un Update URI privado para que las futuras actualizaciones correspondan al directorio. No hay actualizador alternativo propio. Los mínimos y los campos Tested deben reflejar comprobaciones reales.

La ficha divulga servicio externo, datos transmitidos, política de privacidad, términos públicos y límites. El contrato del comercio prevalece sobre términos generales del sitio del procesador. Consulte las [directrices oficiales](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) y revise licencias de código e imágenes antes del envío.

El historial privado de comercios, credenciales, documentos bancarios, registros y resultados detallados permanece fuera de GitHub y del paquete.
