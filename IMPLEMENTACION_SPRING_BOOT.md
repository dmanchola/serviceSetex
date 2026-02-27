# 🚀 Implementación Spring Boot - Servicio SETEX SOAP

## 📁 Estructura Completa del Proyecto

```
setex-soap-service/
├── pom.xml
├── src/main/java/com/setex/soap/
│   ├── SetexSoapApplication.java
│   ├── config/
│   │   ├── WebServiceConfig.java
│   │   └── DatabaseConfig.java  
│   ├── endpoint/
│   │   └── SetexEndpoint.java
│   ├── service/
│   │   └── ParqueoService.java
│   ├── repository/
│   │   ├── TransactionRepository.java
│   │   └── ParkingRepository.java
│   ├── entity/
│   │   ├── Transaction.java
│   │   └── Parking.java
│   └── dto/
│       ├── InitParqueoRequest.java
│       ├── CodigoRespuesta.java
│       └── GetVersionRequest.java
└── src/main/resources/
    ├── application.yml
    ├── schema/setex.xsd
    └── wsdl/setex.wsdl
```

---

## 📄 **pom.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 
         http://maven.apache.org/xsd/maven-4.0.0.xsd">
    <modelVersion>4.0.0</modelVersion>
    <parent>
        <groupId>org.springframework.boot</groupId>
        <artifactId>spring-boot-starter-parent</artifactId>
        <version>3.2.0</version>
        <relativePath/>
    </parent>
    
    <groupId>com.setex</groupId>
    <artifactId>setex-soap-service</artifactId>
    <version>1.0.0</version>
    <packaging>jar</packaging>
    
    <name>SETEX SOAP Service</name>
    <description>Servicio SOAP para parquímetros SETEX</description>
    
    <properties>
        <java.version>17</java.version>
        <maven.compiler.source>17</maven.compiler.source>
        <maven.compiler.target>17</maven.compiler.target>
    </properties>
    
    <dependencies>
        <!-- Spring Boot Web Services -->
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-web-services</artifactId>
        </dependency>
        
        <!-- Spring Boot Data JPA -->
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-data-jpa</artifactId>
        </dependency>
        
        <!-- MySQL Connector -->
        <dependency>
            <groupId>mysql</groupId>
            <artifactId>mysql-connector-java</artifactId>
            <version>8.0.33</version>
        </dependency>
        
        <!-- WSDL Support -->
        <dependency>
            <groupId>wsdl4j</groupId>
            <artifactId>wsdl4j</artifactId>
        </dependency>
        
        <!-- Logging -->
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-logging</artifactId>
        </dependency>
        
        <!-- Validation -->
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-validation</artifactId>
        </dependency>
        
        <!-- Testing -->
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-test</artifactId>
            <scope>test</scope>
        </dependency>
    </dependencies>
    
    <build>
        <plugins>
            <plugin>
                <groupId>org.springframework.boot</groupId>
                <artifactId>spring-boot-maven-plugin</artifactId>
            </plugin>
            
            <!-- JAXB2 Plugin para generar clases desde XSD -->
            <plugin>
                <groupId>org.codehaus.mojo</groupId>
                <artifactId>jaxb2-maven-plugin</artifactId>
                <version>3.1.0</version>
                <executions>
                    <execution>
                        <id>xjc</id>
                        <goals>
                            <goal>xjc</goal>
                        </goals>
                    </execution>
                </executions>
                <configuration>
                    <sources>
                        <source>${project.basedir}/src/main/resources/schema</source>
                    </sources>
                </configuration>
            </plugin>
        </plugins>
    </build>
</project>
```

---

## ⚙️ **application.yml**

```yaml
server:
  port: 8080
  servlet:
    context-path: /setex

spring:
  application:
    name: setex-soap-service
  
  # Database Configuration
  datasource:
    url: jdbc:mysql://alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com:3306/alpha_msj
    username: userAlphaMsj
    password: alpha2000@
    driver-class-name: com.mysql.cj.jdbc.Driver
    hikari:
      connection-timeout: 20000
      maximum-pool-size: 10
      minimum-idle: 5
  
  # JPA Configuration  
  jpa:
    hibernate:
      ddl-auto: validate
    database-platform: org.hibernate.dialect.MySQLDialect
    show-sql: false
    properties:
      hibernate:
        format_sql: true
        use_sql_comments: true

# Logging Configuration
logging:
  level:
    com.setex.soap: DEBUG
    org.springframework.ws: INFO
    org.hibernate.SQL: DEBUG
  pattern:
    file: "%d{yyyy-MM-dd HH:mm:ss} [%thread] %-5level %logger{36} - %msg%n"
  file:
    name: logs/setex-soap.log

# SETEX Configuration
setex:
  auth-token: "dc2fec0f5f08fca379553cc7af20d556"
  version: "3.4"
  
  # Plaza Configuration
  plazas:
    1:
      company-id: "1"
      min-price: "16.00"
    2:
      company-id: "2" 
      min-price: "11.333333333333332"
    3:
      company-id: "3"
      min-price: "12.5"
    4:
      company-id: "7"
      min-price: "10.00"

# Management Endpoints
management:
  endpoints:
    web:
      exposure:
        include: health,info,metrics
  endpoint:
    health:
      show-details: always
```

---

## 🚀 **SetexSoapApplication.java** (Main Class)

```java
package com.setex.soap;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.transaction.annotation.EnableTransactionManagement;

@SpringBootApplication
@EnableTransactionManagement
public class SetexSoapApplication {
    public static void main(String[] args) {
        SpringApplication.run(SetexSoapApplication.class, args);
    }
}
```

---

## ⚙️ **WebServiceConfig.java** (SOAP Configuration)

```java
package com.setex.soap.config;

import org.springframework.boot.web.servlet.ServletRegistrationBean;
import org.springframework.context.ApplicationContext;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.core.io.ClassPathResource;
import org.springframework.ws.config.annotation.EnableWs;
import org.springframework.ws.config.annotation.WsConfigurerAdapter;
import org.springframework.ws.soap.server.endpoint.SoapFaultDefinition;
import org.springframework.ws.soap.server.endpoint.SoapFaultMappingExceptionResolver;
import org.springframework.ws.transport.http.MessageDispatcherServlet;
import org.springframework.ws.wsdl.wsdl11.DefaultWsdl11Definition;
import org.springframework.xml.xsd.SimpleXsdSchema;
import org.springframework.xml.xsd.XsdSchema;

@EnableWs
@Configuration
public class WebServiceConfig extends WsConfigurerAdapter {

    @Bean
    public ServletRegistrationBean<MessageDispatcherServlet> messageDispatcherServlet(
            ApplicationContext applicationContext) {
        MessageDispatcherServlet servlet = new MessageDispatcherServlet();
        servlet.setApplicationContext(applicationContext);
        servlet.setTransformWsdlLocations(true);
        return new ServletRegistrationBean<>(servlet, "/ws/*");
    }

    @Bean(name = "setex")
    public DefaultWsdl11Definition defaultWsdl11Definition(XsdSchema setexSchema) {
        DefaultWsdl11Definition wsdl11Definition = new DefaultWsdl11Definition();
        wsdl11Definition.setPortTypeName("SetexPort");
        wsdl11Definition.setLocationUri("/ws");
        wsdl11Definition.setTargetNamespace("urn:setexwsdl");
        wsdl11Definition.setSchema(setexSchema);
        return wsdl11Definition;
    }

    @Bean
    public XsdSchema setexSchema() {
        return new SimpleXsdSchema(new ClassPathResource("schema/setex.xsd"));
    }

    @Bean
    public SoapFaultMappingExceptionResolver exceptionResolver() {
        SoapFaultMappingExceptionResolver exceptionResolver = 
            new SoapFaultMappingExceptionResolver();

        SoapFaultDefinition faultDefinition = new SoapFaultDefinition();
        faultDefinition.setFaultCode(SoapFaultDefinition.SERVER);
        exceptionResolver.setDefaultFault(faultDefinition);

        return exceptionResolver;
    }
}
```

---

## 📊 **Entity Classes**

### **Transaction.java**

```java
package com.setex.soap.entity;

import jakarta.persistence.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "transactions")
public class Transaction {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    
    @Column(name = "country", length = 10)
    private String country;
    
    @Column(name = "idCompany")
    private String idCompany;
    
    @Column(name = "user")
    private String user;
    
    @Column(name = "type")
    private String type;
    
    @Column(name = "description")
    private String description;
    
    @Column(name = "method")
    private String method;
    
    @Column(name = "authorization")
    private String authorization;
    
    @Column(name = "amount")
    private String amount;
    
    @Column(name = "date")
    private LocalDateTime date;
    
    // Constructors
    public Transaction() {}
    
    public Transaction(String country, String idCompany, String user, String type, 
                      String description, String method, String authorization, 
                      String amount, LocalDateTime date) {
        this.country = country;
        this.idCompany = idCompany;
        this.user = user;
        this.type = type;
        this.description = description;
        this.method = method;
        this.authorization = authorization;
        this.amount = amount;
        this.date = date;
    }
    
    // Getters and Setters
    // ... (generate with IDE)
}
```

### **Parking.java**

```java
package com.setex.soap.entity;

import jakarta.persistence.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "parking")
public class Parking {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    
    @Column(name = "date")
    private LocalDateTime date;
    
    @Column(name = "startTime")
    private LocalDateTime startTime;
    
    @Column(name = "endTime")  
    private LocalDateTime endTime;
    
    @Column(name = "time")
    private Integer time;
    
    @Column(name = "platform")
    private Integer platform;
    
    @Column(name = "tipo")
    private String tipo;
    
    @Column(name = "user")
    private String user;
    
    @Column(name = "plate")
    private String plate;
    
    @Column(name = "place")
    private String place;
    
    @Column(name = "minPrice")
    private String minPrice;
    
    @Column(name = "country")
    private String country;
    
    @Column(name = "idCompany")
    private String idCompany;
    
    @Column(name = "free")
    private Boolean free;
    
    @Column(name = "count")
    private Integer count;
    
    @Column(name = "authorization")
    private String authorization;
    
    // Constructors
    public Parking() {}
    
    // Constructor completo
    // ... 
    
    // Getters and Setters
    // ... (generate with IDE)
}
```

---

## 🎯 **SOAP Endpoint**

### **SetexEndpoint.java**

```java
package com.setex.soap.endpoint;

import com.setex.soap.service.ParqueoService;
import com.setex.soap.dto.*;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.ws.server.endpoint.annotation.Endpoint;
import org.springframework.ws.server.endpoint.annotation.PayloadRoot;
import org.springframework.ws.server.endpoint.annotation.RequestPayload;
import org.springframework.ws.server.endpoint.annotation.ResponsePayload;

@Endpoint
public class SetexEndpoint {
    
    private static final Logger logger = LoggerFactory.getLogger(SetexEndpoint.class);
    private static final String NAMESPACE_URI = "urn:setexwsdl";
    
    @Autowired
    private ParqueoService parqueoService;
    
    @PayloadRoot(namespace = NAMESPACE_URI, localPart = "iniciarParqueo")
    @ResponsePayload
    public CodigoRespuestaComplex iniciarParqueo(@RequestPayload InitParqueoRequest request) {
        logger.info("Received iniciarParqueo request - plazaId: {}, identificador: {}", 
                   request.getPlazaId(), request.getIdentificador());
        
        try {
            CodigoRespuestaComplex response = parqueoService.iniciarParqueo(request);
            
            logger.info("IniciarParqueo completed - codigo: {}", response.getCodigoRespuesta());
            return response;
            
        } catch (Exception e) {
            logger.error("Error processing iniciarParqueo", e);
            
            CodigoRespuestaComplex errorResponse = new CodigoRespuestaComplex();
            errorResponse.setCodigoRespuesta(53); // ERR_QUERY
            return errorResponse;
        }
    }
    
    @PayloadRoot(namespace = NAMESPACE_URI, localPart = "getVersion")
    @ResponsePayload
    public CodigoRespuestaStringComplex getVersion(@RequestPayload GetVersionRequest request) {
        logger.info("Received getVersion request");
        
        CodigoRespuestaStringComplex response = new CodigoRespuestaStringComplex();
        response.setCodigoRespuesta("3.4");
        
        logger.info("GetVersion completed - version: {}", response.getCodigoRespuesta());
        return response;
    }
}
```

---

## 🔧 **Business Service**

### **ParqueoService.java**

```java
package com.setex.soap.service;

import com.setex.soap.dto.*;
import com.setex.soap.entity.*;
import com.setex.soap.repository.*;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Map;

@Service
public class ParqueoService {
    
    private static final Logger logger = LoggerFactory.getLogger(ParqueoService.class);
    private static final DateTimeFormatter DATE_FORMAT = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
    
    @Value("${setex.auth-token}")
    private String authToken;
    
    @Autowired
    private TransactionRepository transactionRepository;
    
    @Autowired  
    private ParkingRepository parkingRepository;
    
    // Plaza configuration - could be loaded from database or config
    private final Map<Integer, PlazaConfig> plazaConfigMap = Map.of(
        1, new PlazaConfig("1", "16.00"),
        2, new PlazaConfig("2", "11.333333333333332"),
        3, new PlazaConfig("3", "12.5"),
        4, new PlazaConfig("7", "10.00")
    );
    
    // Error codes constants
    private static final int TARJETA_APROBADO = 6;
    private static final int ERR_PARAM = 6;
    private static final int ERR_TOKEN = 52;
    private static final int ERR_QUERY = 53;
    private static final int ERR_OFFLINE = 54;
    private static final int ERR_ID = 57;
    
    @Transactional
    public CodigoRespuestaComplex iniciarParqueo(InitParqueoRequest request) {
        logger.info("Processing iniciarParqueo - Plaza: {}, Zona: {}, ID: {}", 
                   request.getPlazaId(), request.getZonaId(), request.getIdentificador());
        
        // 1. Validate token
        if (!authToken.equals(request.getToken())) {
            logger.warn("Invalid token received: {}", request.getToken());
            return createErrorResponse(ERR_TOKEN);
        }
        
        // 2. Validate parameters
        if (!isValidRequest(request)) {
            logger.warn("Invalid parameters in request");
            return createErrorResponse(ERR_PARAM);
        }
        
        // 3. Validate identifier length (must be exactly 13 digits)
        if (request.getIdentificador() == null || request.getIdentificador().length() != 13) {
            logger.warn("Invalid identifier length: {} (expected 13)", 
                       request.getIdentificador() != null ? request.getIdentificador().length() : 0);
            return createErrorResponse(ERR_ID);
        }
        
        // 4. Get plaza configuration
        PlazaConfig plazaConfig = plazaConfigMap.get(request.getPlazaId());
        if (plazaConfig == null) {
            logger.warn("Invalid plaza ID: {}", request.getPlazaId());
            return createErrorResponse(ERR_PARAM);
        }
        
        try {
            // 5. Parse dates
            LocalDateTime fechaInicio = LocalDateTime.parse(request.getFechaInicioParqueo(), DATE_FORMAT);
            LocalDateTime fechaFin = LocalDateTime.parse(request.getFechaFinParqueo(), DATE_FORMAT);
            
            // 6. Insert transaction record
            Transaction transaction = new Transaction(
                "COS",                              // country
                plazaConfig.getCompanyId(),         // idCompany  
                "0",                                // user
                "5",                                // type
                "Parquimetro",                      // description
                "Tarjeta",                          // method
                request.getNroTransaccion(),        // authorization
                String.valueOf(request.getImporteParqueo()), // amount
                fechaInicio                         // date
            );
            
            transactionRepository.save(transaction);
            logger.info("Transaction saved - ID: {}, Authorization: {}", 
                       transaction.getId(), transaction.getAuthorization());
            
            // 7. Insert parking record
            Parking parking = new Parking();
            parking.setDate(LocalDateTime.now());
            parking.setStartTime(fechaInicio);
            parking.setEndTime(fechaFin);
            parking.setTime(request.getTiempoParqueo());
            parking.setPlatform(1);
            parking.setTipo("Parquimetro");
            parking.setUser("0");
            parking.setPlate("Parquimetro" + request.getIdentificador());
            parking.setPlace(String.valueOf(request.getZonaId()));
            parking.setMinPrice(plazaConfig.getMinPrice());
            parking.setCountry("COS");
            parking.setIdCompany(plazaConfig.getCompanyId());
            parking.setFree(false);
            parking.setCount(1);
            parking.setAuthorization(request.getNroTransaccion());
            
            parkingRepository.save(parking);
            logger.info("Parking saved - ID: {}, Plate: {}, Zone: {}", 
                       parking.getId(), parking.getPlate(), parking.getPlace());
            
            // 8. Return success response
            CodigoRespuestaComplex response = new CodigoRespuestaComplex();
            response.setCodigoRespuesta(TARJETA_APROBADO);
            
            logger.info("IniciarParqueo successful - Transaction: {}, Amount: {}", 
                       request.getNroTransaccion(), request.getImporteParqueo());
            
            return response;
            
        } catch (Exception e) {
            logger.error("Error processing iniciarParqueo request", e);
            return createErrorResponse(ERR_QUERY);
        }
    }
    
    private boolean isValidRequest(InitParqueoRequest request) {
        return request.getToken() != null && !request.getToken().trim().isEmpty()
            && request.getPlazaId() != null && request.getPlazaId() > 0
            && request.getZonaId() != null && request.getZonaId() > 0
            && request.getIdentificador() != null && !request.getIdentificador().trim().isEmpty()
            && request.getTiempoParqueo() != null && request.getTiempoParqueo() > 0
            && request.getImporteParqueo() != null && request.getImporteParqueo() > 0
            && request.getFechaInicioParqueo() != null && !request.getFechaInicioParqueo().trim().isEmpty()
            && request.getFechaFinParqueo() != null && !request.getFechaFinParqueo().trim().isEmpty()
            && request.getNroTransaccion() != null && !request.getNroTransaccion().trim().isEmpty();
    }
    
    private CodigoRespuestaComplex createErrorResponse(int errorCode) {
        CodigoRespuestaComplex response = new CodigoRespuestaComplex();
        response.setCodigoRespuesta(errorCode);
        return response;
    }
    
    // Helper class for plaza configuration
    private static class PlazaConfig {
        private final String companyId;
        private final String minPrice;
        
        public PlazaConfig(String companyId, String minPrice) {
            this.companyId = companyId;
            this.minPrice = minPrice;
        }
        
        public String getCompanyId() { return companyId; }
        public String getMinPrice() { return minPrice; }
    }
}
```

---

## 📊 **Repository Interfaces**

### **TransactionRepository.java**

```java
package com.setex.soap.repository;

import com.setex.soap.entity.Transaction;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface TransactionRepository extends JpaRepository<Transaction, Long> {
}
```

### **ParkingRepository.java**

```java  
package com.setex.soap.repository;

import com.setex.soap.entity.Parking;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface ParkingRepository extends JpaRepository<Parking, Long> {
}
```

---

## 📋 **DTO Classes**

### **InitParqueoRequest.java**

```java
package com.setex.soap.dto;

import jakarta.xml.bind.annotation.*;

@XmlAccessorType(XmlAccessType.FIELD)
@XmlType(name = "", propOrder = {
    "token", "plazaId", "zonaId", "identificador", "tiempoParqueo", 
    "importeParqueo", "passwordCps", "fechaInicioParqueo", 
    "fechaFinParqueo", "nroTransaccion", "fechaTransaccion"
})
@XmlRootElement(name = "iniciarParqueo", namespace = "urn:setexwsdl")
public class InitParqueoRequest {
    
    @XmlElement(required = true)
    private String token;
    
    @XmlElement(required = true)
    private Integer plazaId;
    
    @XmlElement(required = true)
    private Integer zonaId;
    
    @XmlElement(required = true)
    private String identificador;
    
    @XmlElement(required = true)
    private Integer tiempoParqueo;
    
    @XmlElement(required = true)
    private Integer importeParqueo;
    
    @XmlElement(required = true)
    private String passwordCps;
    
    @XmlElement(required = true)
    private String fechaInicioParqueo;
    
    @XmlElement(required = true)
    private String fechaFinParqueo;
    
    @XmlElement(required = true)
    private String nroTransaccion;
    
    @XmlElement(required = true)
    private String fechaTransaccion;
    
    // Constructors
    public InitParqueoRequest() {}
    
    // Getters and Setters
    public String getToken() { return token; }
    public void setToken(String token) { this.token = token; }
    
    public Integer getPlazaId() { return plazaId; }
    public void setPlazaId(Integer plazaId) { this.plazaId = plazaId; }
    
    public Integer getZonaId() { return zonaId; }
    public void setZonaId(Integer zonaId) { this.zonaId = zonaId; }
    
    public String getIdentificador() { return identificador; }
    public void setIdentificador(String identificador) { this.identificador = identificador; }
    
    public Integer getTiempoParqueo() { return tiempoParqueo; }
    public void setTiempoParqueo(Integer tiempoParqueo) { this.tiempoParqueo = tiempoParqueo; }
    
    public Integer getImporteParqueo() { return importeParqueo; }
    public void setImporteParqueo(Integer importeParqueo) { this.importeParqueo = importeParqueo; }
    
    public String getPasswordCps() { return passwordCps; }
    public void setPasswordCps(String passwordCps) { this.passwordCps = passwordCps; }
    
    public String getFechaInicioParqueo() { return fechaInicioParqueo; }
    public void setFechaInicioParqueo(String fechaInicioParqueo) { this.fechaInicioParqueo = fechaInicioParqueo; }
    
    public String getFechaFinParqueo() { return fechaFinParqueo; }
    public void setFechaFinParqueo(String fechaFinParqueo) { this.fechaFinParqueo = fechaFinParqueo; }
    
    public String getNroTransaccion() { return nroTransaccion; }
    public void setNroTransaccion(String nroTransaccion) { this.nroTransaccion = nroTransaccion; }
    
    public String getFechaTransaccion() { return fechaTransaccion; }
    public void setFechaTransaccion(String fechaTransaccion) { this.fechaTransaccion = fechaTransaccion; }
}
```

### **CodigoRespuestaComplex.java**

```java
package com.setex.soap.dto;

import jakarta.xml.bind.annotation.*;

@XmlAccessorType(XmlAccessType.FIELD)
@XmlType(name = "codigoRespuestaComplex", propOrder = {"codigoRespuesta"})
public class CodigoRespuestaComplex {
    
    @XmlElement(required = true)
    private Integer codigoRespuesta;
    
    public CodigoRespuestaComplex() {}
    
    public Integer getCodigoRespuesta() { return codigoRespuesta; }
    public void setCodigoRespuesta(Integer codigoRespuesta) { this.codigoRespuesta = codigoRespuesta; }
}
```

---

## 🧪 **Testing Example**

### **SetexSoapIntegrationTest.java**

```java
package com.setex.soap.integration;

import org.junit.jupiter.api.Test;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.web.server.LocalServerPort;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.ws.client.core.WebServiceTemplate;
import com.setex.soap.dto.*;

@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
@ActiveProfiles("test")
public class SetexSoapIntegrationTest {
    
    @LocalServerPort  
    private int port;
    
    @Test
    public void testIniciarParqueo() {
        WebServiceTemplate webServiceTemplate = new WebServiceTemplate();
        
        InitParqueoRequest request = new InitParqueoRequest();
        request.setToken("dc2fec0f5f08fca379553cc7af20d556");
        request.setPlazaId(2);
        request.setZonaId(999);
        request.setIdentificador("1234567890123");
        request.setTiempoParqueo(30);
        request.setImporteParqueo(50);
        request.setPasswordCps("test");
        request.setFechaInicioParqueo("2026-02-26 15:00:00");
        request.setFechaFinParqueo("2026-02-26 15:30:00");
        request.setNroTransaccion("TEST123");
        request.setFechaTransaccion("2026-02-26 15:00:00");
        
        CodigoRespuestaComplex response = (CodigoRespuestaComplex) 
            webServiceTemplate.marshalSendAndReceive(
                "http://localhost:" + port + "/setex/ws", request);
        
        assert response.getCodigoRespuesta() == 6;
    }
}
```

---

## 🚀 **Comandos para Ejecutar**

```bash
# 1. Clonar estructura del proyecto
mkdir setex-soap-service
cd setex-soap-service

# 2. Crear estructura de archivos
# (Copiar todos los archivos anteriores)

# 3. Compilar y ejecutar
mvn clean compile
mvn spring-boot:run

# 4. Verificar WSDL
curl http://localhost:8080/setex/ws/setex.wsdl

# 5. Testing
mvn test

# 6. Generar JAR
mvn clean package
java -jar target/setex-soap-service-1.0.0.jar
```

---

## 🎯 **Próximos Pasos**

1. **Implementar este código base**  
2. **Configurar XSD para generar DTOs automáticamente**
3. **Crear tests de integración completos**
4. **Setup CI/CD pipeline**  
5. **Deploy en ambiente de staging**
6. **Validación con cliente PHP existente**

¿Te ayudo con alguno de estos pasos específicos? 🚀