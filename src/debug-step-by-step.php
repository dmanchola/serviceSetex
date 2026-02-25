<?php
/**
 * SETEX - Debug paso a paso con logs detallados
 */

// Escribir log de inicio
$logDir = '../logs';
$today = date('Y-m-d');
$debugLog = $logDir . '/debug_step_by_step_' . $today . '.txt';

function writeDebugLog($message) {
    global $debugLog;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debugLog, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

writeDebugLog("=== INICIO DEBUG PASO A PASO ===");

try {
    writeDebugLog("PASO 1: Verificando directorio de logs");
    if (!is_dir($logDir)) {
        writeDebugLog("ERROR: Directorio de logs no existe: {$logDir}");
        die("Error: Directorio de logs no existe");
    }
    writeDebugLog("OK: Directorio de logs existe");

    writeDebugLog("PASO 2: Incluyendo setex-config.php");
    include_once('setex-config.php');
    writeDebugLog("OK: setex-config.php incluido");

    writeDebugLog("PASO 3: Verificando constantes definidas");
    if (!defined('LIBSPATH')) {
        writeDebugLog("ERROR: LIBSPATH no está definido");
        die("Error: LIBSPATH no definido");
    }
    writeDebugLog("OK: LIBSPATH = " . LIBSPATH);

    writeDebugLog("PASO 4: Verificando nuSOAP");
    $nusoap_path = LIBSPATH . 'nusoap/lib/nusoap.php';
    if (!file_exists($nusoap_path)) {
        writeDebugLog("ERROR: nuSOAP no encontrado en: {$nusoap_path}");
        die("Error: nuSOAP no encontrado");
    }
    writeDebugLog("OK: nuSOAP encontrado en: {$nusoap_path}");
    
    writeDebugLog("PASO 5: Incluyendo nuSOAP");
    require_once($nusoap_path);
    writeDebugLog("OK: nuSOAP incluido");

    writeDebugLog("PASO 6: Verificando servicio.class.php");
    if (!file_exists('servicio.class.php')) {
        writeDebugLog("ERROR: servicio.class.php no encontrado");  
        die("Error: servicio.class.php no encontrado");
    }
    writeDebugLog("OK: servicio.class.php encontrado");

    writeDebugLog("PASO 7: Incluyendo servicio.class.php");
    require_once("servicio.class.php");
    writeDebugLog("OK: servicio.class.php incluido");

    writeDebugLog("PASO 8: Creando servidor nuSOAP");
    $server = new nusoap_server();
    writeDebugLog("OK: Servidor nuSOAP creado");

    writeDebugLog("PASO 9: Configurando WSDL");
    $server->configureWSDL('SETEX', 'urn:setexwsdl');
    writeDebugLog("OK: WSDL configurado");

    writeDebugLog("PASO 10: Registrando método iniciarParqueo");
    $server->register('iniciarParqueo',
        array('token' => 'xsd:string',
              'plazaId' => 'xsd:int',
              'zonaId' => 'xsd:int',
              'identificador' => 'xsd:string',
              'tiempoParqueo' => 'xsd:int',
              'importeParqueo' => 'xsd:int',
              'passwordCps' => 'xsd:string',
              'fechaInicioParqueo' => 'xsd:string',
              'fechaFinParqueo' => 'xsd:string',
              'nroTransaccion' => 'xsd:string',
              'fechaTransaccion' => 'xsd:string'),
        array('iniciarParqueoReturn' => 'tns:codigoRespuestaComplex'),
        'urn:setexwsdl',
        'urn:setexwsdl#iniciarParqueo',
        'rpc',
        'encoded',
        'Iniciar Parqueo desde el Parquimetro');
    writeDebugLog("OK: Método iniciarParqueo registrado");

    writeDebugLog("PASO 11: Verificando método de request");
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    writeDebugLog("Método HTTP: {$method}");
    
    if ($method === 'POST') {
        writeDebugLog("PASO 12: Leyendo datos POST");
        $rawPostData = file_get_contents('php://input');
        $dataLength = strlen($rawPostData);
        writeDebugLog("Datos POST recibidos: {$dataLength} bytes");
        
        if ($dataLength > 0) {
            $sample = substr($rawPostData, 0, 100);
            writeDebugLog("Muestra de datos: {$sample}");
            
            if (strpos($rawPostData, 'iniciarParqueo') !== false) {
                writeDebugLog("✅ REQUEST SOAP VÁLIDO - Contiene iniciarParqueo");
            } else {
                writeDebugLog("⚠️ REQUEST NO CONTIENE iniciarParqueo");
            }
        }
        
        writeDebugLog("PASO 13: Ejecutando servicio SOAP");
        $server->service($rawPostData);
        writeDebugLog("OK: Servicio SOAP ejecutado");
    } else {
        writeDebugLog("PASO 12-13: GET Request - Mostrando WSDL");
        if (isset($_GET['wsdl'])) {
            writeDebugLog("Solicitando WSDL");
        }
        echo "GET request recibido - Debug completo. Ver logs.";
    }

    writeDebugLog("=== DEBUG COMPLETADO EXITOSAMENTE ===");

} catch (Exception $e) {
    writeDebugLog("❌ EXCEPCIÓN: " . $e->getMessage());
    writeDebugLog("❌ Archivo: " . $e->getFile());
    writeDebugLog("❌ Línea: " . $e->getLine());
    writeDebugLog("❌ Trace: " . $e->getTraceAsString());
    
    // Retornar error SOAP válido
    header('Content-Type: text/xml; charset=utf-8');
    http_response_code(500);
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
    echo '<soap:Body>';
    echo '<soap:Fault>';
    echo '<faultcode>Server</faultcode>';
    echo '<faultstring>Error en debug: ' . htmlspecialchars($e->getMessage()) . '</faultstring>';
    echo '</soap:Fault>';
    echo '</soap:Body>';
    echo '</soap:Envelope>';
}

writeDebugLog("=== FIN DEBUG ===");
?>