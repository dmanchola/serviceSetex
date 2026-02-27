# 📋 Especificación Técnica para Migración a Java - Servicio SETEX SOAP

## 🎯 **Resumen Ejecutivo**

**Servicio Actual:** Sistema SOAP de parquímetros en PHP/nuSOAP
**Goal:** Migrar a Java Spring Boot manteniendo 100% compatibilidad SOAP
**Compatibilidad:** CRÍTICA - XML requests/responses deben permanecer idénticos

---

## 🏗️ **Arquitectura del Servicio Actual**

```
Cliente SOAP ──→ setex-wsdl.php ──→ servicio.class.php ──→ MySQL RDS
             (Endpoint SOAP)     (Lógica Negocio)      (Base Datos)
```

### **Componentes Principales**
- **Endpoint SOAP:** `setex-wsdl.php` (nuSOAP Server)
- **Lógica de Negocio:** `servicio.class.php` (Clase PHP) 
- **Base de Datos:** MySQL en AWS RDS
- **Configuración:** SOAP RPC/Encoded, namespace `urn:setexwsdl`

---

## 🌐 **Servicios SOAP Expuestos**

### **1. iniciarParqueo**
**Descripción:** Registra una nueva sesión de parqueo desde parquímetro

#### **Parámetros de Entrada (11 parámetros):**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `token` | `xsd:string` | Token de autenticación | `dc2fec0f5f08fca379553cc7af20d556` |
| `plazaId` | `xsd:int` | ID de la plaza (1-4) | `2` |
| `zonaId` | `xsd:int` | ID de la zona específica | `999` |
| `identificador` | `xsd:string` | ID vehículo (13 dígitos) | `9876543210987` |
| `tiempoParqueo` | `xsd:int` | Duración en minutos | `30` |
| `importeParqueo` | `xsd:int` | Monto a cobrar | `50` |
| `passwordCps` | `xsd:string` | Password sistema CPS | `pwd123` |
| `fechaInicioParqueo` | `xsd:string` | Timestamp inicio | `2026-02-26 15:00:00` |
| `fechaFinParqueo` | `xsd:string` | Timestamp fin calculado | `2026-02-26 15:30:00` |
| `nroTransaccion` | `xsd:string` | Número de transacción | `TXN999` |
| `fechaTransaccion` | `xsd:string` | Timestamp transacción | `2026-02-26 15:00:00` |

#### **Respuesta:**
```xml
<iniciarParqueoReturn>
    <codigoRespuesta>6</codigoRespuesta>  <!-- int -->
</iniciarParqueoReturn>
```

#### **XML Request Format (DEBE mantenerse idéntico):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema">
<SOAP-ENV:Body>
<m:iniciarParqueo xmlns:m="urn:setexwsdl">
<token xsi:type="xsd:string">dc2fec0f5f08fca379553cc7af20d556</token>
<plazaId xsi:type="xsd:int">2</plazaId>
<zonaId xsi:type="xsd:int">999</zonaId>
<identificador xsi:type="xsd:string">9876543210987</identificador>
<tiempoParqueo xsi:type="xsd:int">30</tiempoParqueo>
<importeParqueo xsi:type="xsd:int">50</importeParqueo>
<passwordCps xsi:type="xsd:string">pwd123</passwordCps>
<fechaInicioParqueo xsi:type="xsd:string">2026-02-26 15:00:00</fechaInicioParqueo>
<fechaFinParqueo xsi:type="xsd:string">2026-02-26 15:30:00</fechaFinParqueo>
<nroTransaccion xsi:type="xsd:string">TXN999</nroTransaccion>
<fechaTransaccion xsi:type="xsd:string">2026-02-26 15:00:00</fechaTransaccion>
</m:iniciarParqueo>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

### **2. getVersion**
**Descripción:** Retorna versión y disponibilidad del servicio

#### **Parámetros:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `valor` | `xsd:string` | Parámetro de consulta |

#### **Respuesta:**
```xml
<getVersionReturn>
    <codigoRespuesta>3.4</codigoRespuesta>  <!-- string -->
</getVersionReturn>
```

---

## 📊 **Códigos de Respuesta**

| Código | Constante PHP | Descripción | Uso |
|--------|---------------|-------------|-----|
| `6` | `TARJETA_APROBADO` | ✅ Parqueo iniciado exitosamente | Success |
| `6` | `ERR_PARAM` | ❌ Parámetros faltantes o inválidos | Error |
| `52` | `ERR_TOKEN` | ❌ Token de autenticación inválido | Security |
| `53` | `ERR_QUERY` | ❌ Error en consulta de base de datos | Database |
| `54` | `ERR_OFFLINE` | ❌ Servicio no disponible | Infrastructure |
| `57` | `ERR_ID` | ❌ Identificador inválido (≠13 dígitos) | Validation |

---

## 🗄️ **Base de Datos - Esquema**

### **Conexión:**
- **Host:** `alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com`
- **Puerto:** `3306`
- **Base:** `alpha_msj`
- **Usuario:** `userAlphaMsj`
- **Password:** `alpha2000@`

### **Tablas Utilizadas:**

#### **1. transactions**
```sql
INSERT INTO transactions 
(country, idCompany, user, type, description, method, authorization, amount, date)
VALUES ('COS', '<idCompany>', '0', '5', 'Parquimetro', 'Tarjeta', '<nroTransaccion>', '<importeParqueo>', '<fechaInicioParqueo>')
```

#### **2. parking**
```sql
INSERT INTO parking 
(date, startTime, endTime, time, platform, tipo, user, plate, place, minPrice, country, idCompany, free, count, authorization)
VALUES (NOW(), '<fechaInicioParqueo>', '<fechaFinParqueo>', <tiempoParqueo>, 1, 'Parquimetro', '0', 'Parquimetro<identificador>', '<zonaId>', '<minPrice>', 'COS', '<idCompany>', 0, 1, '<nroTransaccion>')
```

---

## 🔧 **Lógica de Negocio Detallada**

### **1. Autenticación**
```java
// Token válido hardcodeado
private static final String AUTH_TOKEN = "dc2fec0f5f08fca379553cc7af20d556";

if (!AUTH_TOKEN.equals(token)) {
    return new CodigoRespuesta(52); // ERR_TOKEN
}
```

### **2. Validación de Identificador**
```java
// El identificador DEBE tener exactamente 13 dígitos
if (identificador == null || identificador.length() != 13) {
    return new CodigoRespuesta(57); // ERR_ID
}
```

### **3. Mapeo Plaza → Empresa + Precio Mínimo**
```java
Map<Integer, CompanyInfo> plazaMapping = Map.of(
    1, new CompanyInfo("1", "16.00"),           // Plaza 1
    2, new CompanyInfo("2", "11.333333333333332"), // Plaza 2  
    3, new CompanyInfo("3", "12.5"),            // Plaza 3
    4, new CompanyInfo("7", "10.00")            // Plaza 4
);
```

### **4. Inserción de Registros (Transaccional)**
```java
@Transactional
public CodigoRespuesta iniciarParqueo(ParqueoRequest request) {
    try {
        // 1. Insertar en transactions
        transactionRepository.save(new Transaction(
            country: "COS",
            idCompany: companyInfo.getId(),
            user: "0", 
            type: "5",
            description: "Parquimetro",
            method: "Tarjeta",
            authorization: request.getNroTransaccion(),
            amount: request.getImporteParqueo(),
            date: request.getFechaInicioParqueo()
        ));
        
        // 2. Insertar en parking
        parkingRepository.save(new Parking(
            date: Instant.now(),
            startTime: request.getFechaInicioParqueo(),
            endTime: request.getFechaFinParqueo(),
            time: request.getTiempoParqueo(),
            platform: 1,
            tipo: "Parquimetro",
            user: "0",
            plate: "Parquimetro" + request.getIdentificador(),
            place: request.getZonaId(),
            minPrice: companyInfo.getMinPrice(),
            country: "COS",
            idCompany: companyInfo.getId(),
            free: false,
            count: 1,
            authorization: request.getNroTransaccion()
        ));
        
        return new CodigoRespuesta(6); // TARJETA_APROBADO
        
    } catch (Exception e) {
        log.error("Error en iniciarParqueo", e);
        return new CodigoRespuesta(53); // ERR_QUERY
    }
}
```

---

## 🚀 **Recomendación Tecnológica Java**

### **🥇 Opción Recomendada: Spring Boot + Spring WS**

#### **Ventajas:**
✅ **SOAP Nativo** - Soporte completo RPC/Encoded  
✅ **Contract-First** - Genera código desde WSDL existente  
✅ **Transacciones** - @Transactional automático  
✅ **Logging** - Integración nativa con SLF4J/Logback  
✅ **Testing** - MockMvc para testing SOAP  
✅ **Comunidad** - Ecosystem maduro y estable  

#### **Stack Tecnológico:**
```xml
<dependencies>
    <!-- Core Spring Boot -->
    <dependency>
        <groupId>org.springframework.boot</groupId>
        <artifactId>spring-boot-starter-web-services</artifactId>
    </dependency>
    
    <!-- Database -->
    <dependency>
        <groupId>org.springframework.boot</groupId>
        <artifactId>spring-boot-starter-data-jpa</artifactId>
    </dependency>
    <dependency>
        <groupId>mysql</groupId>
        <artifactId>mysql-connector-java</artifactId>
    </dependency>
    
    <!-- SOAP Processing -->
    <dependency>
        <groupId>wsdl4j</groupId>
        <artifactId>wsdl4j</artifactId>
    </dependency>
</dependencies>
```

### **📁 Estructura de Proyecto Recomendada**
```
setex-soap-service/
├── src/main/java/
│   └── com/setex/soap/
│       ├── SetexSoapApplication.java
│       ├── config/
│       │   ├── WebServiceConfig.java
│       │   └── DatabaseConfig.java
│       ├── endpoint/
│       │   └── SetexEndpoint.java
│       ├── service/
│       │   └── ParqueoService.java
│       ├── repository/
│       │   ├── TransactionRepository.java
│       │   └── ParkingRepository.java
│       ├── entity/
│       │   ├── Transaction.java
│       │   └── Parking.java
│       └── dto/
│           ├── InitParqueoRequest.java
│           └── CodigoRespuesta.java
├── src/main/resources/
│   ├── application.yml
│   ├── schema/
│   │   └── setex.xsd
│   └── wsdl/
│       └── setex.wsdl
└── src/test/java/
    └── com/setex/soap/
        └── integration/
            └── SetexSoapIntegrationTest.java
```

---

## 📋 **Plan de Implementación**

### **Fase 1: Setup & Configuración (2-3 días)**
1. ✅ Crear proyecto Spring Boot
2. ✅ Configurar dependencias SOAP
3. ✅ Setup base de datos MySQL
4. ✅ Generar WSDL compatible

### **Fase 2: Desarrollo Core (3-4 días)**
1. ✅ Implementar endpoint `iniciarParqueo`
2. ✅ Implementar endpoint `getVersion` 
3. ✅ Configurar entidades JPA
4. ✅ Implementar lógica de negocio

### **Fase 3: Testing & Validación (2-3 días)**
1. ✅ Tests unitarios de servicios
2. ✅ Tests de integración SOAP
3. ✅ Validación con cliente existente
4. ✅ Performance testing

### **Fase 4: Deploy & Cutover (1-2 días)**
1. ✅ Deploy en ambiente de staging
2. ✅ Pruebas end-to-end
3. ✅ Switch DNS/Load Balancer
4. ✅ Monitoreo post-deploy

---

## ⚠️ **Consideraciones Críticas**

### **Compatibilidad SOAP:**
- **WSDL IDÉNTICO** - namespace, operations, types
- **XML Schema** - Preservar tipos `xsd:string`, `xsd:int`
- **RPC/Encoded** - Mantener estilo SOAP original
- **Headers** - SOAPAction, Content-Type iguales

### **Base de Datos:**
- **Mismas tablas** - `transactions`, `parking`
- **Campos exactos** - No cambiar nombres ni tipos
- **Transaccionalidad** - Rollback en errores

### **Testing:**
- **Regression** - Todos los casos de prueba existentes
- **Performance** - Mismo throughput o mejor
- **Monitoring** - Logs equivalentes para troubleshooting

---

## 🔗 **URLs de Referencia**

### **Producción:**
- **WSDL Actual:** `http://52.39.146.172/serviceSetex/src/setex-wsdl.php?wsdl`
- **Endpoint:** `http://52.39.146.172/serviceSetex/src/setex-wsdl.php`

### **Desarrollo:**
- **WSDL Dev:** `http://54.187.87.75/serviceSetex/src/setex-wsdl.php?wsdl`
- **Endpoint Dev:** `http://54.187.87.75/serviceSetex/src/setex-wsdl.php`

### **Target Java (propuesto):**
- **WSDL:** `http://[new-host]/setex/ws/setex?wsdl`
- **Endpoint:** `http://[new-host]/setex/ws/setex`

---

## 📞 **Contacto y Siguientes Pasos**

**¿Necesitas ayuda con la implementación?**

1. 🏗️ **Arquitectura Spring Boot detallada**
2. 📝 **Código base completo** 
3. 🧪 **Scripts de testing**
4. 🚀 **Configuración de deploy**

**¡Estoy listo para ayudarte con cualquier parte del proceso de migración!** 🚀

---

*Documento generado: 26 Feb 2026 | Versión: 1.0 | Estado: Listo para implementación*