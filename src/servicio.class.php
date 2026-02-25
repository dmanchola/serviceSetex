<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
/*
 * Clase para el manejo de metodos del WebServices de Parqueo
*/

include_once("setex-config.php");
require_once("conexion.class.php");
require_once("watchdog.php");

class Servicio {


	const AUTH_WS_ACCOUNT = 'dc2fec0f5f08fca379553cc7af20d556';
	const versionId="3.4";


	//Manejo de tajeta de credito
	const TARJETA_APROBADO=6;

	//Errores Generales
	const ERR_PARAM=6; //51
	const ERR_TOKEN=52;
	const ERR_QUERY=53;
	const ERR_OFFLINE=54;
	const ERR_ID=57;

	//*************************************/
	//Parametros Globales
	var $error = array();
	var $parametrosWS = array();

	function __construct() {
		global $conn;

		// Log de inicio de servicio
		watchDog::logInfo('Iniciando servicio SETEX', ['timestamp' => date('Y-m-d H:i:s')], 'servicio');

		$conn = conexion();
		if (!$conn) {
			watchDog::logError('Error de conexión a base de datos', ['error_code' => self::ERR_OFFLINE], 'servicio');
			return self::ERR_OFFLINE;
			exit;
		}
		
		watchDog::logSuccess('Conexión a base de datos establecida', [], 'servicio');
	}


	/**
	 * Validacion de los parametros
	 * @param array $parametros
	 * @return codigo de error
	 */
	function validarParametros($parametros) {
		$codigoError = 0;
		$parametrosFaltantes = [];
		
		watchDog::logDebug('Iniciando validación de parámetros', ['params_count' => count($parametros)], 'validation');
		
		foreach ($parametros as $indice => $valor) {
			if (!isset($parametros[$indice]) OR $parametros[$indice] == "") {
				$parametrosFaltantes[] = $indice;
				$codigoError = self::ERR_PARAM;
			}
		}
		
		if ($codigoError !== 0) {
			watchDog::logError('Parámetros faltantes o vacíos', [
				'missing_params' => $parametrosFaltantes,
				'error_code' => $codigoError
			], 'validation');
		} else {
			watchDog::logSuccess('Validación de parámetros exitosa', ['params_validated' => array_keys($parametros)], 'validation');
		}
		
		return $codigoError;
	}
	/**
	 * Consultar Disponibilidad para WS de WEBSITE
	 * @return <string>
	 */
	function consultarDisponibilidad() {
		$obj = new stdClass();
		$obj->codigoRespuesta = self::versionId;
		return $obj;
	}




	/**
	 * Iniciar Parqueo
	 * @param array $parametros
	 * @return codigo de Respuesta WebServices
	 */
	function iniciarParqueoSetex($parametros=array()) {
		global $conn;
		set_time_limit(0);
		$objLogWs = new watchDog();
		$obj= new stdClass();
		$obj->codigoRespuesta="";

		$token = $this->parametrosWS['token'] = $parametros['token'];
		$plazaId = $this->parametrosWS['plazaId'] = $parametros['plazaId'];
		$zonaId = $this->parametrosWS['zonaId'] = $parametros['zonaId'];
		$identificador = $this->parametrosWS['identificador'] = $parametros['identificador'];
		$tiempoParqueo = $this->parametrosWS['tiempoParqueo'] = $parametros['tiempoParqueo'];
		$importeParqueo = $this->parametrosWS['importeParqueo'] = $parametros['importeParqueo'];
		$fechaInicioParqueo = $this->parametrosWS['fechaInicioParqueo'] = $parametros['fechaInicioParqueo'];
		$fechaFinParqueo = $parametros['fechaFinParqueo'];

		$nroTransaccion = $parametros['nroTransaccion'];
		$fechaTransaccion = $parametros['fechaTransaccion'];

		$plate = "Parquimetro".$identificador;

		//Validacion Token
		watchDog::logDebug('Validando token de autenticación', ['token_received' => !empty($token)], 'auth');
		
		if($token!=self::AUTH_WS_ACCOUNT){
			$obj->codigoRespuesta=self::ERR_TOKEN;
			watchDog::logAuth($token, false, 'auth');
			watchDog::logWarning('Token inválido recibido', [
				'error_code' => self::ERR_TOKEN,
				'plaza_id' => $plazaId,
				'zona_id' => $zonaId
			], 'security');
			return $obj;
		}
		
		watchDog::logAuth($token, true, 'auth');


		//Escritura de Archivo Inicio de Transaccion
		$parametrosEntrada = "INICIO VALIDACION PARQUEO:"  .
				" PLAZA  :" . $plazaId .
				" ZONA  :" . $zonaId .
				" IDENTIFICADOR :" . $identificador .
				" TIEMPO PARQUEO :" . $tiempoParqueo .
				" IMPORTE PARQUEO :" . $importeParqueo .
				" FECHA INICIO PARQUEO :" . $fechaInicioParqueo .
				" FECHA FIN PARQUEO :" . $fechaFinParqueo.
				" NRO TRANSACCION :" . $nroTransaccion.
				" FECHA TRANSACCION :" . $fechaTransaccion;



		//watchDog::writeLogFile("validation", $parametrosEntrada, __LINE__, __FILE__, "iniciarParqueoSetex");

		//Validacion de Parametros
		watchDog::logInfo('Iniciando validación de parámetros del servicio', $this->parametrosWS, 'iniciarParqueoSetex');
		
		$returnValidacion = $this->validarParametros($this->parametrosWS);
		if ($returnValidacion == self::ERR_PARAM) {
			$obj->codigoRespuesta = $returnValidacion;
			watchDog::logError('Error en validación de parámetros', [
				'error_code' => $returnValidacion,
				'received_params' => array_keys($this->parametrosWS)
			], 'iniciarParqueoSetex');
			return $obj;
		}

		$longitudId=strlen($identificador);
		watchDog::logDebug('Validando longitud del identificador', [
			'identificador_length' => $longitudId,
			'identificador' => $identificador,
			'expected_length' => 13
		], 'iniciarParqueoSetex');
		
		if($longitudId==13){
			$minPrice = "0";
			$idCompany = "0";
			switch ($plazaId) {
    		case 1:
        		$minPrice = "16.00";
        		$idCompany = "1";
        		break;
    		case 2:
        		$minPrice = "11.333333333333332";
        		$idCompany = "2";
        		break;
        	case 3:
        		$minPrice = "12.5";
        		$idCompany = "3";
        		break;
        	case 4:
        		$minPrice = "10.00";
        		$idCompany = "7";
        		break;
			}
			#Pagos con tarjeta de credito

			watchDog::logInfo('Preparando inserción de transacción', [
				'company_id' => $idCompany,
				'min_price' => $minPrice,
				'transaction_number' => $nroTransaccion,
				'amount' => $importeParqueo
			], 'iniciarParqueoSetex');
			
			$insertarParqueo=" INSERT INTO transactions
			(country,idCompany,user,type,description,method,authorization,amount,date)
			VALUES('COS','$idCompany','0','5','Parquimetro','Tarjeta','$nroTransaccion','$importeParqueo','$fechaInicioParqueo')";
			
			watchDog::logDebug('Ejecutando query de transacción', ['query' => $insertarParqueo], 'database');
			$ejecutarInsert = $conn->query($insertarParqueo);
			
			if ($ejecutarInsert) {
				watchDog::logSuccess('Transacción insertada correctamente', ['transaction_id' => $conn->insert_id], 'database');
			} else {
				watchDog::logError('Error al insertar transacción', [
					'error' => $conn->error,
					'errno' => $conn->errno,
					'query' => $insertarParqueo
				], 'database');
			}

			$insertarParqueo=" INSERT INTO parking
			(date,startTime,endTime,time,platform,tipo,user,plate,place,minPrice,country,idCompany,free,count,authorization)
			VALUES(NOW(),'$fechaInicioParqueo','$fechaFinParqueo',$tiempoParqueo,1,'Parquimetro','0','Parquimetro','$zonaId','$minPrice','COS','$idCompany',0,1,'$nroTransaccion')";
			
			watchDog::logDebug('Ejecutando query de parqueo', ['query' => $insertarParqueo], 'database');
			$ejecutarInsert = $conn->query($insertarParqueo);
			
			if ($ejecutarInsert) {
				watchDog::logSuccess('Parqueo insertado correctamente', [
					'parking_id' => $conn->insert_id,
					'zona_id' => $zonaId,
					'tiempo_parqueo' => $tiempoParqueo
				], 'database');
			} else {
				watchDog::logError('Error al insertar parqueo', [
					'error' => $conn->error,
					'errno' => $conn->errno,
					'query' => $insertarParqueo
				], 'database');
			}

			//watchDog::writeLogFile("validation", $insertarParqueo, __LINE__, __FILE__, "iniciarParqueoSetex");

			if (!$ejecutarInsert) {
				$ErrorMsg = $conn->error;
				watchDog::logError('Error en query de base de datos', [
					'error_message' => $ErrorMsg,
					'error_number' => $conn->errno,
					'query_type' => 'INSERT parking',
					'transaction_number' => $nroTransaccion
				], 'iniciarParqueoSetex');

				$obj->codigoRespuesta = self::ERR_QUERY;
				return $obj;
			}
			else{
				watchDog::logSuccess('Parqueo iniciado exitosamente', [
					'plaza_id' => $plazaId,
					'zona_id' => $zonaId,
					'identificador' => $identificador,
					'tiempo_parqueo' => $tiempoParqueo,
					'importe' => $importeParqueo,
					'transaction_number' => $nroTransaccion,
					'codigo_respuesta' => self::TARJETA_APROBADO
				], 'iniciarParqueoSetex');

				//Cerrar Conexion
				$conn->close();
				watchDog::logInfo('Conexión a base de datos cerrada', [], 'database');
				
				$obj->codigoRespuesta=self::TARJETA_APROBADO;
				return $obj;
			}
		}
		else{
			//Identificador Ingresado invalido
			$obj->codigoRespuesta = self::ERR_ID;
			watchDog::logWarning('Identificador con longitud inválida', [
				'identificador' => $identificador,
				'longitud_recibida' => $longitudId,
				'longitud_esperada' => 13,
				'error_code' => self::ERR_ID,
				'plaza_id' => $plazaId,
				'zona_id' => $zonaId
			], 'iniciarParqueoSetex');
			return  $obj;
		}



	}



}



/**************************************************************************************/


/**
 * Metodo para indicar la disponibilidad del WebServices en WEBSITE
 * @return <type>
 */
function getVersion() {
	watchDog::logInfo('Consultando versión del servicio', [], 'getVersion');
	
	try {
		$obj = new Servicio();
		$result = $obj->consultarDisponibilidad();
		
		watchDog::logSuccess('Versión consultada exitosamente', [
			'version' => $result->codigoRespuesta
		], 'getVersion');
		
		return $result;
	} catch (Exception $e) {
		watchDog::logError('Error al consultar versión', [
			'error_message' => $e->getMessage(),
			'error_code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine()
		], 'getVersion');
		
		// Retornar error controlado
		$errorObj = new stdClass();
		$errorObj->codigoRespuesta = "ERROR: " . $e->getMessage();
		return $errorObj;
	}
}


/**
 *
 * @param string $token
 * @param string $plazaId
 * @param string $zonaId
 * @param string $identificador
 * @param string $tiempoParqueo
 * @param string $importeParqueo
 * @param string $password
 * @param string $fechaInicioParqueo
 * @param string $fechaFinParqueo
 */
function iniciarParqueo($token="",$plazaId="",$zonaId="",$identificador="",
		$tiempoParqueo="",$importeParqueo="",$password="",
		$fechaInicioParqueo="",$fechaFinParqueo="",$nroTransaccion="",$fechaTransaccion=""){
	$parametros=array();
	$parametros['token']=$token;
	$parametros['plazaId']=$plazaId;
	$parametros['zonaId']=$zonaId;
	$parametros['identificador']=$identificador;
	$parametros['tiempoParqueo']=$tiempoParqueo;
	$parametros['importeParqueo']=$importeParqueo;
	$parametros['fechaInicioParqueo']=$fechaInicioParqueo;
	$parametros['fechaFinParqueo']=$fechaFinParqueo;

	#PAGO TC
	$parametros['nroTransaccion']=$nroTransaccion;
	$parametros['fechaTransaccion']=$fechaTransaccion;

	$enableLog = true; // Habilitamos los logs por defecto para mejor debugging

	if ($enableLog) {
		watchDog::logInfo('Parámetros recibidos en iniciarParqueo', $parametros, 'iniciarParqueo');
	}
	
	// Validación adicional de parámetros críticos
	if (empty($parametros['token'])) {
		watchDog::logWarning('Token vacío o no proporcionado', $parametros, 'iniciarParqueo');
	}
	
	if (empty($parametros['identificador'])) {
		watchDog::logWarning('Identificador vacío o no proporcionado', $parametros, 'iniciarParqueo');
	}
	
	if ($parametros['tiempoParqueo'] <= 0) {
		watchDog::logWarning('Tiempo de parqueo inválido', [
			'tiempo_parqueo' => $parametros['tiempoParqueo']
		], 'iniciarParqueo');
	}

	$obj = new Servicio();
	return $obj->iniciarParqueoSetex($parametros);
}




?>
