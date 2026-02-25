<?php
/**
 * Test rápido usando localhost para evitar problemas de red
 */

echo "<!DOCTYPE html><html><head><title>SETEX Localhost Test</title></head><body>";
echo "<h1>🔍 SETEX - Test Localhost (Sin timeouts)</h1>";

// Usar localhost en lugar de IP pública
$serviceUrl = "http://localhost/serviceSetex/src/setex-wsdl.php";

echo "<h2>🧪 Test POST directo usando LOCALHOST</h2>";

$soapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope 
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:urn="urn:setexwsdl">
    <soap:Header/>
    <soap:Body>
        <urn:getVersion>
            <valor>test</valor>
        </urn:getVersion>
    </soap:Body>
</soap:Envelope>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serviceUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/xml; charset=utf-8',
    'SOAPAction: "getVersion"',
    'Content-Length: ' . strlen($soapEnvelope),
    'Host: localhost'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($error) {
    echo "<p>❌ Error cURL con localhost: " . $error . "</p>";
} else {
    echo "<p>✅ POST localhost exitoso: Código {$httpCode}</p>";
    echo "<h4>📨 Respuesta del servidor:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h3>📊 Información de la conexión:</h3>";
echo "<pre>" . print_r($info, true) . "</pre>";

echo "<hr>";
echo "<h2>🎯 Resultado del diagnóstico:</h2>";
echo "<p><strong>PROBLEMA:</strong> El servidor no puede conectarse a sí mismo usando la IP pública.</p>";
echo "<p><strong>SOLUCIÓN:</strong> Usar Postman o cliente externo para probar el servicio.</p>";

echo "<h3>🚀 XML correcto para Postman:</h3>";
echo "<p><strong>URL:</strong> http://54.187.87.75/serviceSetex/src/setex-wsdl.php</p>";
echo "<p><strong>Method:</strong> POST</p>";
echo "<p><strong>Headers:</strong></p>";
echo "<ul>";
echo "<li>Content-Type: text/xml; charset=utf-8</li>";
echo "<li>SOAPAction: \"iniciarParqueo\"</li>";
echo "</ul>";

$correctXML = '<?xml version="1.0" encoding="UTF-8"?>
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
</soap:Envelope>';

echo "<textarea style='width:100%; height:400px;'>" . htmlspecialchars($correctXML) . "</textarea>";

echo "</body></html>";
?>