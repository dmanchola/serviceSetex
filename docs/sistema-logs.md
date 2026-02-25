# Sistema de Logs Mejorado - SETEX

## Descripción General

El sistema de logs ha sido mejorado para proporcionar un mejor manejo de errores, debugging y monitoreo del servicio web SETEX. Ahora incluye diferentes niveles de log, información contextual estructurada y logs automáticos en puntos críticos.

## Niveles de Log

### 1. **ERROR**
- **Uso:** Errores críticos que afectan la funcionalidad
- **Ejemplos:** 
  - Conexión a base de datos fallida
  - Queries SQL que fallan
  - Validación de parámetros fallida

### 2. **WARNING** 
- **Uso:** Situaciones que requieren atención pero no detienen el servicio
- **Ejemplos:**
  - Token de autenticación inválido
  - Identificador con longitud incorrecta
  - Parámetros sospechosos

### 3. **INFO**
- **Uso:** Información general del flujo del servicio
- **Ejemplos:**
  - Inicio de servicios
  - Parámetros recibidos
  - Operaciones exitosas

### 4. **SUCCESS**
- **Uso:** Operaciones completadas exitosamente
- **Ejemplos:**
  - Conexión a BD establecida
  - Validaciones exitosas
  - Parqueo iniciado correctamente

### 5. **DEBUG**
- **Uso:** Información detallada para debugging
- **Ejemplos:**
  - Validación de longitud de identificadores
  - Queries SQL antes de ejecutarse
  - Información de contexto detallada

## Nuevas Funcionalidades de Log

### Métodos Específicos por Nivel

```php
// Log de errores
watchDog::logError($message, $context, $file);

// Log de advertencias  
watchDog::logWarning($message, $context, $file);

// Log de información
watchDog::logInfo($message, $context, $file);

// Log de éxito
watchDog::logSuccess($message, $context, $file);

// Log de debug
watchDog::logDebug($message, $context, $file);
```

### Métodos Especializados

```php
// Log de validación de parámetros
watchDog::logValidation($result, $params, $file);

// Log de operaciones de base de datos
watchDog::logDatabase($operation, $query, $success, $error, $file);

// Log de autenticación
watchDog::logAuth($token, $success, $file);
```

## Estructura de Archivos de Log

Los logs se guardan en la carpeta `logs/` con el siguiente formato:

```
logs/
├── servicio2026-02-25.txt        # Logs generales del servicio
├── validation2026-02-25.txt      # Logs de validación
├── database2026-02-25.txt        # Logs de base de datos
├── auth2026-02-25.txt            # Logs de autenticación
├── security2026-02-25.txt        # Logs de seguridad
├── errors2026-02-25.txt          # Logs de errores
├── warnings2026-02-25.txt        # Logs de advertencias
├── info2026-02-25.txt            # Logs informativos
├── success2026-02-25.txt         # Logs de operaciones exitosas
├── debug2026-02-25.txt           # Logs de debug
└── iniciarParqueoSetex2026-02-25.txt  # Logs específicos del servicio
```

## Información Contextual

Cada log ahora incluye información contextual estructurada en formato JSON:

### Ejemplo de Log de Error:
```
[ERROR] Error en query de base de datos | Context: {
    "error_message": "Table 'alpha_msj.parking' doesn't exist",
    "error_number": 1146,
    "query_type": "INSERT parking",
    "transaction_number": "TXN123456"
} Archivo: /path/to/servicio.class.php Linea: 156 Fecha: 2026-02-25 10:30:15
```

### Ejemplo de Log de Éxito:
```
[SUCCESS] Parqueo iniciado exitosamente | Context: {
    "plaza_id": "1",
    "zona_id": "101", 
    "identificador": "ABC123",
    "tiempo_parqueo": "60",
    "importe": "500",
    "transaction_number": "TXN123456",
    "codigo_respuesta": 6
} Archivo: /path/to/servicio.class.php Linea: 180 Fecha: 2026-02-25 10:30:16
```

## Puntos de Log Agregados

### 1. **Inicio del Servicio**
- Conexión a base de datos
- Inicialización de servicios

### 2. **Autenticación**
- Validación de tokens
- Intentos de autenticación fallidos

### 3. **Validación de Parámetros**
- Parámetros faltantes o vacíos
- Validación exitosa con lista de parámetros

### 4. **Operaciones de Base de Datos**
- Queries ejecutadas
- Errores de SQL con detalles completos
- Operaciones exitosas con IDs generados

### 5. **Lógica de Negocio**
- Validación de identificadores
- Cálculo de precios por plaza
- Flujo completo del servicio iniciarParqueo

### 6. **Manejo de Errores**
- Excepciones capturadas con stack trace
- Errores de conexión
- Códigos de respuesta de error

## Configuración de Logs

### Habilitar/Deshabilitar Logs por Tipo

Los logs están habilitados por defecto. Para modificar el comportamiento:

```php
// En servicio.class.php
$enableLog = true; // Cambiar a false para deshabilitar logs detallados
```

### Logs de Debug

Los logs de debug se pueden usar para troubleshooting detallado sin afectar el rendimiento en producción.

## Monitoreo y Análisis

### Búsqueda de Errores
```bash
# Buscar todos los errores del día
grep "ERROR" logs/*$(date +%Y-%m-%d).txt

# Buscar errores de autenticación
grep "ERROR.*auth" logs/auth$(date +%Y-%m-%d).txt

# Buscar errores de base de datos
grep "ERROR" logs/database$(date +%Y-%m-%d).txt
```

### Análisis de Patrones
```bash
# Contar tipos de errores
grep "ERROR" logs/*$(date +%Y-%m-%d).txt | cut -d']' -f2 | cut -d'|' -f1 | sort | uniq -c

# Ver últimos logs en tiempo real
tail -f logs/servicio$(date +%Y-%m-%d).txt
```

## Mejores Prácticas

### 1. **Revisión Regular**
- Revisar logs diariamente para identificar patrones
- Monitorear logs de error y warning especialmente

### 2. **Limpieza de Logs**
- Los logs se acumulan por día
- Implementar rotación automática después de cierto período

### 3. **Alertas**
- Configurar alertas para errores críticos (ERR_OFFLINE, ERR_QUERY)
- Monitorear intentos de autenticación fallidos

### 4. **Performance**
- Los logs de DEBUG pueden ser deshabilitados en producción
- Mantener logs INFO, SUCCESS, WARNING y ERROR para monitoreo

## Códigos de Error Monitoreados

- **ERR_PARAM (6):** Parámetros faltantes o inválidos
- **ERR_TOKEN (52):** Token de autenticación inválido  
- **ERR_QUERY (53):** Error en consulta SQL
- **ERR_OFFLINE (54):** Error de conexión a base de datos
- **ERR_ID (57):** Identificador inválido
- **TARJETA_APROBADO (6):** Transacción aprobada exitosamente

## Troubleshooting

### Problema: No se generan logs
1. Verificar permisos de escritura en carpeta `logs/`
2. Verificar que la carpeta `logs/` exista
3. Revisar logs de PHP para errores

### Problema: Logs muy grandes
1. Implementar rotación diaria automática
2. Comprimir logs antiguos
3. Ajustar nivel de log (deshabilitar DEBUG)

### Problema: Errores frecuentes
1. Revisar logs de ERROR para patrones
2. Verificar configuración de base de datos
3. Validar parámetros de entrada del servicio