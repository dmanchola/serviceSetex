<?php
// SETEX NATIVE SOAP Web Service - Migrado de nuSOAP a SOAP nativo
// Compatible con servicio original - MISMA URL, MEJOR RENDIMIENTO
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

include_once('setex-config.php');

define('SOAP_LOGGING', SetexEnvLoader::getBool('SETEX_LOG_ENABLED', true));
define('SOAP_DEBUG_LOG', SOAP_LOGGING ? dirname(__DIR__) . '/logs/native_soap_debug.txt' : '/dev/null');

try {
    require_once("servicio.class.php");

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
            <soap:address location="http://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . strtok($_SERVER['REQUEST_URI'] ?? '/', '?') . '"/>
        </port>
    </service>

</definitions>';

    // sys_get_temp_dir() is always writable by the web server user
    $wsdlFile = sys_get_temp_dir() . '/setex_' . md5($_SERVER['SERVER_NAME'] ?? 'default') . '.wsdl';
    if (!file_exists($wsdlFile) || filemtime($wsdlFile) < time() - 3600) {
        file_put_contents($wsdlFile, $wsdl_content);
    }

    // Si se solicita WSDL, devolverlo
    if (isset($_GET['wsdl']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        header('Content-Type: text/xml; charset=utf-8');
        echo $wsdl_content;
        exit;
    }

    // Responder a GET sin body (acceso desde navegador)
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<service><status>online</status><version>' . Servicio::versionId . '</version>';
        echo '<wsdl>' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['SERVER_NAME'] ?? '') . ($_SERVER['PHP_SELF'] ?? '') . '?wsdl</wsdl></service>';
        exit;
    }

    $server = new SoapServer($wsdlFile, [
        'encoding'   => 'UTF-8',
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_MEMORY,
    ]);

    // Clase wrapper - MISMA LÓGICA DE NEGOCIO
    class SetexSoapService {
        
        public function getVersion($valor) {
            try {
                // getVersion es una función independiente, no método de clase
                return getVersion();
            } catch (Exception $e) {
                @file_put_contents(SOAP_DEBUG_LOG, "[" . date('Y-m-d H:i:s') . "] getVersion ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                return ['codigoRespuesta' => 'ERROR: ' . $e->getMessage()];
            }
        }

        public function iniciarParqueo($token, $plazaId, $zonaId, $identificador, $tiempoParqueo,
                                     $importeParqueo, $passwordCps, $fechaInicioParqueo,
                                     $fechaFinParqueo, $nroTransaccion, $fechaTransaccion) {

            try {
                $r = iniciarParqueo(
                    $token, $plazaId, $zonaId, $identificador, $tiempoParqueo,
                    $importeParqueo, $passwordCps, $fechaInicioParqueo,
                    $fechaFinParqueo, $nroTransaccion, $fechaTransaccion
                );

                $codigo = is_object($r) ? $r->codigoRespuesta : (is_array($r) ? $r['codigoRespuesta'] : $r);

                $out = new stdClass();
                $out->codigoRespuesta = (int)$codigo;
                return $out;

            } catch (Throwable $e) {
                @file_put_contents(SOAP_DEBUG_LOG, "[" . date('Y-m-d H:i:s') . "] iniciarParqueo ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                error_log('[setex] iniciarParqueo: ' . $e->getMessage());

                $out = new stdClass();
                $out->codigoRespuesta = 53;
                return $out;
            }
        }
    }

    // Registrar la clase en el servidor SOAP
    $server->setClass('SetexSoapService');

    $rawPostData = file_get_contents('php://input');
    if (empty($rawPostData) && isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        $rawPostData = $GLOBALS['HTTP_RAW_POST_DATA'];
    }

    // getVersion en V1 NuSOAP usaba <return xsi:type="SOAP-ENC:Struct"> por registro incorrecto
    // iniciarParqueo en V1 NuSOAP usaba prefijo tns: para el tipo (urn:setexwsdl)
    ob_start();
    $server->handle($rawPostData);
    $soapOutput = ob_get_clean();
    $soapOutput = preg_replace('/<getVersionReturn[^>]*>/', '<return xsi:type="SOAP-ENC:Struct">', $soapOutput);
    $soapOutput = str_replace('</getVersionReturn>', '</return>', $soapOutput);
    $soapOutput = str_replace('xmlns:ns1="urn:setexwsdl"', 'xmlns:tns="urn:setexwsdl" xmlns:ns1="urn:setexwsdl"', $soapOutput);
    $soapOutput = str_replace('xsi:type="ns1:codigoRespuesta', 'xsi:type="tns:codigoRespuesta', $soapOutput);
    echo $soapOutput;

} catch (Exception $e) {
    @file_put_contents(SOAP_DEBUG_LOG, "[" . date('Y-m-d H:i:s') . "] ERROR CRÍTICO: " . $e->getMessage() . "\n", FILE_APPEND);
    
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