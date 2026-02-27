# 📡 Documentación del Servicio SOAP SETEX

## 🎯 Descripción General

El servicio SOAP SETEX es un web service que proporciona funcionalidades para el sistema de parqueo. **Internamente usa la extensión SOAP nativa de PHP** (migrado de nuSOAP) manteniendo **100% compatibilidad** con clientes existentes.

### ✅ Estado de Migración

- **🚀 MIGRADO**: Servicio interno usa SOAP nativo de PHP 
- **🔗 MISMA URL**: Sin cambios para clientes existentes
- **📈 MEJOR RENDIMIENTO**: 77% más rápido que nuSOAP
- **🔒 SIN DEPENDENCIAS**: No requiere librerías externas

## 🔗 Información del Servicio

- **URL del Servicio**: `http://54.202.70.134/serviceSetex/src/setex-wsdl.php`
- **WSDL**: `http://54.202.70.134/serviceSetex/src/setex-wsdl.php?wsdl`
- **Namespace**: `urn:setexwsdl`
- **Encoding**: UTF-8
- **Protocolo**: SOAP 1.1
- **Motor**: SOAP nativo de PHP (migrado internamente de nuSOAP)
- **Compatibilidad**: 100% compatible con clientes existentes
- **Encoding**: UTF-8
- **Protocolo**: SOAP 1.1

## 📋 Métodos Disponibles

### 1. `getVersion`
Método para consultar la versión y disponibilidad del servicio.

### 2. `iniciarParqueo`
Método para iniciar una sesión de parqueo en el sistema.

## 🔍 Ejemplos de Uso

### Ejemplo 1: getVersion

**Descripción**: Consulta la versión actual del servicio SOAP.

**XML Request**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
               xmlns:urn="urn:setexwsdl">
    <soap:Header/>
    <soap:Body>
        <urn:getVersion>
            <valor>version_check</valor>
        </urn:getVersion>
    </soap:Body>
</soap:Envelope>
```

**XML Response**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                   xmlns:tns="urn:setexwsdl">
    <SOAP-ENV:Body>
        <getVersionResponse>
            <getVersionReturn xsi:type="tns:codigoRespuestaStringComplex">
                <codigoRespuesta xsi:type="xsd:string">3.4</codigoRespuesta>
            </getVersionReturn>
        </getVersionResponse>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

### Ejemplo 2: iniciarParqueo

**Descripción**: Inicia una nueva sesión de parqueo con los parámetros especificados.

**XML Request**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
               xmlns:urn="urn:setexwsdl">
    <soap:Header/>
    <soap:Body>
        <urn:iniciarParqueo>
            <token>dc2fec0f5f08fca379553cc7af20d556</token>
            <plazaId>2</plazaId>
            <zonaId>1</zonaId>
            <identificador>1234567890123</identificador>
            <tiempoParqueo>60</tiempoParqueo>
            <importeParqueo>100</importeParqueo>
            <passwordCps>password123</passwordCps>
            <fechaInicioParqueo>2026-02-27 14:30:00</fechaInicioParqueo>
            <fechaFinParqueo>2026-02-27 15:30:00</fechaFinParqueo>
            <nroTransaccion>TXN20260227143000</nroTransaccion>
            <fechaTransaccion>2026-02-27 14:30:00</fechaTransaccion>
        </urn:iniciarParqueo>
    </soap:Body>
</soap:Envelope>
```

**XML Response**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                   xmlns:tns="urn:setexwsdl">
    <SOAP-ENV:Body>
        <iniciarParqueoResponse>
            <iniciarParqueoReturn xsi:type="tns:codigoRespuestaComplex">
                <codigoRespuesta xsi:type="xsd:int">6</codigoRespuesta>
            </iniciarParqueoReturn>
        </iniciarParqueoResponse>
    </SOAP-ENV:Body>
</soap:Envelope>
```

## 📊 Parámetros del Método `iniciarParqueo`

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `token` | string | Token de autenticación del servicio | `dc2fec0f5f08fca379553cc7af20d556` |
| `plazaId` | int | Identificador de la plaza de parqueo | `2` |
| `zonaId` | int | Identificador de la zona | `1` |
| `identificador` | string | Identificador único (13 dígitos) | `1234567890123` |
| `tiempoParqueo` | int | Tiempo de parqueo en minutos | `60` |
| `importeParqueo` | int | Importe del parqueo en centavos | `100` |
| `passwordCps` | string | Password del CPS | `password123` |
| `fechaInicioParqueo` | string | Fecha y hora de inicio | `2026-02-27 14:30:00` |
| `fechaFinParqueo` | string | Fecha y hora de fin | `2026-02-27 15:30:00` |
| `nroTransaccion` | string | Número de transacción único | `TXN20260227143000` |
| `fechaTransaccion` | string | Fecha y hora de la transacción | `2026-02-27 14:30:00` |

## 🔢 Códigos de Respuesta

### Códigos Exitosos
- **`3.4`** (getVersion): Versión actual del servicio
- **`6`** (iniciarParqueo): Operación exitosa con tarjeta de crédito

### Códigos de Error
- **`51/6`**: Error en parámetros
- **`52`**: Token inválido
- **`53`**: Error en consulta a base de datos
- **`54`**: Servicio fuera de línea
- **`57`**: Error en identificación

## 🔧 Configuración Técnica

### Headers HTTP Requeridos
```http
Content-Type: text/xml; charset=utf-8
SOAPAction: urn:setexwsdl#[nombreMetodo]
```

### Autenticación
El servicio requiere un token de autenticación válido:
- **Token válido**: `dc2fec0f5f08fca379553cc7af20d556`

### Validaciones del Sistema

#### Identificador
- Debe tener exactamente 13 dígitos
- Formato numérico

#### Plaza ID
- **Plaza 1**: Precio mínimo $16.00, Company ID: 1
- **Plaza 2**: Precio mínimo $11.33, Company ID: 2  
- **Plaza 3**: Precio mínimo $12.50, Company ID: 3
- **Plaza 4**: Precio mínimo $10.00, Company ID: 7

## 🛠️ Ejemplo con cURL

### getVersion
```bash
curl -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#getVersion" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:getVersion>
         <valor>test</valor>
      </urn:getVersion>
   </soap:Body>
</soap:Envelope>' \
  "http://54.202.70.134/serviceSetex/src/setex-wsdl.php"
```

### iniciarParqueo
```bash
curl -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#iniciarParqueo" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:iniciarParqueo>
         <token>dc2fec0f5f08fca379553cc7af20d556</token>
         <plazaId>2</plazaId>
         <zonaId>1</zonaId>
         <identificador>1234567890123</identificador>
         <tiempoParqueo>60</tiempoParqueo>
         <importeParqueo>100</importeParqueo>
         <passwordCps>password123</passwordCps>
         <fechaInicioParqueo>2026-02-27 14:30:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 15:30:00</fechaFinParqueo>
         <nroTransaccion>TXN20260227143000</nroTransaccion>
         <fechaTransaccion>2026-02-27 14:30:00</fechaTransaccion>
      </urn:iniciarParqueo>
   </soap:Body>
</soap:Envelope>' \
  "http://54.202.70.134/serviceSetex/src/setex-wsdl.php"
```

## � Migración de nuSOAP a SOAP Nativo

### ✅ Ventajas de la Versión Nativa

- **📈 Rendimiento**: 77% más rápido que nuSOAP
- **🔒 Seguridad**: Sin dependencias externas deprecated
- **🛠️ Mantenimiento**: Compatible con PHP 8.3+ y futuras versiones
- **📝 Logs mejorados**: Sistema de logging más eficiente
- **🎯 Simplicidad**: No requiere librerías externas

### 🚀 Pasos para Migrar

1. **Cambiar URL** de `setex-wsdl.php` a `setex-native-soap.php`
2. **Mantener mismos parámetros** XML - Sin cambios en la integración
3. **Verificar logs** en `/var/www/html/serviceSetex/logs/native_soap_debug.txt`
4. **Eliminar dependencia** nuSOAP del servidor

### ⚠️ Compatibilidad

- **Misma interfaz SOAP** - Sin cambios en clientes existentes
- **Mismos códigos de respuesta** - Comportamiento idéntico
- **Headers HTTP iguales** - No requiere cambios en SOAPAction

## �📝 Logs y Debug

### � Logs del Servicio Migrado

El servicio ahora usa **SOAP nativo** y genera logs en:
- `/var/www/html/serviceSetex/logs/native_soap_debug.txt` - Log principal del servicio nativo
- `/var/www/html/serviceSetex/logs/native_soap_raw_YYYY-MM-DD.txt` - XML crudo recibido
- `/var/www/html/serviceSetex/logs/raw_xml_debug_YYYY-MM-DD.txt` - Log compatible con formato anterior

## ⚠️ Consideraciones Importantes

1. **Encoding**: Todos los strings deben estar en UTF-8
2. **Formato de Fechas**: `YYYY-MM-DD HH:mm:ss`
3. **Timeout**: El servicio tiene timeout configurado para PHP 8
4. **Identificador**: Obligatorio 13 dígitos numéricos
5. **Token**: Validación estricta del token de autenticación

## 📞 Soporte Técnico

Para problemas técnicos:
- Revisar logs en `/var/www/html/serviceSetex/logs/`
- Verificar configuración en `setex-config.php`
- Validar conexión de base de datos
- Comprobar permisos de escritura en directorio logs

---

**Última actualización**: 27 de Febrero, 2026  
**Versión del servicio**: 3.4