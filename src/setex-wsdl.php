<?php
// SETEX NATIVE SOAP Web Service - Migrado de nuSOAP a SOAP nativo
// Compatible con servicio original - MISMA URL, MEJOR RENDIMIENTO
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

include_once('setex-config.php');

// Log de inicio - MÉTODO NATIVO
file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
    "NATIVE SOAP - Servicio iniciado (MISMA URL) - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

try {
    // Cargar clase de servicio
    require_once("servicio.class.php");
    
    file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
        "NATIVE SOAP - servicio.class.php cargado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Log con watchDog si está disponible
    if (function_exists('watchDog::logInfo')) {
        watchDog::logInfo('NATIVE SOAP Service iniciado', [
            'migration' => 'nuSOAP -> PHP SOAP nativo',
            'php_version' => PHP_VERSION,
            'soap_enabled' => extension_loaded('soap') ? 'YES' : 'NO',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
        ], 'native_soap_service');
    }

    // Verificar si la extensión SOAP está disponible
    if (!extension_loaded('soap')) {
        throw new Exception('Extensión PHP SOAP no está disponible. Por favor instalar php-soap');
    }

    // WSDL inline compatible con el original
    $wsdl_content = '<?xml version="1.0" encoding="UTF-8"?>
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/" 
             xmlns:tns="urn:setexwsdl" 
             xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" 
             xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
             targetNamespace="urn:setexwsdl" 
             elementFormDefault="qualified">

    <!-- Types - Compatible con nuSOAP original -->
    <types>
        <xsd:schema targetNamespace="urn:setexwsdl">
            <xsd:complexType name="codigoRespuestaComplex">
                <xsd:all>
                    <xsd:element name="codigoRespuesta" type="xsd:int"/>
                </xsd:all>
            </xsd:complexType>
            <xsd:complexType name="codigoRespuestaStringComplex">
                <xsd:all>
                    <xsd:element name="codigoRespuesta" type="xsd:string"/>
                </xsd:all>
            </xsd:complexType>
        </xsd:schema>
    </types>

    <!-- Messages - MISMA ESTRUCTURA QUE ANTES -->
    <message name="getVersionRequest">
        <part name="valor" type="xsd:string"/>
    </message>
    <message name="getVersionResponse">
        <part name="getVersionReturn" type="tns:codigoRespuestaStringComplex"/>
    </message>

    <message name="iniciarParqueoRequest">
        <part name="token" type="xsd:string"/>
        <part name="plazaId" type="xsd:int"/>
        <part name="zonaId" type="xsd:int"/>
        <part name="identificador" type="xsd:string"/>
        <part name="tiempoParqueo" type="xsd:int"/>
        <part name="importeParqueo" type="xsd:int"/>
        <part name="passwordCps" type="xsd:string"/>
        <part name="fechaInicioParqueo" type="xsd:string"/>
        <part name="fechaFinParqueo" type="xsd:string"/>
        <part name="nroTransaccion" type="xsd:string"/>
        <part name="fechaTransaccion" type="xsd:string"/>
    </message>
    <message name="iniciarParqueoResponse">
        <part name="iniciarParqueoReturn" type="tns:codigoRespuestaComplex"/>
    </message>

    <!-- Port Type - MISMO CONTRATO -->
    <portType name="SetexPortType">
        <operation name="getVersion">
            <input message="tns:getVersionRequest"/>
            <output message="tns:getVersionResponse"/>
        </operation>
        <operation name="iniciarParqueo">
            <input message="tns:iniciarParqueoRequest"/>
            <output message="tns:iniciarParqueoResponse"/>
        </operation>
    </portType>

    <!-- Binding - Compatible RPC/encoded como nuSOAP -->
    <binding name="SetexBinding" type="tns:SetexPortType">
        <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
        <operation name="getVersion">
            <soap:operation soapAction="urn:setexwsdl#getVersion"/>
            <input><soap:body use="encoded" namespace="urn:setexwsdl" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></input>
            <output><soap:body use="encoded" namespace="urn:setexwsdl" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></output>
        </operation>
        <operation name="iniciarParqueo">
            <soap:operation soapAction="urn:setexwsdl#iniciarParqueo"/>
            <input><soap:body use="encoded" namespace="urn:setexwsdl" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></input>
            <output><soap:body use="encoded" namespace="urn:setexwsdl" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></output>
        </operation>
    </binding>

    <!-- Service - MISMA URL -->
    <service name="SETEX">
        <port name="SetexPort" binding="tns:SetexBinding">
            <soap:address location="http://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/') . '"/>
        </port>
    </service>

</definitions>';

    // Si se solicita WSDL, devolverlo
    if (isset($_GET['wsdl'])) {
        header('Content-Type: text/xml; charset=utf-8');
        echo $wsdl_content;
        exit;
    }

    // Crear servidor SOAP nativo CON compatibilidad nuSOAP
    $server = new SoapServer(null, [
        'location' => 'http://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'),
        'uri' => 'urn:setexwsdl',
        'encoding' => 'UTF-8',
        'soap_version' => SOAP_1_1,
        'style' => SOAP_RPC,
        'use' => SOAP_ENCODED
    ]);

    file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
        "NATIVE SOAP - SoapServer creado (RPC/encoded compatible) - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Clase wrapper - MISMA LÓGICA DE NEGOCIO
    class SetexSoapService {
        
        public function getVersion($valor) {
            file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
                "NATIVE SOAP - getVersion llamado con valor: $valor - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
                
            try {
                // getVersion es una función independiente, no método de clase
                $resultado = getVersion();
                
                file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
                    "NATIVE SOAP - getVersion resultado: " . print_r($resultado, true) . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
                
                return $resultado;
            } catch (Exception $e) {
                file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
                    "NATIVE SOAP - getVersion ERROR: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
                return ['codigoRespuesta' => 'ERROR: ' . $e->getMessage()];
            }
        }

        public function iniciarParqueo($token, $plazaId, $zonaId, $identificador, $tiempoParqueo, 
                                     $importeParqueo, $passwordCps, $fechaInicioParqueo, 
                                     $fechaFinParqueo, $nroTransaccion, $fechaTransaccion) {
            
            file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
                "NATIVE SOAP - iniciarParqueo llamado - Token: $token, PlazaId: $plazaId - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
                
            try {
                // iniciarParqueo es una función independiente, no método de clase
                $resultado = iniciarParqueo(
                    $token, $plazaId, $zonaId, $identificador, $tiempoParqueo,
                    $importeParqueo, $passwordCps, $fechaInicioParqueo, 
                    $fechaFinParqueo, $nroTransaccion, $fechaTransaccion
                );
                
                file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
                    "NATIVE SOAP - iniciarParqueo resultado: " . print_r($resultado, true) . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
                
                return $resultado;
            } catch (Exception $e) {
                file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
                    "NATIVE SOAP - iniciarParqueo ERROR: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
                return ['codigoRespuesta' => 51]; // Mismo código de error que antes
            }
        }
    }

    // Registrar la clase en el servidor SOAP
    $server->setClass('SetexSoapService');

    file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
        "NATIVE SOAP - Clase registrada, procesando request - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Capturar XML de entrada para debugging - MISMO FORMATO
    $rawPostData = file_get_contents('php://input');
    if (empty($rawPostData) && isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        $rawPostData = $GLOBALS['HTTP_RAW_POST_DATA'];
    }
    
    if (!empty($rawPostData)) {
        // Logs compatibles con formato anterior
        file_put_contents('/var/www/html/serviceSetex/logs/raw_xml_debug_' . date('Y-m-d') . '.txt', 
            "[" . date('Y-m-d H:i:s') . "] NATIVE SOAP RAW XML:\n" . $rawPostData . "\n\n", FILE_APPEND);
            
        // Log adicional para debugging nativo
        file_put_contents('/var/www/html/serviceSetex/logs/native_soap_raw_' . date('Y-m-d') . '.txt', 
            "[" . date('Y-m-d H:i:s') . "] MIGRATED SOAP RAW XML:\n" . $rawPostData . "\n\n", FILE_APPEND);
    }

    // Log de debugging igual que antes
    if (function_exists('watchDog::logDebug') && !empty($rawPostData)) {
        @watchDog::logDebug('NATIVE SOAP Request recibido', [
            'migration_status' => 'nuSOAP -> PHP SOAP nativo',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'raw_data_length' => strlen($rawPostData),
            'raw_data_sample' => substr($rawPostData, 0, 200)
        ], 'native_soap_service');
    }

    file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
        "NATIVE SOAP - Iniciando server->handle() - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Procesar la request SOAP
    $server->handle($rawPostData);

    file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
        "NATIVE SOAP - Request procesado exitosamente - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

} catch (Exception $e) {
    // Manejo de errores compatible con nuSOAP
    if (function_exists('watchDog::logError')) {
        watchDog::logError('Error crítico en NATIVE SOAP Service', [
            'migration_note' => 'Error durante migración nuSOAP -> SOAP nativo',
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ], 'native_soap_service');
    }
    
    file_put_contents('/var/www/html/serviceSetex/logs/native_soap_debug.txt', 
        "NATIVE SOAP - ERROR CRÍTICO: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Responder con error SOAP válido - MISMO FORMATO
    header('Content-Type: text/xml; charset=utf-8');
    http_response_code(500);
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
    echo '<SOAP-ENV:Body>';
    echo '<SOAP-ENV:Fault>';
    echo '<faultcode>Server</faultcode>';
    echo '<faultstring>Error interno del servidor</faultstring>';
    echo '</SOAP-ENV:Fault>';
    echo '</SOAP-ENV:Body>';
    echo '</SOAP-ENV:Envelope>';
    
    exit(1);
}
?>