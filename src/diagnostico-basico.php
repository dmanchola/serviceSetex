<?php
/**
 * SETEX SOAP - Diagnóstico básico de transporte y conectividad
 */

echo "<!DOCTYPE html><html><head><title>SETEX Diagnóstico</title></head><body>";
echo "<h1>🔍 SETEX - Diagnóstico Paso a Paso</h1>";

echo "<h2>📋 1. Verificar extensiones PHP</h2>";

// Verificar extensiones críticas
$extensions = ['curl', 'soap', 'openssl', 'xml', 'libxml'];
foreach ($extensions as $ext) {
    echo "<p>";
    if (extension_loaded($ext)) {
        echo "✅ <strong>{$ext}</strong>: Habilitada";
        if ($ext === 'curl') {
            $version = curl_version();
            echo " (versión: " . $version['version'] . ")";
        }
    } else {
        echo "❌ <strong>{$ext}</strong>: NO DISPONIBLE - CRÍTICO";
    }
    echo "</p>";
}

echo "<h2>🌐 2. Verificar conectividad del servicio</h2>";

$serviceUrl = "http://54.187.87.75/serviceSetex/src/setex-wsdl.php";

// Test básico de conectividad HTTP
echo "<h3>📡 Test de conectividad básica</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serviceUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p>❌ Error cURL: " . $error . "</p>";
} else {
    echo "<p>✅ Conectividad HTTP: Código {$httpCode}</p>";
    if ($httpCode === 200) {
        echo "<p>✅ Respuesta del servicio exitosa</p>";
    }
}

// Test WSDL específico
echo "<h3>📋 Test de acceso al WSDL</h3>";
$wsdlUrl = $serviceUrl . "?wsdl";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $wsdlUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$wsdlResponse = curl_exec($ch);
$wsdlHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$wsdlError = curl_error($ch);
curl_close($ch);

if ($wsdlError) {
    echo "<p>❌ Error cURL en WSDL: " . $wsdlError . "</p>";
} else {
    echo "<p>✅ Acceso WSDL: Código {$wsdlHttpCode}</p>";
    if ($wsdlHttpCode === 200 && strpos($wsdlResponse, 'definitions') !== false) {
        echo "<p>✅ WSDL válido encontrado</p>";
        echo "<p><a href='{$wsdlUrl}' target='_blank'>Ver WSDL completo</a></p>";
    } else {
        echo "<p>❌ WSDL inválido o no encontrado</p>";
        echo "<p>Primeros 500 caracteres de la respuesta:</p>";
        echo "<pre>" . htmlspecialchars(substr($wsdlResponse, 0, 500)) . "</pre>";
    }
}

echo "<h2>🧪 3. Test de cliente SOAP simple</h2>";

try {
    // Verificar si podemos crear SoapClient nativo de PHP
    if (class_exists('SoapClient')) {
        echo "<p>✅ SoapClient nativo disponible</p>";
        
        $options = [
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 10,
        ];
        
        $client = new SoapClient($wsdlUrl, $options);
        echo "<p>✅ SoapClient nativo creado correctamente</p>";
        
        // Mostrar funciones disponibles
        $functions = $client->__getFunctions();
        echo "<h4>📋 Funciones disponibles:</h4>";
        echo "<ul>";
        foreach ($functions as $function) {
            echo "<li>" . htmlspecialchars($function) . "</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p>❌ SoapClient nativo NO disponible</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error al crear SoapClient nativo: " . $e->getMessage() . "</p>";
}

echo "<h2>📊 4. Información del sistema</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Sistema:</strong> " . php_uname() . "</p>";
echo "<p><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? 'Habilitado' : 'Deshabilitado') . "</p>";
echo "<p><strong>user_agent:</strong> " . ini_get('user_agent') . "</p>";

echo "<h2>🎯 5. Diagnóstico dirigido</h2>";

// Test directo al servicio sin nuSOAP
echo "<h3>📤 Test POST directo</h3>";

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
    'Content-Length: ' . strlen($soapEnvelope)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$directResponse = curl_exec($ch);
$directHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$directError = curl_error($ch);
curl_close($ch);

if ($directError) {
    echo "<p>❌ Error en POST directo: " . $directError . "</p>";
} else {
    echo "<p>✅ POST directo: Código {$directHttpCode}</p>";
    echo "<h4>📨 Respuesta del servidor:</h4>";
    echo "<pre>" . htmlspecialchars($directResponse) . "</pre>";
}

echo "</body></html>";
?>