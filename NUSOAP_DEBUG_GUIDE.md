# 🔧 Solución de Problemas nuSOAP - SETEX

## Problemas Identificados y Corregidos

### 1. **Error de Namespace en getVersion**
**Problema**: El método `getVersion` usaba un namespace incorrecto (`'xsd:setexwsdl'` en lugar de `'urn:setexwsdl'`)
**✅ Corregido**: Actualizado para usar el namespace correcto con parámetros completos

### 2. **Configuración Mejorada de nuSOAP**
**Problema**: Configuración básica no optimizada para PHP 8
**✅ Corregido**: Agregadas las siguientes configuraciones:
```php
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$server->charSet = 'UTF-8';
$server->wsdl->schemaTargetNamespace = 'urn:setexwsdl';
$server->serialize_return = true;
```

### 3. **Debug Mejorado**
**✅ Agregado**: Sistema de logging extendido que captura:
- XML crudo recibido
- Headers HTTP
- Variables POST/GET
- Parámetros parseados por nuSOAP

## Herramientas de Diagnóstico

### 🔍 Archivo de Diagnóstico del Servidor
**Archivo**: `src/test-nusoap-debug.php`
**Uso**: 
1. Visita: `http://tu-dominio/serviceSetex/src/test-nusoap-debug.php`
2. Para ver WSDL: `http://tu-dominio/serviceSetex/src/test-nusoap-debug.php?wsdl`

### 🧪 Cliente de Prueba
**Archivo**: `src/test-client-debug.php`
**Uso**: 
1. Ejecuta: `http://tu-dominio/serviceSetex/src/test-client-debug.php`
2. Revisa los resultados y logs generados

### 📋 Archivos de Log Generados
Todos en la carpeta `logs/`:
- `raw_xml_debug_YYYY-MM-DD.txt` - XML crudo recibido
- `headers_debug_YYYY-MM-DD.txt` - Headers HTTP
- `variables_debug_YYYY-MM-DD.txt` - Variables POST/GET
- `iniciarParqueo_debug_YYYY-MM-DD.txt` - Debug de función iniciarParqueo
- `getVersion_debug_YYYY-MM-DD.txt` - Debug de función getVersion

## Pasos para Diagnosticar

### Paso 1: Verificación Básica
```bash
# Acceder al diagnóstico
curl "http://tu-dominio/serviceSetex/src/test-nusoap-debug.php"
```

### Paso 2: Probar WSDL
```bash
# Verificar WSDL
curl "http://tu-dominio/serviceSetex/src/test-nusoap-debug.php?wsdl"
```

### Paso 3: Ejecutar Cliente de Prueba
```bash
# Ejecutar todas las pruebas
curl "http://tu-dominio/serviceSetex/src/test-client-debug.php"
```

### Paso 4: Revisar Logs
Después de ejecutar las pruebas, revisa los archivos de log para identificar exactamente dónde está el problema:

1. **Si el XML no llega**: Problema de red/configuración
2. **Si el XML llega pero parámetros vacíos**: Problema de parsing nuSOAP
3. **Si los parámetros llegan**: El servicio funciona correctamente

## Problemas Comunes y Soluciones

### ❌ Parámetros Vacíos en la Función
**Síntoma**: Los logs muestran parámetros vacíos o null
**Causa**: nuSOAP no está parseando correctamente el XML
**Solución**: 
1. Verificar que el Content-Type sea `text/xml`
2. Verificar la codificación UTF-8
3. Revisar que el XML cumpla con el XSD

### ❌ Error de Namespace
**Síntoma**: Errores de SOAP Fault relacionados con namespaces  
**Causa**: Cliente usando namespace incorrecto
**Solución**: Usar `urn:setexwsdl` en todas las llamadas

### ❌ Encoding de Caracteres
**Síntoma**: Caracteres especiales mal codificados
**Causa**: Configuración de encoding inconsistente
**Solución**: Ya corregido en la configuración del servidor

## XML de Ejemplo Válido

Para `iniciarParqueo`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:iniciarParqueo>
         <token>dc2fec0f5f08fca379553cc7af20d556</token>
         <plazaId>2</plazaId>
         <zonaId>999</zonaId>
         <identificador>1234567890123</identificador>
         <tiempoParqueo>30</tiempoParqueo>
         <importeParqueo>50</importeParqueo>
         <passwordCps>test123</passwordCps>
         <fechaInicioParqueo>2026-02-27 10:30:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 11:00:00</fechaFinParqueo>
         <nroTransaccion>TEST_20260227103000</nroTransaccion>
         <fechaTransaccion>2026-02-27 10:30:00</fechaTransaccion>
      </urn:iniciarParqueo>
   </soap:Body>
</soap:Envelope>
```

## Compatibilidad Mantenida

✅ **Todas las correcciones mantienen compatibilidad** con el XML de entrada existente
✅ **No se requieren cambios** en los clientes existentes
✅ **Mejoras de logging** no afectan el funcionamiento normal
✅ **Configuración mejorada** es retrocompatible

## Próximos Pasos

1. **Ejecutar diagnóstico**: Usa las herramientas proporcionadas
2. **Revisar logs**: Identifica exactamente dónde está el problema  
3. **Probar con cliente real**: Verifica que la solución funcione
4. **Monitorear**: Usa los logs para verificar que todo funcione correctamente

¿Necesitas ayuda con algún paso específico del diagnóstico?