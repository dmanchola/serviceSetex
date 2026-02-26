<?php
// SETEX SOAP Web Service - Compatible con servicio original
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); // Suprimir warnings deprecated y notices
ini_set('display_errors', '0'); // CRÍTICO: No mostrar errores en el XML SOAP

include_once('setex-config.php');

// DEBUG: Verificar si config se cargó - SIN DEPENDENCIAS EXTERNAS
file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
    "PASO 1: Config cargada - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Cargar nuSOAP con manejo de errores
try {
    file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 2: Intentando cargar nuSOAP - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
	require_once(LIBSPATH . 'nusoap/lib/nusoap.php');
	
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 2 OK: nuSOAP cargado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 2 ERROR: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
	die("Error cargando nuSOAP: " . $e->getMessage());
}

// Cargar servicio.class.php con manejo de errores  
try {
    file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 3: Intentando cargar servicio.class.php - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
	require_once("servicio.class.php");
	
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 3 OK: servicio.class.php cargado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 3 ERROR: " . $e->getMessage() . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
	die("Error cargando servicio.class.php: " . $e->getMessage());
}

// Log de inicio del servicio
if (function_exists('watchDog::logInfo')) {
	watchDog::logInfo('SOAP Service iniciado', [
		'php_version' => PHP_VERSION,
		'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
		'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
	], 'soap_service');
}

try {
	// Se instancia el servidor con manejo de errores
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 4: Creando servidor nuSOAP - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
	$server = new nusoap_server();
	
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 4 OK: Servidor nuSOAP creado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
	
	$server->configureWSDL('SETEX', 'urn:setexwsdl');
	
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 5 OK: WSDL configurado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

	// Configuración para PHP 8
	$server->soap_defencoding = 'UTF-8';
	$server->debug_flag = false; // CRÍTICO: Debug OFF para evitar output en XML

	$server->wsdl->addComplexType('codigoRespuestaComplex',
			'complexType',
			'struct',
			'all',
			'',
			array('codigoRespuesta' => array('name' => 'codigoRespuesta', 'type' => 'xsd:int'))
	);

	$server->wsdl->addComplexType('codigoRespuestaStringComplex',
			'complexType',
			'struct',
			'all',
			'',
			array('codigoRespuesta' => array('name' => 'codigoRespuesta', 'type' => 'xsd:string'))
	);

	//Metodos para Parquimetro
	$server->register('iniciarParqueo',
			array('token' => 'xsd:string',
					'plazaId' => 'xsd:int',
					'zonaId' => 'xsd:int',
					'identificador' => 'xsd:string',
					'tiempoParqueo' => 'xsd:int',
					'importeParqueo' => 'xsd:int',
					'password' => 'xsd:string',
					'fechaInicioParqueo' => 'xsd:string',
					'fechaFinParqueo' => 'xsd:string',
					'nroTransaccion' => 'xsd:string',
					'fechaTransaccion' => 'xsd:string'),
			array('iniciarParqueoReturn' => 'tns:codigoRespuestaComplex'),
			'urn:setexwsdl',
			'urn:setexwsdl#iniciarParqueo',
			'rpc',
			'encoded',
			'Iniciar Parqueo desde el Parquimetro'); //Inicio del Parqueo

	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 6 OK: Método iniciarParqueo registrado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

	//Manejo de Version
	$server->register('getVersion',
		 array('valor' => 'xsd:string'),
		array('getVersionReturn' => 'codigoRespuestaStringComplex'),
		'xsd:setexwsdl'); // Disponibilidad del WS

	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 7 OK: Todos los métodos registrados - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

	// Metodo de transferencia de datos compatible con PHP 8
	$rawPostData = file_get_contents('php://input');
	if (empty($rawPostData)) {
		$rawPostData = $GLOBALS['HTTP_RAW_POST_DATA'] ?? '';
	}
	
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 8: Request recibido - Length: " . strlen($rawPostData) . " - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
	
	// Log de request si necesario - SIN AFECTAR XML
	if (function_exists('watchDog::logDebug') && !empty($rawPostData)) {
		// Log discreto sin output
		@watchDog::logDebug('SOAP Request recibido', [
			'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
			'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
			'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
			'raw_data_length' => strlen($rawPostData),
			'raw_data_sample' => substr($rawPostData, 0, 200)
		], 'soap_service');
	}

	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 9: Iniciando server->service() - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

	$server->service($rawPostData);
	
	file_put_contents('/var/www/html/serviceSetex/logs/debug_simple.txt', 
        "PASO 10 OK: service() completado - " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

} catch (Exception $e) {
	// Manejo de errores para PHP 8
	if (function_exists('watchDog::logError')) {
		watchDog::logError('Error crítico en SOAP Service', [
			'error_message' => $e->getMessage(),
			'error_file' => $e->getFile(),
			'error_line' => $e->getLine(),
			'stack_trace' => $e->getTraceAsString()
		], 'soap_service');
	}
	
	// Responder con error SOAP válido
	header('Content-Type: text/xml; charset=utf-8');
	http_response_code(500);
	
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
	echo '<soap:Body>';
	echo '<soap:Fault>';
	echo '<faultcode>Server</faultcode>';
	echo '<faultstring>Error interno del servidor</faultstring>';
	echo '</soap:Fault>';
	echo '</soap:Body>';
	echo '</soap:Envelope>';
	
	exit(1);
}
?>