# 🔄 Migración a Java - SETEX SOAP Service

## 🚨 **IMPORTANTE: Este Proyecto se está Migrando**

### **📍 Nuevo Proyecto Java:**
```
Ubicación: ~/alphaig/setex-java-service/
Tecnología: Java 17 + Spring Boot 3.2
Estado: En desarrollo
```

### **🎯 Objetivo de Migración:**
- **Mantener 100% compatibilidad SOAP** con clientes existentes  
- **Mejorar performance** (JVM vs PHP interpreted)
- **Mejor monitoreo** y mantenimiento a largo plazo
- **Testing robusto** integrado

### **📋 Status Actual:**

#### **✅ Completado:**
- Análisis completo del servicio PHP actual
- Especificación técnica detallada 
- Diseño de arquitectura Java/Spring Boot
- Documentación completa de migración
- Setup inicial del proyecto Java

#### **🔄 En Progreso:**
- Implementación de endpoints SOAP
- Testing de compatibilidad
- Validación con base de datos

#### **📅 Próximos Pasos:**
- Deploy en ambiente de desarrollo
- Testing en paralelo (PHP vs Java)
- Migración gradual de tráfico

---

## 📂 **Archivos de Referencia en Proyecto Java:**

| Archivo | Descripción |
|---------|-------------|
| **[MIGRACION_JAVA_SPEC.md](../setex-java-service/docs/MIGRACION_JAVA_SPEC.md)** | Especificación completa |
| **[IMPLEMENTACION_SPRING_BOOT.md](../setex-java-service/docs/IMPLEMENTACION_SPRING_BOOT.md)** | Código Java completo |
| **[CLIENTES_JAVA_SOAP.md](../setex-java-service/docs/CLIENTES_JAVA_SOAP.md)** | Clientes de prueba |

---

## 🔧 **Para Desarrolladores:**

### **Servicio PHP Actual (Este proyecto):**
```bash
# Endpoint SOAP
http://54.187.87.75/serviceSetex/src/setex-wsdl.php

# Testing
curl -X POST http://54.187.87.75/serviceSetex/src/setex-wsdl.php \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: \"urn:setexwsdl#getVersion\"" \
  -d '<soap:Envelope>...</soap:Envelope>'
```

### **Servicio Java (En desarrollo):**
```bash
# Nuevo Workspace
cd ~/alphaig/setex-java-service/

# Compilar & Ejecutar
mvn spring-boot:run

# Endpoint SOAP (cuando esté listo)
http://localhost:8080/setex/ws
```

---

## ⚠️ **Notas para Mantenimiento:**

1. **Durante migración:** Ambos servicios funcionarán en paralelo
2. **Testing:** Usar clientes en ambos proyectos para validar compatibilidad  
3. **Base de datos:** Misma instancia RDS para ambos servicios
4. **Logs:** Separados pero en mismo servidor para comparación
5. **Rollback:** Este servicio PHP permanece disponible como respaldo

---

**📞 Contacto:** Para consultas sobre migración, revisar documentación en proyecto Java

*Última actualización: 26 Feb 2026*