Instalé PHP 8.3 + ext-soap, corrí el WSDL de tu V2 y el nuSOAP de tu V1, y comparé salidas reales. Resultados: 

## **Tu V2, tal como está en el zip, YA emite la respuesta correcta.** 

<ns1:iniciarParqueoResponse><iniciarParqueoReturn xsi:type="ns1:codigoRespuestaComplex"> <codigoRespuesta xsi:type="xsd:int">6</codigoRespuesta> </iniciarParqueoReturn></ns1:iniciarParqueoResponse> 

Probé con request con prefijo `tns:` , con namespace por defecto y sin namespace: los tres dan lo mismo. O sea, **el código fuente no es la causa** . Lo que ves en producción ( `<return xsi:type="SOAP-ENC:Struct">` ) es la firma exacta de `new SoapServer(null, ...)` , y eso solo puede venir de un WSDL que no se está usando. 

# **Causa raíz: nombre de archivo fijo + cache de ext-soap** 

## php 

$wsdlFile = sys_get_temp_dir() . '/setex_' . md5($_SERVER['SERVER_NAME']) . '.wsdl'; if (!file_exists($wsdlFile) || filemtime($wsdlFile) < time() - 3600) { ... } $server = new SoapServer($wsdlFile, ['cache_wsdl' => WSDL_CACHE_MEMORY, ...]); 

Lo demostré: sobrescribí el archivo con un WSDL distinto y PHP siguió sirviendo el viejo. El SDL se cachea por **ruta** , y esa ruta es constante. Con `soap.wsdl_cache_ttl=86400` en disco ( `/tmp/wsdl-*` ) más `WSDL_CACHE_MEMORY` en workers php-fpm de vida larga, una versión anterior de ese archivo queda pegada indefinidamente. `filemtime` no invalida nada. 

Segundo problema, este sí un bug tuyo introducido a mano: 

php 

$soapOutput = preg_replace('/<getVersionReturn[^>]*>/', '<return xsi:type="SOAPENC:Struct">', $soapOutput); 

Corrí el nuSOAP de V1 y **V1 nunca emitió eso** para `getVersion` . Emitía 

`<getVersionReturn xsi:type=":codigoRespuestaStringComplex">` . Ese parche está rompiendo `getVersion` para acercarse a algo que no existía. 

# **Pasos** 

**1.** Copiá `setex.wsdl` a `<proyecto>/schema/setex.wsdl` y reemplazá `src/setexwsdl.php` por el archivo adjunto. El WSDL sale del heredoc y pasa a ser un archivo versionable en git. 

**2.** El nuevo código nombra el temporal por **hash del contenido** ( `setex_<sha1>.wsdl` ) y usa `WSDL_CACHE_NONE` . Si cambiás el contrato, cambia la ruta y el cache no puede servir 

nada rancio. La escritura es `write + rename` atómico, así que dos requests concurrentes no leen un WSDL truncado. 

## **3.** Limpiá el estado envenenado antes de probar: 

## bash 

rm -f /tmp/setex_*.wsdl /tmp/wsdl-* systemctl reload php8.3-fpm # obligatorio: WSDL_CACHE_MEMORY vive en el worker 

## **4.** Verificá contra el servidor real: 

## bash 

curl -s -X POST http://TU_HOST/serviceSetex/src/setex-wsdl.php \ -H 'Content-Type: text/xml; charset=utf-8' \ -H 'SOAPAction: urn:setexwsdl#iniciarParqueo' \ --data-binary @request.xml | xmllint --format - 

Tiene que salir `<iniciarParqueoReturn xsi:type="tns:codigoRespuestaComplex">` . Si sale `<return ...SOAP-ENC:Struct>` , el `/tmp` no se limpió o php-fpm no recargó. 

**5.** `.env` : agregá `SETEX_LEGACY_WIRE=true` . 

# **Sobre paridad exacta con V1** 

El nuSOAP de V1 emitía esto (capturado, no deducido): 

<?xml version="1.0" encoding="ISO-8859-1"?> ...xmlns:tns="urn:setexwsdl"... <ns1:iniciarParqueoResponse xmlns:ns1="urn:setexwsdl"> <iniciarParqueoReturn xsi:type="tns:codigoRespuestaComplex"> 

Dos diferencias que ext-soap no puede evitar y que el nuevo archivo corrige en postproceso ( `setexLegacyWire` ): prefijo `tns:` en vez de `ns1:` en el `xsi:type` , y declaración `ISO-8859-1` . Con eso la salida queda equivalente elemento por elemento. Lo único que no replico: dónde se declara `xmlns:ns1` (envelope vs. elemento) y el orden de atributos — irrelevante para cualquier parser XML, y replicarlo exigiría armar el envelope a mano. 

**Punto donde no te doy paridad a propósito:** `getVersion` ahora sale como `xsi:type="tns:codigoRespuestaStringComplex"` . V1 emitía 

`xsi:type=":codigoRespuestaStringComplex"` — QName inválido, producto de que en V1 registraste el tipo sin prefijo `tns:` . Si algún monitor hace string-match contra ese valor roto se te va a caer; verificalo antes de subir. Replicar XML malformado a propósito es deuda que no vale la pena arrastrar. 

