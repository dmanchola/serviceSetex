<?php
/**
 * Cliente de prueba para diagnosticar problemas con nuSOAP
 * Prueba tanto la función de test como el servicio real
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('setex-config.php');
require_once(LIBSPATH . 'nusoap/lib/nusoap.php');

echo "<h2>🧪 Cliente de Prueba - SETEX SOAP</h2>\n";
echo "<pre>\n";

// Configuración del servidor
$serverUrl = $conf["rooturl"] . "/src/test-nusoap-debug.php";
$realServerUrl = $conf["rooturl"] . "/src/setex-wsdl.php";

function testSoapCall($url, $method, $params, $description) {
    echo "\n--- $description ---\n";
    echo "URL: $url\n";
    echo "Método: $method\n";
    echo "Parámetros: " . json_encode($params) . "\n";
    
    try {
        $client = new nusoap_client($url . '?wsdl', true);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;
        
        // Verificar errores del cliente
        $error = $client->getError();
        if ($error) {
            echo "❌ Error creando cliente: $error\n";
            return false;
        }
        
        echo "✅ Cliente SOAP creado correctamente\n";
        
        // Realizar la llamada
        echo "📞 Realizando llamada...\n";
        $result = $client->call($method, $params);
        
        // Verificar errores de la llamada
        if ($client->fault) {
            echo "❌ Fault SOAP: " . print_r($result, true) . "\n";
        } else {
            $error = $client->getError();
            if ($error) {
                echo "❌ Error en llamada: $error\n";
            } else {
                echo "✅ Llamada exitosa!\n";
                echo "📋 Resultado: " . print_r($result, true) . "\n";
            }
        }
        
        // Mostrar información de debug
        echo "\n🔍 Debug Info:\n";
        echo "Request XML:\n" . htmlspecialchars($client->request) . "\n\n";
        echo "Response XML:\n" . htmlspecialchars($client->response) . "\n\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Excepción: " . $e->getMessage() . "\n";
        return false;
    }
}

// 1. Probar función de test
echo "=== PRUEBA 1: Función de Test ===\n";
testSoapCall($serverUrl, 'testFunction', array(
    'param1' => 'test_value_1',
    'param2' => 'test_value_2'
), 'Probando función de diagnóstico');

// 2. Probar getVersion en el servicio real
echo "\n=== PRUEBA 2: getVersion Real ===\n";
testSoapCall($realServerUrl, 'getVersion', array(
    'valor' => 'test'
), 'Probando getVersion del servicio real');

// 3. Probar iniciarParqueo con datos de prueba
echo "\n=== PRUEBA 3: iniciarParqueo Real ===\n";
testSoapCall($realServerUrl, 'iniciarParqueo', array(
    'token' => 'dc2fec0f5f08fca379553cc7af20d556',
    'plazaId' => 2,
    'zonaId' => 999,
    'identificador' => '1234567890123',
    'tiempoParqueo' => 30,
    'importeParqueo' => 50,
    'passwordCps' => 'test123',
    'fechaInicioParqueo' => date('Y-m-d H:i:s'),
    'fechaFinParqueo' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
    'nroTransaccion' => 'TEST_' . date('YmdHis'),
    'fechaTransaccion' => date('Y-m-d H:i:s')
), 'Probando iniciarParqueo con datos válidos');

echo "\n=== RESULTADO ===\n";
echo "✅ Pruebas completadas\n";
echo "📁 Revisa los logs generados en: " . dirname(__FILE__) . "/../logs/\n";
echo "   - *_debug*.txt para información detallada\n";
echo "   - raw_xml_debug_*.txt para XML crudo recibido\n";
echo "   - headers_debug_*.txt para headers HTTP\n";

echo "\n</pre>";
?>