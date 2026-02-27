<?php
/**
 * Script de diagnóstico para problemas con nuSOAP
 * Ayuda a identificar problemas de parsing de parámetros XML
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('setex-config.php');

// Crear directorio de logs si no existe
$logsDir = dirname(__FILE__) . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

echo "<h2>🔍 Diagnóstico de nuSOAP - SETEX</h2>\n";
echo "<pre>\n";

echo "1. Verificando configuración...\n";
echo "   - PHP Version: " . PHP_VERSION . "\n";
echo "   - LIBSPATH: " . LIBSPATH . "\n";

// 2. Verificar nuSOAP
echo "\n2. Verificando nuSOAP...\n";
$nuSoapPath = LIBSPATH . 'nusoap/lib/nusoap.php';
if (file_exists($nuSoapPath)) {
    echo "   ✅ nuSOAP encontrado: $nuSoapPath\n";
    require_once($nuSoapPath);
    echo "   ✅ nuSOAP cargado correctamente\n";
} else {
    echo "   ❌ nuSOAP NO encontrado: $nuSoapPath\n";
    exit;
}

// 3. Probar creación de servidor
echo "\n3. Probando servidor nuSOAP...\n";
try {
    $server = new nusoap_server();
    echo "   ✅ Servidor nuSOAP creado\n";
    
    $server->configureWSDL('SETEX_TEST', 'urn:setexwsdl');
    echo "   ✅ WSDL configurado\n";
    
    $server->soap_defencoding = 'UTF-8';
    $server->decode_utf8 = false;
    $server->debug_flag = true; // Para testing habilitamos debug
    $server->charSet = 'UTF-8';
    echo "   ✅ Configuración aplicada\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 4. Registrar función de prueba
echo "\n4. Registrando función de prueba...\n";

function testFunction($param1 = "", $param2 = "") {
    $result = new stdClass();
    $result->mensaje = "Parámetros recibidos: P1='$param1', P2='$param2'";
    $result->timestamp = date('Y-m-d H:i:s');
    
    // Log para debug
    $debugLog = '../logs/test_function_debug.txt';
    file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] testFunction llamada\n", FILE_APPEND);
    file_put_contents($debugLog, "   - param1: '$param1'\n", FILE_APPEND);
    file_put_contents($debugLog, "   - param2: '$param2'\n", FILE_APPEND);
    file_put_contents($debugLog, "   - func_get_args(): " . json_encode(func_get_args()) . "\n", FILE_APPEND);
    
    return $result;
}

try {
    $server->register('testFunction',
        array('param1' => 'xsd:string', 'param2' => 'xsd:string'),
        array('testFunctionReturn' => 'xsd:string'),
        'urn:setexwsdl',
        'urn:setexwsdl#testFunction',
        'rpc',
        'encoded',
        'Función de prueba para diagnosticar parsing de parámetros'
    );
    echo "   ✅ Función de prueba registrada\n";
} catch (Exception $e) {
    echo "   ❌ Error registrando función: " . $e->getMessage() . "\n";
}

// 5. Información del WSDL
echo "\n5. Información del WSDL generado...\n";
echo "   - URL del WSDL: " . $conf["rooturl"] . "/src/test-nusoap-debug.php?wsdl\n";
echo "   - Namespace: urn:setexwsdl\n";

// 6. Mostrar WSDL si se solicita
if (isset($_GET['wsdl'])) {
    echo "\n6. Generando WSDL...\n";
    $server->service(file_get_contents('php://input'));
    exit;
}

// 7. Verificar si se está recibiendo una llamada SOAP
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    echo "\n6. Procesando llamada SOAP...\n";
    echo "   - Contenido recibido:\n";
    echo "     " . htmlspecialchars($rawInput) . "\n";
    
    // Procesar con nuSOAP
    $server->service($rawInput);
    exit;
}

echo "\n✅ Diagnóstico completado\n";
echo "\nPara probar:\n";
echo "1. Accede al WSDL: {$conf["rooturl"]}/src/test-nusoap-debug.php?wsdl\n";
echo "2. Usa un cliente SOAP para llamar a 'testFunction'\n";
echo "3. Revisa los logs en: {$logsDir}/test_function_debug.txt\n";
echo "\nEjemplo de XML para probar:\n";
echo htmlspecialchars('
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:testFunction>
         <param1>valor1</param1>
         <param2>valor2</param2>
      </urn:testFunction>
   </soap:Body>
</soap:Envelope>
');

echo "\n</pre>";
?>