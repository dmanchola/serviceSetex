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

## 📝 **Variables de Entorno (.env)**

El proyecto ahora usa un archivo `.env` para configuración:

### **Archivo .env de Ejemplo:**
```bash
# Servidor y URLs
SETEX_SERVER_HOST="52.39.146.172"
SETEX_PROTOCOL="http"  
SETEX_SERVICE_URL="http://52.39.146.172/serviceSetex/src/setex-wsdl.php"

# Base de Datos
DB_HOST="alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com"
DB_USER="userAlphaMsj"
DB_PASS="alpha2000@"
DB_NAME="alpha_msj"
DB_PORT="3306"

# Configuración
SETEX_DEBUG="false"
SETEX_LOG_ENABLED="false"
ENVIRONMENT="production"
```

### **Configuración por Entorno:**

**🏠 Desarrollo Local:**
```bash
SETEX_SERVER_HOST="localhost"
SETEX_DEBUG="true"
SETEX_LOG_ENABLED="true"
ENVIRONMENT="development"
```

**☁️ Producción EC2:**
```bash
SETEX_SERVER_HOST="tu-ip-publica"
SETEX_DEBUG="false"
SETEX_LOG_ENABLED="false"
ENVIRONMENT="production"
```

## 📋 **Instalación Rápida**

### **1️⃣ Instalar en Ubuntu 24**
```bash
# Instalar dependencias
sudo apt update && sudo apt install apache2 php8.3 php8.3-mysqli php8.3-soap git -y

# Clonar proyecto
cd /var/www/html
sudo git clone https://github.com/tu-usuario/tu-repositorio.git serviceSetex
cd serviceSetex

# Configurar .env
sudo cp .env.example .env
sudo nano .env  # Personalizar configuración

# Configurar permisos
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 logs/
```

### **2️⃣ Testing Rápido**
```bash
# Verificar servicio
curl http://tu-ip/serviceSetex/src/testphp.php

# Test automático
php test-client.php
```

## 🔒 **Consideraciones de Seguridad**

1. **Tokens de autenticación** siempre requeridos
2. **Logs detallados** para auditoría
3. **Error handling** sin exposición de datos sensibles
4. **Variables de entorno** para configuración segura

---

*Generado automáticamente el 2026-02-25 para el proyecto SETEX*