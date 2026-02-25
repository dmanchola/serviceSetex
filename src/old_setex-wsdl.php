<?php
include_once('setex-config.php');
require_once(LIBSPATH . 'nusoap/lib/nusoap.php');
require_once("servicio.class.php");

// Se instancia el servidor
$server = new nusoap_server;
$server->configureWSDL('SETEX', 'urn:setexwsdl');

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

// Metodo de transferencia de datos
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>
