<?php
// SETEX SOAP Web Service - Compatible con servicio original
error_reporting(E_ALL ^ E_DEPRECATED); // Suprimir warnings de nuSOAP legacy
ini_set('display_errors', '0'); // No mostrar errores en producción

include_once('setex-config.php');
require_once(LIBSPATH . 'nusoap/lib/nusoap.php');
require_once("servicio.class.php");

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
	$server->configureWSDL('SETEX', 'urn:setexwsdl');

	// Configuración para PHP 8
	$server->soap_defencoding = 'UTF-8';
	$server->debug_flag = false; // Desactivar debug en producción

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

	//Manejo de Version
	$server->register('getVersion',
		 array('valor' => 'xsd:string'),
		array('getVersionReturn' => 'codigoRespuestaStringComplex'),
		'xsd:setexwsdl'); // Disponibilidad del WS

	// Metodo de transferencia de datos compatible con PHP 8
	$rawPostData = file_get_contents('php://input');
	if (empty($rawPostData)) {
		$rawPostData = $GLOBALS['HTTP_RAW_POST_DATA'] ?? '';
	}
	
	// Log de request si necesario
	if (function_exists('watchDog::logDebug') && !empty($rawPostData)) {
		watchDog::logDebug('SOAP Request recibido', [
			'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
			'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
			'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A'
		], 'soap_service');
	}

	$server->service($rawPostData);

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