<?php
// SETEX SOAP Web Service - PHP 8 / ext-soap con contrato WSDL estático
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

include_once('setex-config.php');
require_once('servicio.class.php');

define('SOAP_LOGGING', SetexEnvLoader::getBool('SETEX_LOG_ENABLED', true));
define('SOAP_DEBUG_LOG', SOAP_LOGGING ? dirname(__DIR__) . '/logs/native_soap_debug.txt' : '/dev/null');

// Paridad byte a byte con nuSOAP (V1). Poner en false solo cuando el
// consumidor confirme que tolera UTF-8 y el prefijo ns1.
define('SETEX_LEGACY_WIRE', SetexEnvLoader::getBool('SETEX_LEGACY_WIRE', true));

const SETEX_WSDL_PATH = __DIR__ . '/../schema/setex.wsdl';

function setexLog($msg) {
    if (SOAP_LOGGING) {
        @file_put_contents(SOAP_DEBUG_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
    }
}

function setexEndpoint() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $path   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return $scheme . '://' . $host . $path;
}

function setexWsdlContent() {
    $wsdl = file_get_contents(SETEX_WSDL_PATH);
    if ($wsdl === false) {
        throw new RuntimeException('No se pudo leer ' . SETEX_WSDL_PATH);
    }
    return str_replace('SETEX_ENDPOINT_PLACEHOLDER', htmlspecialchars(setexEndpoint(), ENT_QUOTES), $wsdl);
}

// Archivo temporal por endpoint Y por contenido: si cambia el WSDL cambia el
// nombre, de modo que el cache de ext-soap nunca sirve una version vieja.
function setexWsdlFile() {
    $content = setexWsdlContent();
    $file = sys_get_temp_dir() . '/setex_' . sha1($content) . '.wsdl';
    if (!file_exists($file)) {
        $tmp = $file . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, $content);
        rename($tmp, $file); // atomico: evita que otro proceso lea un WSDL truncado
    }
    return $file;
}

class SetexSoapService
{
    public function getVersion($valor = '')
    {
        try {
            $r = getVersion();
            $out = new stdClass();
            $out->codigoRespuesta = is_object($r) ? (string)$r->codigoRespuesta
                                  : (is_array($r) ? (string)$r['codigoRespuesta'] : (string)$r);
            return $out;
        } catch (Throwable $e) {
            setexLog('getVersion ERROR: ' . $e->getMessage());
            $out = new stdClass();
            $out->codigoRespuesta = 'ERROR: ' . $e->getMessage();
            return $out;
        }
    }

    public function iniciarParqueo($token, $plazaId, $zonaId, $identificador, $tiempoParqueo,
                                   $importeParqueo, $passwordCps, $fechaInicioParqueo,
                                   $fechaFinParqueo, $nroTransaccion, $fechaTransaccion)
    {
        try {
            $r = iniciarParqueo($token, $plazaId, $zonaId, $identificador, $tiempoParqueo,
                                $importeParqueo, $passwordCps, $fechaInicioParqueo,
                                $fechaFinParqueo, $nroTransaccion, $fechaTransaccion);

            $codigo = is_object($r) ? $r->codigoRespuesta : (is_array($r) ? $r['codigoRespuesta'] : $r);

            $out = new stdClass();
            $out->codigoRespuesta = (int)$codigo;
            return $out;
        } catch (Throwable $e) {
            setexLog('iniciarParqueo ERROR: ' . $e->getMessage());
            error_log('[setex] iniciarParqueo: ' . $e->getMessage());
            $out = new stdClass();
            $out->codigoRespuesta = 53;
            return $out;
        }
    }
}

// Normaliza la salida de ext-soap al formato exacto que emitia nuSOAP en V1.
function setexLegacyWire($xml)
{
    // nuSOAP declaraba tns y usaba ese prefijo en los xsi:type
    $xml = str_replace(
        'xmlns:ns1="urn:setexwsdl"',
        'xmlns:ns1="urn:setexwsdl" xmlns:tns="urn:setexwsdl"',
        $xml
    );
    $xml = str_replace('xsi:type="ns1:codigoRespuesta', 'xsi:type="tns:codigoRespuesta', $xml);

    // nuSOAP respondia en ISO-8859-1
    $xml = str_replace(
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<?xml version="1.0" encoding="ISO-8859-1"?>',
        $xml
    );
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($xml, 'ISO-8859-1', 'UTF-8');
    }
    $conv = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $xml);
    return $conv === false ? $xml : $conv;
}

try {
    if (!extension_loaded('soap')) {
        throw new RuntimeException('Extension php-soap no disponible');
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (isset($_GET['wsdl']) && $method === 'GET') {
        header('Content-Type: text/xml; charset=utf-8');
        echo setexWsdlContent();
        exit;
    }

    if ($method === 'GET') {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<service><status>online</status><version>' . Servicio::versionId . '</version>';
        echo '<wsdl>' . htmlspecialchars(setexEndpoint() . '?wsdl', ENT_QUOTES) . '</wsdl></service>';
        exit;
    }

    $rawPostData = file_get_contents('php://input');
    if (SOAP_LOGGING && $rawPostData !== '') {
        @file_put_contents(dirname(__DIR__) . '/logs/native_soap_raw_' . date('Y-m-d') . '.txt',
            '[' . date('Y-m-d H:i:s') . "] RAW XML:\n" . $rawPostData . "\n\n", FILE_APPEND);
    }

    $server = new SoapServer(setexWsdlFile(), [
        'soap_version' => SOAP_1_1,
        'encoding'     => 'UTF-8',
        'cache_wsdl'   => WSDL_CACHE_NONE,
        'send_errors'  => false,
    ]);
    $server->setClass('SetexSoapService');

    ob_start();
    $server->handle($rawPostData);
    $soapOutput = ob_get_clean();

    if (SETEX_LEGACY_WIRE) {
        $soapOutput = setexLegacyWire($soapOutput);
        header('Content-Type: text/xml; charset=ISO-8859-1');
    } else {
        header('Content-Type: text/xml; charset=utf-8');
    }

    header('Content-Length: ' . strlen($soapOutput));
    echo $soapOutput;

} catch (Throwable $e) {
    setexLog('ERROR CRITICO: ' . $e->getMessage());
    error_log('[setex] ' . $e->getMessage());

    header('Content-Type: text/xml; charset=utf-8');
    http_response_code(500);
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
    echo '<SOAP-ENV:Body><SOAP-ENV:Fault>';
    echo '<faultcode>SOAP-ENV:Server</faultcode>';
    echo '<faultstring>Error interno del servidor</faultstring>';
    echo '</SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>';
    exit(1);
}
