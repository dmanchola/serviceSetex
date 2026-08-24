<?php
/**
 * Script de prueba del servicio SETEX
 * Uso: php test-service.php [url]
 * Ejemplo: php test-service.php http://54.187.87.75/serviceSetex/src/setex-wsdl.php
 */

$serviceUrl = $argv[1] ?? 'http://54.187.87.75/serviceSetex/src/setex-wsdl.php';

echo "=== SETEX Service Test ===\n";
echo "URL: $serviceUrl\n\n";

// ── 1. Verificar que el endpoint responde ─────────────────────────────────────
echo "[1] Verificando disponibilidad del endpoint...\n";
$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($serviceUrl . '?wsdl', false, $ctx);
if ($response === false) {
    echo "    FALLO: No se pudo conectar al servidor.\n";
    exit(1);
}
echo "    OK: Endpoint responde (" . strlen($response) . " bytes de WSDL)\n\n";

// ── Inicializar cliente SOAP ──────────────────────────────────────────────────
try {
    $client = new SoapClient(null, [
        'location'       => $serviceUrl,
        'uri'            => 'urn:setexwsdl',
        'style'          => SOAP_RPC,
        'use'            => SOAP_ENCODED,
        'soap_version'   => SOAP_1_1,
        'encoding'       => 'UTF-8',
        'exceptions'     => true,
        'connection_timeout' => 15,
        'trace'          => true,
    ]);
} catch (Exception $e) {
    echo "FALLO al crear SoapClient: " . $e->getMessage() . "\n";
    exit(1);
}

// ── 2. getVersion ─────────────────────────────────────────────────────────────
echo "[2] Probando getVersion...\n";
try {
    $result = $client->getVersion('');
    $version = $result->codigoRespuesta ?? $result ?? 'N/A';
    echo "    OK: versión = $version\n\n";
} catch (SoapFault $e) {
    echo "    FALLO: " . $e->getMessage() . "\n\n";
}

// ── 3. iniciarParqueo con datos válidos ───────────────────────────────────────
$nroTransaccion = 'TEST_' . date('YmdHis') . '_' . rand(100, 999);
$fechaInicio    = date('Y-m-d H:i:s');
$fechaFin       = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$params = [
    'token'              => 'dc2fec0f5f08fca379553cc7af20d556',
    'plazaId'            => 2,
    'zonaId'             => 100,
    'identificador'      => '9876543210987', // 13 dígitos
    'tiempoParqueo'      => 30,
    'importeParqueo'     => 340,
    'passwordCps'        => 'cps123',
    'fechaInicioParqueo' => $fechaInicio,
    'fechaFinParqueo'    => $fechaFin,
    'nroTransaccion'     => $nroTransaccion,
    'fechaTransaccion'   => $fechaInicio,
];

echo "[3] Probando iniciarParqueo (nroTransaccion: $nroTransaccion)...\n";
try {
    $result = $client->iniciarParqueo(...array_values($params));
    $codigo = $result->codigoRespuesta ?? 'N/A';
    echo "    codigoRespuesta = $codigo\n";
    echo ($codigo == 6) ? "    OK: Parqueo aprobado (6)\n\n" : "    REVISAR: código inesperado\n\n";
} catch (SoapFault $e) {
    echo "    FALLO: " . $e->getMessage() . "\n";
    echo "    Request XML:\n" . $client->__getLastRequest() . "\n\n";
}

// ── 4. Misma transacción de nuevo → debe retornar 6 sin duplicar ──────────────
echo "[4] Probando detección de duplicado (mismo nroTransaccion)...\n";
try {
    $result = $client->iniciarParqueo(...array_values($params));
    $codigo = $result->codigoRespuesta ?? 'N/A';
    echo "    codigoRespuesta = $codigo\n";
    echo ($codigo == 6) ? "    OK: Duplicado detectado correctamente, retornó 6\n\n" : "    REVISAR: código inesperado\n\n";
} catch (SoapFault $e) {
    echo "    FALLO: " . $e->getMessage() . "\n\n";
}

// ── 5. Token inválido → debe retornar 52 ─────────────────────────────────────
echo "[5] Probando token inválido (debe retornar 52)...\n";
$paramsInvalidToken = $params;
$paramsInvalidToken['token'] = 'token_invalido_12345';
$paramsInvalidToken['nroTransaccion'] = 'TEST_TOKEN_' . date('YmdHis');
try {
    $result = $client->iniciarParqueo(...array_values($paramsInvalidToken));
    $codigo = $result->codigoRespuesta ?? 'N/A';
    echo "    codigoRespuesta = $codigo\n";
    echo ($codigo == 52) ? "    OK: Token inválido rechazado (52)\n\n" : "    REVISAR: código inesperado\n\n";
} catch (SoapFault $e) {
    echo "    FALLO: " . $e->getMessage() . "\n\n";
}

// ── 6. Identificador con longitud incorrecta → debe retornar 57 ───────────────
echo "[6] Probando identificador inválido (debe retornar 57)...\n";
$paramsInvalidId = $params;
$paramsInvalidId['identificador'] = '123'; // menos de 13 dígitos
$paramsInvalidId['nroTransaccion'] = 'TEST_ID_' . date('YmdHis');
try {
    $result = $client->iniciarParqueo(...array_values($paramsInvalidId));
    $codigo = $result->codigoRespuesta ?? 'N/A';
    echo "    codigoRespuesta = $codigo\n";
    echo ($codigo == 57) ? "    OK: Identificador inválido rechazado (57)\n\n" : "    REVISAR: código inesperado\n\n";
} catch (SoapFault $e) {
    echo "    FALLO: " . $e->getMessage() . "\n\n";
}

echo "=== Pruebas completadas ===\n";
