<?php
// SETEX SOAP Web Service - Compatible con servicio original
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); // Suprimir warnings deprecated y notices
ini_set('display_errors', '0'); // CRÍTICO: No mostrar errores en el XML SOAP

include_once('setex-config.php');

// DEBUG: Verificar si config se cargó
if (function_exists('watchDog::logInfo')) {
	watchDog::logInfo('PASO 1: Config cargada correctamente', [
		'LIBSPATH' => defined('LIBSPATH') ? LIBSPATH : 'NOT_DEFINED',
		'timestamp' => date('Y-m-d H:i:s')
	], 'soap_debug');
}

// Cargar nuSOAP con manejo de errores
try {
	require_once(LIBSPATH . 'nusoap/lib/nusoap.php');
	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 2: nuSOAP cargado correctamente', [
			'nusoap_class_exists' => class_exists('nusoap_server'),
			'nusoap_file_path' => LIBSPATH . 'nusoap/lib/nusoap.php'
		], 'soap_debug');
	}
} catch (Exception $e) {
	if (function_exists('watchDog::logError')) {
		watchDog::logError('ERROR: Falló carga de nuSOAP', [
			'error' => $e->getMessage(),
			'file_path' => LIBSPATH . 'nusoap/lib/nusoap.php'
		], 'soap_debug');
	}
	die("Error cargando nuSOAP: " . $e->getMessage());
}

// Cargar servicio.class.php con manejo de errores  
try {
	require_once("servicio.class.php");
	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 3: servicio.class.php cargado correctamente', [
			'servicio_class_exists' => class_exists('servicio'),
			'servicio_file_path' => __DIR__ . '/servicio.class.php'
		], 'soap_debug');
	}
} catch (Exception $e) {
	if (function_exists('watchDog::logError')) {
		watchDog::logError('ERROR: Falló carga de servicio.class.php', [
			'error' => $e->getMessage(),
			'file_path' => __DIR__ . '/servicio.class.php'
		], 'soap_debug');
	}
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
	$server = new nusoap_server();
	
	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 4: Servidor nuSOAP creado correctamente', [
			'server_class' => get_class($server),
			'server_created' => is_object($server)
		], 'soap_debug');
	}
	
	$server->configureWSDL('SETEX', 'urn:setexwsdl');
	
	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 5: WSDL configurado correctamente', [
			'wsdl_namespace' => 'urn:setexwsdl',
			'service_name' => 'SETEX'
		], 'soap_debug');
	}

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
			'Iniciar Parqueo desde el Parquimetro'); //Inicio del Parqueo

	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 6: Método iniciarParqueo registrado correctamente', [
			'method_name' => 'iniciarParqueo',
			'namespace' => 'urn:setexwsdl',
			'soap_action' => 'urn:setexwsdl#iniciarParqueo'
		], 'soap_debug');
	}

	//Manejo de Version
	$server->register('getVersion',
		 array('valor' => 'xsd:string'),
		array('getVersionReturn' => 'codigoRespuestaStringComplex'),
		'xsd:setexwsdl'); // Disponibilidad del WS

	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 7: Todos los métodos SOAP registrados', [
			'methods_registered' => ['iniciarParqueo', 'getVersion'],
			'server_ready' => true
		], 'soap_debug');
	}

	// Metodo de transferencia de datos compatible con PHP 8
	$rawPostData = file_get_contents('php://input');
	if (empty($rawPostData)) {
		$rawPostData = $GLOBALS['HTTP_RAW_POST_DATA'] ?? '';
	}
	
	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 8: Request recibido', [
			'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
			'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
			'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
			'raw_data_length' => strlen($rawPostData),
			'has_data' => !empty($rawPostData)
		], 'soap_debug');
	}
	
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

	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 9: Iniciando procesamiento SOAP', [
			'about_to_call' => 'server->service()',
			'timestamp' => date('Y-m-d H:i:s')
		], 'soap_debug');
	}

	$server->service($rawPostData);
	
	if (function_exists('watchDog::logInfo')) {
		watchDog::logInfo('PASO 10: Procesamiento SOAP completado', [
			'service_executed' => true,
			'timestamp' => date('Y-m-d H:i:s')
		], 'soap_debug');
	}

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