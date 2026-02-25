<?php
/**
 * SETEX SOAP Service - Archivo de prueba y debugging
 * Usar para probar el servicio SOAP correctamente
 */

// Activar reporting de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../libs/nusoap/lib/nusoap.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>SETEX SOAP Debug Test</title></head><body>";
echo "<h1>🔍 SETEX SOAP Service - Test & Debug</h1>";

$serviceUrl = "http://54.187.87.75/serviceSetex/src/setex-wsdl.php";

try {
    echo "<h2>📡 1. Probando conexión al servicio</h2>";
    
    // Crear cliente SOAP
    $client = new nusoap_client($serviceUrl . '?wsdl', true);
    
    if ($client->getError()) {
        throw new Exception('Error al crear cliente SOAP: ' . $client->getError());
    }
    
    echo "<p>✅ Cliente SOAP creado correctamente</p>";
    
    // Mostrar WSDL
    echo "<h3>📋 WSDL del servicio:</h3>";
    echo "<p><a href='{$serviceUrl}?wsdl' target='_blank'>Ver WSDL completo</a></p>";
    
    echo "<h2>🧪 2. Probando método getVersion</h2>";
    
    // Probar getVersion primero
    $result = $client->call('getVersion', array('valor' => 'test'));
    
    if ($client->getError()) {
        echo "<p>❌ Error en getVersion: " . $client->getError() . "</p>";
    } else {
        echo "<p>✅ getVersion exitoso:</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
    
    echo "<h2>🚗 3. Probando método iniciarParqueo</h2>";
    
    // Parámetros de prueba
    $parametros = array(
        'token' => 'dc2fec0f5f08fca379553cc7af20d556',
        'plazaId' => 1,
        'zonaId' => 1,
        'identificador' => '1234567890123',
        'tiempoParqueo' => 60,
        'importeParqueo' => 100,
        'passwordCps' => 'test123',
        'fechaInicioParqueo' => '2026-02-25 21:30:00',
        'fechaFinParqueo' => '2026-02-25 22:30:00',
        'nroTransaccion' => 'TXN123456',
        'fechaTransaccion' => '2026-02-25 21:30:00'
    );
    
    echo "<h3>📤 Parámetros enviados:</h3>";
    echo "<pre>" . print_r($parametros, true) . "</pre>";
    
    // Ejecutar llamada
    $result = $client->call('iniciarParqueo', $parametros);
    
    if ($client->getError()) {
        echo "<p>❌ Error en iniciarParqueo: " . $client->getError() . "</p>";
    } else {
        echo "<p>✅ iniciarParqueo ejecutado:</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
    
    // Mostrar request y response XML
    echo "<h2>🔍 4. Debug información</h2>";
    
    echo "<h3>📤 Request XML enviado:</h3>";
    echo "<pre>" . htmlspecialchars($client->request) . "</pre>";
    
    echo "<h3>📨 Response XML recibido:</h3>";
    echo "<pre>" . htmlspecialchars($client->response) . "</pre>";
    
    echo "<h3>🐛 Debug info:</h3>";
    echo "<pre>" . htmlspecialchars($client->debug_str) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Error crítico: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Formato SOAP correcto para Postman:</h2>";
echo "<p><strong>URL:</strong> http://54.187.87.75/serviceSetex/src/setex-wsdl.php</p>";
echo "<p><strong>Method:</strong> POST</p>";
echo "<p><strong>Headers:</strong></p>";
echo "<ul>";
echo "<li>Content-Type: text/xml; charset=utf-8</li>";
echo "<li>SOAPAction: \"iniciarParqueo\"</li>";
echo "</ul>";

echo "<p><strong>Body (XML correcto):</strong></p>";
echo "<textarea style='width:100%; height:300px;'>";
echo htmlspecialchars('<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope 
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:urn="urn:setexwsdl">
    <soap:Header/>
    <soap:Body>
        <urn:iniciarParqueo>
            <token>dc2fec0f5f08fca379553cc7af20d556</token>
            <plazaId>1</plazaId>
            <zonaId>1</zonaId>
            <identificador>1234567890123</identificador>
            <tiempoParqueo>60</tiempoParqueo>
            <importeParqueo>100</importeParqueo>
            <passwordCps>test123</passwordCps>
            <fechaInicioParqueo>2026-02-25 21:30:00</fechaInicioParqueo>
            <fechaFinParqueo>2026-02-25 22:30:00</fechaFinParqueo>
            <nroTransaccion>TXN123456</nroTransaccion>
            <fechaTransaccion>2026-02-25 21:30:00</fechaTransaccion>
        </urn:iniciarParqueo>
    </soap:Body>
</soap:Envelope>');
echo "</textarea>";

echo "</body></html>";
?>