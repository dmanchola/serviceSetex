<?php
/**
 * SETEX SOAP Web Service - Versión migrada a extensión SOAP nativa de PHP
 * Compatible con servicio original pero usando SoapServer nativo
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0'); // CRÍTICO: No mostrar errores en el XML SOAP

include_once('setex-config.php');

// DEBUG: Verificar si config se cargó
file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
    "PASO 1: Config cargada - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Verificar que la extensión SOAP esté disponible
if (!extension_loaded('soap')) {
    die("Error: La extensión SOAP de PHP no está disponible");
}

file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
    "PASO 2: Extensión SOAP nativa disponible - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Cargar clase de servicio
try {
    require_once("servicio.class.php");
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "PASO 3: servicio.class.php cargado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "PASO 3 ERROR: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Error cargando servicio.class.php: " . $e->getMessage());
}

// Clase wrapper para SOAP nativo
class SetexSoapService {
    
    /**
     * Iniciar Parqueo - Compatible con cliente original
     */
    public function iniciarParqueo($token, $plazaId, $zonaId, $identificador, 
                                  $tiempoParqueo, $importeParqueo, $passwordCps,
                                  $fechaInicioParqueo, $fechaFinParqueo, $nroTransaccion, $fechaTransaccion) {
        
        $debugLog = RUTA_LOGS_WS . '/iniciarParqueo_native_debug_' . date('Y-m-d') . '.txt';
        file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] === FUNCIÓN iniciarParqueo NATIVA INICIADA ===\n", FILE_APPEND | LOCK_EX);
        
        // Log de parámetros recibidos
        $params = compact('token', 'plazaId', 'zonaId', 'identificador', 'tiempoParqueo', 
                         'importeParqueo', 'passwordCps', 'fechaInicioParqueo', 'fechaFinParqueo', 
                         'nroTransaccion', 'fechaTransaccion');
        
        file_put_contents($debugLog, "✅ Parámetros recibidos correctamente:\n", FILE_APPEND | LOCK_EX);
        foreach ($params as $key => $value) {
            file_put_contents($debugLog, "   - $key: '$value'\n", FILE_APPEND | LOCK_EX);
        }
        
        try {
            $servicio = new Servicio();
            $resultado = $servicio->iniciarParqueoSetex($params);
            
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] Resultado: " . json_encode($resultado) . "\n", FILE_APPEND | LOCK_EX);
            
            return $resultado;
            
        } catch (Exception $e) {
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] ❌ EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            
            $errorObj = new stdClass();
            $errorObj->codigoRespuesta = "ERROR: " . $e->getMessage();
            return $errorObj;
        }
    }
    
    /**
     * Obtener versión del servicio
     */
    public function getVersion($valor) {
        $debugLog = RUTA_LOGS_WS . '/getVersion_native_debug_' . date('Y-m-d') . '.txt';
        file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] getVersion nativo - valor: '$valor'\n", FILE_APPEND | LOCK_EX);
        
        try {
            $servicio = new Servicio();
            $resultado = $servicio->consultarDisponibilidad();
            
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] Resultado: " . json_encode($resultado) . "\n", FILE_APPEND | LOCK_EX);
            
            return $resultado;
            
        } catch (Exception $e) {
            file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] ❌ EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            
            $errorObj = new stdClass();
            $errorObj->codigoRespuesta = "ERROR: " . $e->getMessage();
            return $errorObj;
        }
    }
}

// Log de inicio del servicio
if (function_exists('watchDog::logInfo')) {
    watchDog::logInfo('SOAP Service Nativo iniciado', [
        'php_version' => PHP_VERSION,
        'soap_version' => phpversion('soap'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ], 'soap_native_service');
}

try {
    // Verificar si se solicita WSDL
    if (isset($_GET['wsdl'])) {
        // Servir WSDL
        header('Content-Type: text/xml; charset=utf-8');
        
        $wsdl = '<?xml version="1.0" encoding="UTF-8"?>
<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" 
                  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
                  xmlns:tns="urn:setexwsdl"
                  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                  targetNamespace="urn:setexwsdl">
                  
    <!-- Tipos complejos -->
    <wsdl:types>
        <xsd:schema targetNamespace="urn:setexwsdl">
            <xsd:complexType name="codigoRespuestaComplex">
                <xsd:sequence>
                    <xsd:element name="codigoRespuesta" type="xsd:int"/>
                </xsd:sequence>
            </xsd:complexType>
            <xsd:complexType name="codigoRespuestaStringComplex">
                <xsd:sequence>
                    <xsd:element name="codigoRespuesta" type="xsd:string"/>
                </xsd:sequence>
            </xsd:complexType>
        </xsd:schema>
    </wsdl:types>
    
    <!-- Mensajes -->
    <wsdl:message name="iniciarParqueoRequest">
        <wsdl:part name="token" type="xsd:string"/>
        <wsdl:part name="plazaId" type="xsd:int"/>
        <wsdl:part name="zonaId" type="xsd:int"/>
        <wsdl:part name="identificador" type="xsd:string"/>
        <wsdl:part name="tiempoParqueo" type="xsd:int"/>
        <wsdl:part name="importeParqueo" type="xsd:int"/>
        <wsdl:part name="passwordCps" type="xsd:string"/>
        <wsdl:part name="fechaInicioParqueo" type="xsd:string"/>
        <wsdl:part name="fechaFinParqueo" type="xsd:string"/>
        <wsdl:part name="nroTransaccion" type="xsd:string"/>
        <wsdl:part name="fechaTransaccion" type="xsd:string"/>
    </wsdl:message>
    
    <wsdl:message name="iniciarParqueoResponse">
        <wsdl:part name="iniciarParqueoReturn" type="tns:codigoRespuestaComplex"/>
    </wsdl:message>
    
    <wsdl:message name="getVersionRequest">
        <wsdl:part name="valor" type="xsd:string"/>
    </wsdl:message>
    
    <wsdl:message name="getVersionResponse">
        <wsdl:part name="getVersionReturn" type="tns:codigoRespuestaStringComplex"/>
    </wsdl:message>
    
    <!-- Port Type -->
    <wsdl:portType name="SetexPortType">
        <wsdl:operation name="iniciarParqueo">
            <wsdl:input message="tns:iniciarParqueoRequest"/>
            <wsdl:output message="tns:iniciarParqueoResponse"/>
        </wsdl:operation>
        <wsdl:operation name="getVersion">
            <wsdl:input message="tns:getVersionRequest"/>
            <wsdl:output message="tns:getVersionResponse"/>
        </wsdl:operation>
    </wsdl:portType>
    
    <!-- Binding -->
    <wsdl:binding name="SetexBinding" type="tns:SetexPortType">
        <soap:binding transport="http://schemas.xmlsoap.org/soap/http"/>
        
        <wsdl:operation name="iniciarParqueo">
            <soap:operation soapAction="urn:setexwsdl#iniciarParqueo"/>
            <wsdl:input><soap:body use="literal"/></wsdl:input>
            <wsdl:output><soap:body use="literal"/></wsdl:output>
        </wsdl:operation>
        
        <wsdl:operation name="getVersion">
            <soap:operation soapAction="urn:setexwsdl#getVersion"/>
            <wsdl:input><soap:body use="literal"/></wsdl:input>
            <wsdl:output><soap:body use="literal"/></wsdl:output>
        </wsdl:operation>
    </wsdl:binding>
    
    <!-- Service -->
    <wsdl:service name="SetexService">
        <wsdl:port name="SetexPort" binding="tns:SetexBinding">
            <soap:address location="' . $conf["rooturl"] . '/src/setex-native-soap.php"/>
        </wsdl:port>
    </wsdl:service>
    
</wsdl:definitions>';
        
        echo $wsdl;
        exit;
    }
    
    // Crear servidor SOAP nativo
    $options = array(
        'soap_version' => SOAP_1_1,
        'encoding' => 'UTF-8',
        'uri' => 'urn:setexwsdl'
    );
    
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "PASO 4: Creando SoapServer nativo - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    $server = new SoapServer(null, $options);
    
    // Registrar la clase de servicio
    $server->setClass('SetexSoapService');
    
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "PASO 5: SoapServer configurado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Capturar XML para debug
    $rawPostData = file_get_contents('php://input');
    file_put_contents(RUTA_LOGS_WS . '/raw_xml_native_debug_' . date('Y-m-d') . '.txt', 
        "[" . date('Y-m-d H:i:s') . "] RAW XML RECIBIDO (NATIVO):\n" . $rawPostData . "\n\n", FILE_APPEND);
    
    // Procesar peticiones SOAP
    $server->handle();
    
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "PASO 6: Petición procesada correctamente - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
} catch (SoapFault $f) {
    // Manejo específico de errores SOAP
    if (function_exists('watchDog::logError')) {
        watchDog::logError('SOAP Fault en servicio nativo', [
            'fault_code' => $f->faultcode,
            'fault_string' => $f->faultstring,
            'fault_detail' => $f->detail ?? 'N/A'
        ], 'soap_native_service');
    }
    
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "ERROR SOAP: " . $f->faultstring . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // El SoapServer manejará automáticamente la respuesta de error
    
} catch (Exception $e) {
    // Manejo de errores generales
    if (function_exists('watchDog::logError')) {
        watchDog::logError('Error crítico en SOAP Service nativo', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ], 'soap_native_service');
    }
    
    file_put_contents(RUTA_LOGS_WS . '/debug_native_soap.txt', 
        "ERROR GENERAL: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Responder con error SOAP válido
    header('Content-Type: text/xml; charset=utf-8');
    http_response_code(500);
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
    echo '<soap:Body>';
    echo '<soap:Fault>';
    echo '<faultcode>Server</faultcode>';
    echo '<faultstring>Error interno del servidor</faultstring>';
    echo '</soap:Fault>';
    echo '</soap:Body>';
    echo '</soap:Envelope>';
    
    exit(1);
}
?>