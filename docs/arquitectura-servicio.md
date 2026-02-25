# 📚 Guía del Servicio SETEX SOAP

## 🔍 **¿Cómo Funciona el Servicio?**

### **Arquitectura General**
```
Cliente SOAP ──→ old_setex-wsdl.php ──→ servicio.class.php ──→ Base de Datos
               (Punto Principal)      (Lógica de Negocio)    (MySQL RDS)
```

### **Componentes Principales**

#### 1️⃣ **Puntos de Entrada SOAP**
- � **`setex-wsdl.php`** - Punto de entrada PRINCIPAL (nombre original)
  - Arquitectura original preservada
  - Ruta directa: Cliente SOAP → setex-wsdl.php
  - Mejorado con compatibilidad PHP 8+
  - Logging integrado añadido
  

- 🔧 **`testphp.php`** - Dashboard web + diagnóstico (complementario)
  - Interface web para diagnóstico
  - No altera la arquitectura principal

#### 2️⃣ **Lógica de Negocio**
- **`servicio.class.php`** - Clase principal con métodos:
  - `iniciarParqueo()` - Wrapper público 
  - `iniciarParqueoSetex()` - Lógica interna
  - `getVersion()` - Info del servicio

#### 3️⃣ **Infraestructura**
- **`conexion.class.php`** - Manejo de BD
- **`setex-config.php`** - Configuración flexible
- **`watchdog.php`** - Sistema de logs

## 🚀 **Servicios Disponibles**

### **iniciarParqueo**
Inicia una sesión de parqueo en el sistema.

**Parámetros:**
```xml
<iniciarParqueo>
    <token>string - Token de autenticación</token>
    <plazaId>int - ID de la plaza</plazaId>
    <zonaId>int - ID de la zona</zonaId>
    <identificador>string - Identificador del vehículo</identificador>
    <tiempoParqueo>int - Tiempo en minutos</tiempoParqueo>
    <importeParqueo>int - Importe a cobrar</importeParqueo>
    <passwordCps>string - Password del sistema</passwordCps>
    <fechaInicioParqueo>string - Fecha/hora inicio</fechaInicioParqueo>
    <fechaFinParqueo>string - Fecha/hora fin</fechaFinParqueo>
    <nroTransaccion>string - Número de transacción</nroTransaccion>
    <fechaTransaccion>string - Fecha de transacción</fechaTransaccion>
</iniciarParqueo>
```

**Respuesta:**
```xml
<iniciarParqueoReturn>
    <codigoRespuesta>int - Código de estado</codigoRespuesta>
</iniciarParqueoReturn>
```

### **getVersion**
Obtiene la versión y disponibilidad del servicio.

**Parámetros:**
```xml
<getVersion>
    <valor>string - Valor de consulta</valor>
</getVersion>
```

**Respuesta:**
```xml
<getVersionReturn>
    <codigoRespuesta>string - Versión del servicio</codigoRespuesta>
</getVersionReturn>
```

## 📊 **Códigos de Respuesta**

| Código | Descripción |
|--------|-------------|
| `6` | Tarjeta aprobada |
| `51/6` | Error en parámetros |
| `52` | Error en token |
| `53` | Error en consulta |
| `54` | Servicio offline |
| `57` | Error en ID |

## 🌐 **URLs del Servicio**

### **En Desarrollo Local**
```
🔥 Endpoint SOAP Principal: http://localhost/setex/src/setex-wsdl.php
📋 WSDL Principal:          http://localhost/setex/src/setex-wsdl.php?wsdl
🔧 Dashboard diagnóstico:   http://localhost/setex/src/testphp.php
🧪 Cliente prueba:          http://localhost/setex/test-client.php
```

### **En EC2 Producción**
```
🔥 Endpoint SOAP Principal: http://52.39.146.172/serviceSetex/src/setex-wsdl.php
📋 WSDL Principal:          http://52.39.146.172/serviceSetex/src/setex-wsdl.php?wsdl
🔧 Dashboard diagnóstico:   http://52.39.146.172/serviceSetex/src/testphp.php
🧪 Cliente prueba:          http://52.39.146.172/serviceSetex/test-client.php
```

## 🔧 **Archivos del Sistema**

### **`setex-wsdl.php` (PRINCIPAL) 🔥**
```php
✅ Arquitectura original preservada
✅ Ruta directa: Cliente SOAP → setex-wsdl.php
✅ PHP 8+ compatible (mejorado)
✅ Manejo de errores mejorado
✅ Logging integrado añadido
✅ Funcionalidad completa SOAP
✅ Punto de entrada recomendado para TODOS los clientes
```

### **`testphp.php` (DASHBOARD) 🔧**
```php
✅ Dashboard web de diagnóstico
✅ Información del sistema en tiempo real
✅ No intercepta peticiones SOAP
✅ Preserva la arquitectura original
✅ Solo para monitoreo y debug
```

## 🛠️ **Cómo Generar/Personalizar el WSDL**

### **Opción 1: Usar el Generado**
El archivo `setex-wsdl.php` ya está listo y optimizado.

### **Opción 2: Personalizar Existente**
Edita `setex-wsdl.php` para:
- Añadir nuevos métodos
- Modificar tipos de datos
- Ajustar configuración

### **Opción 3: Regenerar Automáticamente**
```php
// Ejemplo de generación dinámica basada en la clase
$servicio = new ReflectionClass('Servicio');
$methods = $servicio->getMethods(ReflectionMethod::IS_PUBLIC);

foreach ($methods as $method) {
    // Registrar automáticamente en nuSOAP
    $server->register($method->getName(), ...);
}
```

## 🚦 **Recomendaciones de Uso**

### **Para TODOS los Clientes (Recomendado)**
```
Usar: http://tu-servidor/serviceSetex/src/old_setex-wsdl.php
Beneficios: Arquitectura estable, ruta directa, funcionalidad completa
```

### **Para Diagnóstico/Testing**
```
Usar: http://tu-servidor/serviceSetex/src/testphp.php
Beneficios: Dashboard web, información del sistema
```

### **Para Desarrollo/Testing**
```
Usar: http://tu-servidor/serviceSetex/test-client.php
Beneficios: Testing automático, detección de entorno
```

## 📝 **Variables de Entorno Útiles**

```bash
# URL personalizada del servicio
export SETEX_SERVICE_URL="http://mi-servidor.com/setex/src/testphp.php"

# Activar debug mode
export SETEX_DEBUG="true"

# Configuración de base de datos
export DB_HOST="mi-rds-endpoint.amazonaws.com"
export DB_USER="mi_usuario"
export DB_PASS="mi_password"

# Configuración del servidor
export SETEX_SERVER_HOST="mi-dominio.com"
export SETEX_PROTOCOL="https"
```

## 🎯 **Testing Rápido**

### **Verificar Disponibilidad**
```bash
curl http://tu-servidor/serviceSetex/src/testphp.php
```

### **Obtener WSDL**
```bash
curl http://tu-servidor/serviceSetex/src/testphp.php?wsdl
```

### **Test Automático**
```bash
php test-client.php
```

## 🔒 **Consideraciones de Seguridad**

1. **Tokens de autenticación** siempre requeridos
2. **Logs detallados** para auditoría
3. **Error handling** sin exposición de datos sensibles
4. **Variables de entorno** para configuración segura

---

*Generado automáticamente el 2026-02-25 para el proyecto SETEX*