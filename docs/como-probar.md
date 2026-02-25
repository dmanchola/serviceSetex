# Guía para Probar los Servicios Web SETEX

Esta guía te ayudará a probar los servicios web expuestos por el proyecto SETEX.

## Requisitos Previos

- **PHP 7.0+** con extensión SOAP habilitada
- **Servidor Web** (Apache, Nginx o servidor de desarrollo PHP)
- **Base de datos MySQL** correctamente configurada
- **Biblioteca nusoap** (ya incluida en `libs/nusoap/`)

## 1. Configuración del Entorno

### Verificar la conexión a la base de datos
```bash
# Ejecutar el archivo de conexión para verificar
php src/connect.php
```
Deberías ver: "1Connected."

### Levantar el servidor web
```bash
# Usando el servidor de desarrollo de PHP
php -S localhost:8080 -t /ruta/del/proyecto

# O configurar un servidor Apache/Nginx apuntando al directorio del proyecto
```

## 2. Acceso al WSDL

Una vez que el servicio esté ejecutándose, puedes acceder al WSDL en:
```
http://localhost:8080/src/tu-archivo-servicio.php?wsdl
```

Este WSDL describe todos los servicios disponibles, sus métodos, tipos de datos y parámetros.

## 3. Herramientas de Prueba

### Opción 1: SoapUI
1. Descargar e instalar [SoapUI](https://www.soapui.org/)
2. Crear un nuevo proyecto SOAP
3. Importar el WSDL: `http://localhost:8080/src/tu-archivo-servicio.php?wsdl`
4. Generar las peticiones de prueba automáticamente

### Opción 2: Postman
1. Crear una nueva petición POST
2. URL: `http://localhost:8080/src/tu-archivo-servicio.php`
3. Headers:
   - `Content-Type: text/xml; charset=utf-8`
   - `SOAPAction: "urn:setexwsdl#iniciarParqueo"` (para el método iniciarParqueo)
4. Body: Usar el ejemplo SOAP XML de abajo

### Opción 3: Código PHP
Crear un cliente PHP para consumir el servicio (ver ejemplos abajo).

### Opción 4: Cliente de Prueba Automatizado
Utilizar el cliente de prueba incluido en el proyecto que ejecuta una batería completa de pruebas.

## 4. Ejemplos de Peticiones SOAP

### Probar `getVersion`

#### Petición XML:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <getVersion xmlns="urn:setexwsdl">
      <valor>test</valor>
    </getVersion>
  </soap:Body>
</soap:Envelope>
```

#### Cliente PHP:
```php
<?php
require_once('libs/nusoap/nusoap.php');

$client = new nusoap_client('http://localhost:8080/src/tu-archivo-servicio.php?wsdl', true);

$result = $client->call('getVersion', array('valor' => 'test'));

if ($client->fault) {
    echo '<h2>Error</h2><pre>' . $client->faultstring . '</pre>';
} else {
    echo '<h2>Resultado:</h2><pre>' . print_r($result, true) . '</pre>';
}
?>
```

### Probar `iniciarParqueo`

#### Petición XML:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <iniciarParqueo xmlns="urn:setexwsdl">
      <token>mi_token_seguro</token>
      <plazaId>1</plazaId>
      <zonaId>101</zonaId>
      <identificador>ABC123</identificador>
      <tiempoParqueo>60</tiempoParqueo>
      <importeParqueo>500</importeParqueo>
      <passwordCps>password123</passwordCps>
      <fechaInicioParqueo>2026-02-24 10:00:00</fechaInicioParqueo>
      <fechaFinParqueo>2026-02-24 11:00:00</fechaFinParqueo>
      <nroTransaccion>TXN123456</nroTransaccion>
      <fechaTransaccion>2026-02-24 10:00:00</fechaTransaccion>
    </iniciarParqueo>
  </soap:Body>
</soap:Envelope>
```

#### Cliente PHP:
```php
<?php
require_once('libs/nusoap/nusoap.php');

$client = new nusoap_client('http://localhost:8080/src/tu-archivo-servicio.php?wsdl', true);

$params = array(
    'token' => 'mi_token_seguro',
    'plazaId' => 1,
    'zonaId' => 101,
    'identificador' => 'ABC123',
    'tiempoParqueo' => 60,
    'importeParqueo' => 500,
    'passwordCps' => 'password123',
    'fechaInicioParqueo' => '2026-02-24 10:00:00',
    'fechaFinParqueo' => '2026-02-24 11:00:00',
    'nroTransaccion' => 'TXN123456',
    'fechaTransaccion' => '2026-02-24 10:00:00'
);

$result = $client->call('iniciarParqueo', $params);

if ($client->fault) {
    echo '<h2>Error</h2><pre>' . $client->faultstring . '</pre>';
} else {
    echo '<h2>Resultado:</h2><pre>' . print_r($result, true) . '</pre>';
}
?>
```

## 5. Códigos de Respuesta Esperados

### `getVersion`
- **Éxito**: Debería retornar información de versión del servicio
- **Error**: Código de error específico del sistema

### `iniciarParqueo`
- **Éxito**: `codigoRespuesta = 0` (o código de éxito definido)
- **Error**: Código numérico indicando el tipo de error

## 6. Debugging y Logs

### Ver logs del servidor web
```bash
# Para Apache
tail -f /var/log/apache2/error.log

# Para servidor de desarrollo PHP
# Los errores se mostrarán en la terminal donde ejecutaste php -S
```

### Ver logs de la aplicación
Los logs de parqueo se guardan en la carpeta `logs/` con el formato:
- `iniciarParqueo[fecha].txt`
- `iniciarParqueoSetex[fecha].txt`

### Habilitar debugging en nusoap
```php
// Agregar al cliente para ver peticiones y respuestas
$client->debug_flag = true;
echo '<h2>Petición:</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Respuesta:</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
```

## 8. Cliente de Prueba Automatizado

### Descripción
El proyecto incluye un cliente de prueba automatizado (`test-client.php`) que ejecuta una batería completa de pruebas con logs detallados y manejo mejorado de errores.

### Características del Cliente de Prueba

#### ✅ Pruebas Incluidas:
1. **getVersion** - Verificar disponibilidad del servicio
2. **iniciarParqueo válido** - Prueba con datos correctos
3. **Token inválido** - Prueba de seguridad con token incorrecto
4. **Identificador inválido** - Prueba con identificador de longitud incorrecta

#### ✅ Funcionalidades:
- **Logs automáticos** con diferentes niveles (ERROR, WARNING, INFO, SUCCESS, DEBUG)
- **Validación previa** de parámetros
- **Análisis de respuestas** con interpretación de códigos de error
- **Medición de tiempos** de respuesta
- **Manejo de excepciones** robusto
- **Resumen de resultados** visual

### Uso del Cliente de Prueba

#### Configuración:
```php
// Editar test-client.php línea 240
$serviceUrl = 'http://localhost:8080/src/tu-archivo-servicio.php';
```

#### Ejecución:
```bash
# Ejecutar desde la línea de comandos
php test-client.php

# O desde navegador web
http://localhost:8080/test-client.php
```

#### Salida Ejemplo:
```
🚀 Iniciando cliente de prueba SETEX

🧪 Probando getVersion...
🧪 Probando iniciarParqueo con datos válidos...
🧪 Probando iniciarParqueo con token inválido...
🧪 Probando iniciarParqueo con identificador inválido...

📊 Resumen de pruebas:
- getVersion: ✅ EXITOSO
- iniciarParqueo_valid: ✅ EXITOSO
- iniciarParqueo_invalid_token: ✅ EXITOSO (error controlado)
- iniciarParqueo_invalid_id: ✅ EXITOSO (error controlado)

✅ Pruebas completadas. Revisa los logs para más detalles.
```

### Logs Generados

El cliente genera logs detallados en:
- `logs/client_test[fecha].txt` - Logs específicos del cliente
- `logs/validation[fecha].txt` - Logs de validación
- `logs/database[fecha].txt` - Logs de operaciones de BD
- `logs/auth[fecha].txt` - Logs de autenticación

### Personalización de Pruebas

#### Agregar Prueba Personalizada:
```php
// Datos de prueba personalizados
$customData = [
    'token' => 'dc2fec0f5f08fca379553cc7af20d556',
    'plazaId' => 2, // Plaza diferente
    'zonaId' => 205,
    'identificador' => '9876543210987',
    'tiempoParqueo' => 120, // 2 horas
    'importeParqueo' => 2266,
    'passwordCps' => 'custom_password',
    'fechaInicioParqueo' => date('Y-m-d H:i:s'),
    'fechaFinParqueo' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'nroTransaccion' => 'CUSTOM_' . date('YmdHis'),
    'fechaTransaccion' => date('Y-m-d H:i:s')
];

$testClient = new SetexClientTest($serviceUrl);
$result = $testClient->testIniciarParqueo($customData);
```

---

## 9. Sistema de Logs Mejorado

El sistema incluye logs detallados para mejor debugging y monitoreo. Ver [sistema-logs.md](sistema-logs.md) para documentación completa.

### Tipos de Logs:
- **ERROR**: Errores críticos que requieren atención inmediata
- **WARNING**: Situaciones anómalas que requieren revisión
- **INFO**: Información general del flujo del servicio
- **SUCCESS**: Operaciones completadas exitosamente
- **DEBUG**: Información detallada para troubleshooting

### Ubicación de Logs:
```
logs/
├── servicio[fecha].txt           # Logs generales
├── validation[fecha].txt         # Validaciones
├── database[fecha].txt           # Operaciones de BD  
├── auth[fecha].txt              # Autenticación
├── security[fecha].txt          # Eventos de seguridad
├── client_test[fecha].txt       # Cliente de prueba
└── iniciarParqueoSetex[fecha].txt # Servicio específico
```

---

## 10. Solución de Problemas Comunes

### Error de conexión a base de datos
- Verificar credenciales en `src/connect.php`
- Asegurar que el servidor MySQL esté ejecutándose
- Comprobar conectividad de red al servidor AWS RDS

### Error "WSDL not found"
- Verificar que el servicio esté ejecutándose correctamente
- Comprobar la URL del WSDL en el navegador
- Revisar logs del servidor web

### Error SOAP
- Validar formato XML de la petición
- Verificar namespace y SOAPAction
- Comprobar que todos los parámetros requeridos estén presentes

### Timeout de petición
- Aumentar tiempo límite en PHP (`set_time_limit()`)
- Verificar rendimiento de la base de datos
- Comprobar logs de la aplicación