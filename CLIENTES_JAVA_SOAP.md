# 🧪 Cliente de Prueba Java - Servicio SETEX SOAP

## 📋 **Cliente SOAP en Java**

### **SetexSoapClient.java**

```java
package com.setex.soap.client;

import org.springframework.ws.client.core.WebServiceTemplate;
import org.springframework.ws.soap.client.core.SoapActionCallback;
import com.setex.soap.dto.*;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

public class SetexSoapClient {
    
    private static final String SOAP_ENDPOINT = "http://localhost:8080/setex/ws";
    private static final String NAMESPACE = "urn:setexwsdl";
    private static final DateTimeFormatter DATE_FORMAT = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
    
    private final WebServiceTemplate webServiceTemplate;
    
    public SetexSoapClient() {
        this.webServiceTemplate = new WebServiceTemplate();
    }
    
    /**
     * Cliente para probar el servicio iniciarParqueo
     */
    public CodigoRespuestaComplex iniciarParqueo(String token, int plazaId, int zonaId, 
                                               String identificador, int tiempoParqueo, 
                                               int importeParqueo, String passwordCps) {
        
        // Calcular fechas
        LocalDateTime ahora = LocalDateTime.now();
        LocalDateTime fin = ahora.plusMinutes(tiempoParqueo);
        
        // Crear request
        InitParqueoRequest request = new InitParqueoRequest();
        request.setToken(token);
        request.setPlazaId(plazaId);
        request.setZonaId(zonaId);
        request.setIdentificador(identificador);
        request.setTiempoParqueo(tiempoParqueo);
        request.setImporteParqueo(importeParqueo);
        request.setPasswordCps(passwordCps);
        request.setFechaInicioParqueo(ahora.format(DATE_FORMAT));
        request.setFechaFinParqueo(fin.format(DATE_FORMAT));
        request.setNroTransaccion("TXN_" + System.currentTimeMillis());
        request.setFechaTransaccion(ahora.format(DATE_FORMAT));
        
        // Crear callback con SOAPAction
        SoapActionCallback callback = new SoapActionCallback(NAMESPACE + "#iniciarParqueo");
        
        // Enviar request
        CodigoRespuestaComplex response = (CodigoRespuestaComplex) 
            webServiceTemplate.marshalSendAndReceive(SOAP_ENDPOINT, request, callback);
        
        return response;
    }
    
    /**
     * Cliente para probar el servicio getVersion
     */
    public CodigoRespuestaStringComplex getVersion() {
        GetVersionRequest request = new GetVersionRequest();
        request.setValor("test");
        
        SoapActionCallback callback = new SoapActionCallback(NAMESPACE + "#getVersion");
        
        CodigoRespuestaStringComplex response = (CodigoRespuestaStringComplex)
            webServiceTemplate.marshalSendAndReceive(SOAP_ENDPOINT, request, callback);
            
        return response;
    }
    
    /**
     * Método principal para testing
     */
    public static void main(String[] args) {
        SetexSoapClient client = new SetexSoapClient();
        
        System.out.println("🚀 Testing SETEX SOAP Service...\n");
        
        try {
            // Test 1: getVersion
            System.out.println("📋 Test 1: getVersion");
            CodigoRespuestaStringComplex versionResponse = client.getVersion();
            System.out.println("✅ Version: " + versionResponse.getCodigoRespuesta());
            System.out.println();
            
            // Test 2: iniciarParqueo - Caso exitoso
            System.out.println("📋 Test 2: iniciarParqueo - Caso exitoso");
            CodigoRespuestaComplex parqueoResponse = client.iniciarParqueo(
                "dc2fec0f5f08fca379553cc7af20d556", // token válido
                2,                                   // plazaId
                999,                                 // zonaId  
                "1234567890123",                     // identificador (13 dígitos)
                30,                                  // tiempoParqueo
                50,                                  // importeParqueo
                "test123"                            // passwordCps
            );
            System.out.println("✅ Código respuesta: " + parqueoResponse.getCodigoRespuesta());
            if (parqueoResponse.getCodigoRespuesta() == 6) {
                System.out.println("🎉 Parqueo iniciado exitosamente!");
            }
            System.out.println();
            
            // Test 3: iniciarParqueo - Token inválido
            System.out.println("📋 Test 3: iniciarParqueo - Token inválido");
            CodigoRespuestaComplex errorResponse = client.iniciarParqueo(
                "token_invalido",                   // token inválido
                2, 999, "1234567890123", 30, 50, "test123"
            );
            System.out.println("❌ Código respuesta: " + errorResponse.getCodigoRespuesta());
            if (errorResponse.getCodigoRespuesta() == 52) {
                System.out.println("🔒 Error de token - como esperado");
            }
            System.out.println();
            
            // Test 4: iniciarParqueo - ID inválido
            System.out.println("📋 Test 4: iniciarParqueo - ID inválido");
            CodigoRespuestaComplex idErrorResponse = client.iniciarParqueo(
                "dc2fec0f5f08fca379553cc7af20d556", 
                2, 999, "12345",                    // identificador corto (inválido)
                30, 50, "test123"
            );
            System.out.println("❌ Código respuesta: " + idErrorResponse.getCodigoRespuesta());
            if (idErrorResponse.getCodigoRespuesta() == 57) {
                System.out.println("🆔 Error de ID - como esperado");
            }
            
        } catch (Exception e) {
            System.err.println("❌ Error en cliente: " + e.getMessage());
            e.printStackTrace();
        }
        
        System.out.println("\n🎯 Testing completado!");
    }
}
```

---

## 📱 **Cliente Simplificado**

### **SimpleSetexClient.java**

```java
package com.setex.soap.client;

import java.io.*;
import java.net.*;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

/**
 * Cliente SOAP simple usando HTTP directo (sin Spring WS)
 * Útil para testing rápido y debugging
 */
public class SimpleSetexClient {
    
    private static final String SOAP_ENDPOINT = "http://localhost:8080/setex/ws";
    private static final DateTimeFormatter DATE_FORMAT = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
    
    /**
     * Envía request SOAP de iniciarParqueo
     */
    public static String iniciarParqueo(String token, int plazaId, int zonaId, 
                                       String identificador, int tiempoParqueo, 
                                       int importeParqueo, String passwordCps) throws Exception {
        
        // Calcular fechas
        LocalDateTime ahora = LocalDateTime.now();
        LocalDateTime fin = ahora.plusMinutes(tiempoParqueo);
        String nroTransaccion = "TXN_" + System.currentTimeMillis();
        
        // Crear XML SOAP
        String soapXml = String.format("""
            <?xml version="1.0" encoding="UTF-8"?>
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                               SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                               xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <SOAP-ENV:Body>
            <m:iniciarParqueo xmlns:m="urn:setexwsdl">
            <token xsi:type="xsd:string">%s</token>
            <plazaId xsi:type="xsd:int">%d</plazaId>
            <zonaId xsi:type="xsd:int">%d</zonaId>
            <identificador xsi:type="xsd:string">%s</identificador>
            <tiempoParqueo xsi:type="xsd:int">%d</tiempoParqueo>
            <importeParqueo xsi:type="xsd:int">%d</importeParqueo>
            <passwordCps xsi:type="xsd:string">%s</passwordCps>
            <fechaInicioParqueo xsi:type="xsd:string">%s</fechaInicioParqueo>
            <fechaFinParqueo xsi:type="xsd:string">%s</fechaFinParqueo>
            <nroTransaccion xsi:type="xsd:string">%s</nroTransaccion>
            <fechaTransaccion xsi:type="xsd:string">%s</fechaTransaccion>
            </m:iniciarParqueo>
            </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
            """, 
            token, plazaId, zonaId, identificador, tiempoParqueo, importeParqueo, 
            passwordCps, ahora.format(DATE_FORMAT), fin.format(DATE_FORMAT), 
            nroTransaccion, ahora.format(DATE_FORMAT)
        );
        
        return sendSoapRequest(soapXml, "urn:setexwsdl#iniciarParqueo");
    }
    
    /**
     * Envía request SOAP de getVersion  
     */
    public static String getVersion() throws Exception {
        String soapXml = """
            <?xml version="1.0" encoding="UTF-8"?>
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                               xmlns:m="urn:setexwsdl">
            <SOAP-ENV:Body>
            <m:getVersion>
            <valor>test</valor>
            </m:getVersion>
            </SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
            """;
            
        return sendSoapRequest(soapXml, "urn:setexwsdl#getVersion");
    }
    
    /**
     * Método auxiliar para enviar requests HTTP SOAP
     */
    private static String sendSoapRequest(String soapXml, String soapAction) throws Exception {
        URL url = new URL(SOAP_ENDPOINT);
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        
        // Configurar request
        connection.setRequestMethod("POST");
        connection.setRequestProperty("Content-Type", "text/xml; charset=utf-8");
        connection.setRequestProperty("SOAPAction", "\"" + soapAction + "\"");
        connection.setDoOutput(true);
        
        // Enviar XML
        try (OutputStream os = connection.getOutputStream()) {
            byte[] input = soapXml.getBytes("utf-8");
            os.write(input, 0, input.length);
        }
        
        // Leer respuesta
        StringBuilder response = new StringBuilder();
        try (BufferedReader br = new BufferedReader(
                new InputStreamReader(connection.getInputStream(), "utf-8"))) {
            String responseLine;
            while ((responseLine = br.readLine()) != null) {
                response.append(responseLine.trim());
            }
        }
        
        return response.toString();
    }
    
    /**
     * Método principal para testing
     */
    public static void main(String[] args) {
        System.out.println("🧪 Simple SETEX SOAP Client\n");
        
        try {
            // Test 1: getVersion
            System.out.println("📋 Test 1: getVersion");
            String versionResponse = getVersion();
            System.out.println("Response: " + versionResponse);
            System.out.println();
            
            // Test 2: iniciarParqueo exitoso
            System.out.println("📋 Test 2: iniciarParqueo - Token válido");
            String parqueoResponse = iniciarParqueo(
                "dc2fec0f5f08fca379553cc7af20d556", // token válido
                2, 999, "1234567890123", 30, 50, "test123"
            );
            System.out.println("Response: " + parqueoResponse);
            System.out.println();
            
            // Test 3: iniciarParqueo con error
            System.out.println("📋 Test 3: iniciarParqueo - Token inválido");
            String errorResponse = iniciarParqueo(
                "token_invalido", 
                2, 999, "1234567890123", 30, 50, "test123"
            );
            System.out.println("Response: " + errorResponse);
            
        } catch (Exception e) {
            System.err.println("❌ Error: " + e.getMessage());
            e.printStackTrace();
        }
        
        System.out.println("\n✅ Testing completado!");
    }
}
```

---

## 🐍 **Cliente Python (equivalente al PHP actual)**

### **setex_soap_client.py**

```python
#!/usr/bin/env python3
"""
Cliente SOAP Python para servicio SETEX
Equivalente al cliente PHP actual
"""

import requests
from datetime import datetime, timedelta
import xml.etree.ElementTree as ET

class SetexSoapClient:
    def __init__(self, endpoint_url="http://localhost:8080/setex/ws"):
        self.endpoint_url = endpoint_url
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'text/xml; charset=utf-8'
        })
    
    def iniciar_parqueo(self, token, plaza_id, zona_id, identificador, 
                       tiempo_parqueo, importe_parqueo, password_cps):
        """Iniciar parqueo usando SOAP"""
        
        # Calcular fechas
        fecha_inicio = datetime.now()
        fecha_fin = fecha_inicio + timedelta(minutes=tiempo_parqueo)
        nro_transaccion = f"TXN_{int(datetime.now().timestamp())}"
        
        # Formato de fecha
        fecha_inicio_str = fecha_inicio.strftime('%Y-%m-%d %H:%M:%S')
        fecha_fin_str = fecha_fin.strftime('%Y-%m-%d %H:%M:%S')
        
        # Crear XML SOAP
        soap_xml = f'''<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema">
<SOAP-ENV:Body>
<m:iniciarParqueo xmlns:m="urn:setexwsdl">
<token xsi:type="xsd:string">{token}</token>
<plazaId xsi:type="xsd:int">{plaza_id}</plazaId>
<zonaId xsi:type="xsd:int">{zona_id}</zonaId>
<identificador xsi:type="xsd:string">{identificador}</identificador>
<tiempoParqueo xsi:type="xsd:int">{tiempo_parqueo}</tiempoParqueo>
<importeParqueo xsi:type="xsd:int">{importe_parqueo}</importeParqueo>
<passwordCps xsi:type="xsd:string">{password_cps}</passwordCps>
<fechaInicioParqueo xsi:type="xsd:string">{fecha_inicio_str}</fechaInicioParqueo>
<fechaFinParqueo xsi:type="xsd:string">{fecha_fin_str}</fechaFinParqueo>
<nroTransaccion xsi:type="xsd:string">{nro_transaccion}</nroTransaccion>
<fechaTransaccion xsi:type="xsd:string">{fecha_inicio_str}</fechaTransaccion>
</m:iniciarParqueo>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>'''
        
        return self._send_soap_request(soap_xml, "urn:setexwsdl#iniciarParqueo")
    
    def get_version(self):
        """Obtener versión del servicio"""
        soap_xml = '''<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:m="urn:setexwsdl">
<SOAP-ENV:Body>
<m:getVersion>
<valor>test</valor>
</m:getVersion>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>'''
        
        return self._send_soap_request(soap_xml, "urn:setexwsdl#getVersion")
    
    def _send_soap_request(self, soap_xml, soap_action):
        """Enviar request SOAP"""
        headers = {
            'SOAPAction': f'"{soap_action}"'
        }
        
        response = self.session.post(
            self.endpoint_url,
            data=soap_xml,
            headers=headers
        )
        
        return {
            'status_code': response.status_code,
            'headers': dict(response.headers),
            'content': response.text
        }
    
    def parse_codigo_respuesta(self, soap_response):
        """Extraer código de respuesta del XML"""
        try:
            root = ET.fromstring(soap_response['content'])
            
            # Buscar codigoRespuesta en la respuesta
            for elem in root.iter():
                if 'codigoRespuesta' in elem.tag:
                    return int(elem.text) if elem.text.isdigit() else elem.text
            
            return None
        except Exception as e:
            print(f"Error parsing response: {e}")
            return None

def main():
    """Función principal de testing"""
    print("🧪 Python SETEX SOAP Client\n")
    
    client = SetexSoapClient()
    
    try:
        # Test 1: getVersion
        print("📋 Test 1: getVersion")
        version_response = client.get_version()
        print(f"Status: {version_response['status_code']}")
        print(f"Response: {version_response['content'][:200]}...")
        version = client.parse_codigo_respuesta(version_response)
        print(f"Version: {version}")
        print()
        
        # Test 2: iniciarParqueo exitoso
        print("📋 Test 2: iniciarParqueo - Token válido")
        parqueo_response = client.iniciar_parqueo(
            token="dc2fec0f5f08fca379553cc7af20d556",
            plaza_id=2,
            zona_id=999,
            identificador="1234567890123",
            tiempo_parqueo=30,
            importe_parqueo=50,
            password_cps="test123"
        )
        print(f"Status: {parqueo_response['status_code']}")
        codigo = client.parse_codigo_respuesta(parqueo_response)
        print(f"Código respuesta: {codigo}")
        if codigo == 6:
            print("🎉 Parqueo iniciado exitosamente!")
        print()
        
        # Test 3: iniciarParqueo con error
        print("📋 Test 3: iniciarParqueo - Token inválido")
        error_response = client.iniciar_parqueo(
            token="token_invalido",
            plaza_id=2,
            zona_id=999,
            identificador="1234567890123",
            tiempo_parqueo=30,
            importe_parqueo=50,
            password_cps="test123"
        )
        codigo_error = client.parse_codigo_respuesta(error_response)
        print(f"Código respuesta: {codigo_error}")
        if codigo_error == 52:
            print("🔒 Error de token - como esperado")
        
    except Exception as e:
        print(f"❌ Error: {e}")
    
    print("\n✅ Testing completado!")

if __name__ == "__main__":
    main()
```

---

## 📱 **Cliente cURL (Línea de comandos)**

### **test_setex.sh**

```bash
#!/bin/bash

# 🧪 Script de testing SETEX SOAP usando cURL

ENDPOINT="http://localhost:8080/setex/ws"
TOKEN_VALIDO="dc2fec0f5f08fca379553cc7af20d556"

echo "🚀 Testing SETEX SOAP Service con cURL"
echo "======================================"
echo

# Test 1: getVersion
echo "📋 Test 1: getVersion"
curl -X POST "$ENDPOINT" \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: \"urn:setexwsdl#getVersion\"" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:m="urn:setexwsdl">
<SOAP-ENV:Body>
<m:getVersion>
<valor>test</valor>
</m:getVersion>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>' \
  --write-out "\n📊 HTTP: %{http_code}\n"

echo
echo "----------------------------------------"
echo

# Test 2: iniciarParqueo exitoso
echo "📋 Test 2: iniciarParqueo - Token válido"
curl -X POST "$ENDPOINT" \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: \"urn:setexwsdl#iniciarParqueo\"" \
  -d "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" 
                   SOAP-ENV:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\"
                   xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
                   xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
<SOAP-ENV:Body>
<m:iniciarParqueo xmlns:m=\"urn:setexwsdl\">
<token xsi:type=\"xsd:string\">$TOKEN_VALIDO</token>
<plazaId xsi:type=\"xsd:int\">2</plazaId>
<zonaId xsi:type=\"xsd:int\">999</zonaId>
<identificador xsi:type=\"xsd:string\">1234567890123</identificador>
<tiempoParqueo xsi:type=\"xsd:int\">30</tiempoParqueo>
<importeParqueo xsi:type=\"xsd:int\">50</importeParqueo>
<passwordCps xsi:type=\"xsd:string\">test123</passwordCps>
<fechaInicioParqueo xsi:type=\"xsd:string\">$(date '+%Y-%m-%d %H:%M:%S')</fechaInicioParqueo>
<fechaFinParqueo xsi:type=\"xsd:string\">$(date -d '+30 minutes' '+%Y-%m-%d %H:%M:%S')</fechaFinParqueo>
<nroTransaccion xsi:type=\"xsd:string\">TXN_$(date +%s)</nroTransaccion>
<fechaTransaccion xsi:type=\"xsd:string\">$(date '+%Y-%m-%d %H:%M:%S')</fechaTransaccion>
</m:iniciarParqueo>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>" \
  --write-out "\n📊 HTTP: %{http_code}\n"

echo
echo "----------------------------------------"
echo

# Test 3: iniciarParqueo con token inválido
echo "📋 Test 3: iniciarParqueo - Token inválido"
curl -X POST "$ENDPOINT" \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: \"urn:setexwsdl#iniciarParqueo\"" \
  -d "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" 
                   SOAP-ENV:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\"
                   xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
                   xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
<SOAP-ENV:Body>
<m:iniciarParqueo xmlns:m=\"urn:setexwsdl\">
<token xsi:type=\"xsd:string\">token_invalido</token>
<plazaId xsi:type=\"xsd:int\">2</plazaId>
<zonaId xsi:type=\"xsd:int\">999</zonaId>
<identificador xsi:type=\"xsd:string\">1234567890123</identificador>
<tiempoParqueo xsi:type=\"xsd:int\">30</tiempoParqueo>
<importeParqueo xsi:type=\"xsd:int\">50</importeParqueo>
<passwordCps xsi:type=\"xsd:string\">test123</passwordCps>
<fechaInicioParqueo xsi:type=\"xsd:string\">$(date '+%Y-%m-%d %H:%M:%S')</fechaInicioParqueo>
<fechaFinParqueo xsi:type=\"xsd:string\">$(date -d '+30 minutes' '+%Y-%m-%d %H:%M:%S')</fechaFinParqueo>
<nroTransaccion xsi:type=\"xsd:string\">TXN_$(date +%s)</nroTransaccion>
<fechaTransaccion xsi:type=\"xsd:string\">$(date '+%Y-%m-%d %H:%M:%S')</fechaTransaccion>
</m:iniciarParqueo>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>" \
  --write-out "\n📊 HTTP: %{http_code}\n"

echo
echo "✅ Testing completado!"
```

### **Ejecutar el script:**

```bash
chmod +x test_setex.sh
./test_setex.sh
```

---

## 🎯 **Uso de los Clientes**

### **Para Desarrollo:**
1. **Java Client:** Testing automático en CI/CD
2. **Python Client:** Scripts de integración y monitoreo  
3. **cURL Script:** Testing rápido y debugging
4. **Simple Java Client:** Testing sin dependencias Spring

### **Para Testing de Compatibilidad:**
- Usar el **mismo XML** que el servicio PHP actual
- Validar **mismas respuestas** de códigos de error
- Verificar **performance** equivalente o mejor

¿Te ayudo con algún cliente específico o necesitas alguna funcionalidad adicional? 🚀