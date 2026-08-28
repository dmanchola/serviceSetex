<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
/*
 * Clase para el manejo de metodos del WebServices de Parqueo
*/

include_once("setex-config.php");
require_once("conexion.class.php");
require_once("watchdog.php");
require_once("env-loader.php");

class Servicio {


	const AUTH_WS_ACCOUNT = 'dc2fec0f5f08fca379553cc7af20d556';
	const versionId="3.4";


	//Manejo de tajeta de credito
	const TARJETA_APROBADO=6;

	//Errores Generales
	const ERR_PARAM=6; // 51;
	const ERR_TOKEN=52;
	const ERR_QUERY=53;
	const ERR_OFFLINE=54;
	const ERR_ID=57;

	//*************************************/
	//Parametros Globales
	var $error = array();
	var $parametrosWS = array();
	var $transactionId = null;

	function __construct() {
		global $conn;

		$conn = conexion();
		if (!$conn) {
			return self::ERR_OFFLINE;
			exit;
		}
	}


	/**
	 * Validacion de los parametros
	 * @param array $parametros
	 * @return codigo de error
	 */
	function validarParametros($parametros) {
		$codigoError = 0;
		
		foreach ($parametros as $indice => $valor) {
			if (!isset($parametros[$indice]) OR $parametros[$indice] == "") {
				$codigoError = self::ERR_PARAM;
			}
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

	// Incrementa contador diario en archivo JSON sin bloquear la respuesta
	private static function contarDuplicado($tipo) {
		$archivo = dirname(dirname(__FILE__)) . '/logs/duplicados_' . date('Y-m-d') . '.txt';
		$fp = @fopen($archivo, 'c+');
		if (!$fp) return;
		if (@flock($fp, LOCK_EX | LOCK_NB)) {
			$data = json_decode(@fread($fp, 512), true) ?: ['expired' => 0, 'db' => 0, 'total' => 0];
			$data[$tipo] = ($data[$tipo] ?? 0) + 1;
			$data['total'] = $data['expired'] + $data['db'];
			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, json_encode($data));
			flock($fp, LOCK_UN);
		}
		fclose($fp);
	}




	/**
	 * Iniciar Parqueo
	 * @param array $parametros
	 * @return codigo de Respuesta WebServices
	 */
	function iniciarParqueoSetex($parametros=array()) {
		global $conn;
		// 30s es suficiente para 2 INSERTs; evita acumulación de procesos colgados
		set_time_limit(30);
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
		if($token!=self::AUTH_WS_ACCOUNT){
			$obj->codigoRespuesta=self::ERR_TOKEN;
			watchDog::logAuth($token, false, $this->transactionId);
			return $obj;
		}


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

		//Validacion de Parametros
		$returnValidacion = $this->validarParametros($this->parametrosWS);
		if ($returnValidacion == self::ERR_PARAM) {
			$obj->codigoRespuesta = $returnValidacion;
			return $obj;
		}

		// Modo contingencia: responde OK sin tocar la BD
		if (SetexEnvLoader::getBool('SETEX_CONTINGENCY_MODE', false)) {
			watchDog::logWarning('CONTINGENCY MODE activo - retornando aprobado sin DB', [
				'nro_transaccion' => $nroTransaccion,
				'plaza_id' => $plazaId
			], $this->transactionId);
			$obj->codigoRespuesta = self::TARJETA_APROBADO;
			return $obj;
		}

		// Parqueo vencido: fecha fin anterior al día de hoy → ya fue procesado, no consultar BD
		$fechaFinDate = date('Y-m-d', strtotime($fechaFinParqueo));
		if ($fechaFinDate < date('Y-m-d')) {
			self::contarDuplicado('expired');
			$obj->codigoRespuesta = self::TARJETA_APROBADO;
			return $obj;
		}

		$longitudId=strlen($identificador);
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
			// Verificar si ya existe un registro con el mismo nroTransaccion (idempotencia)
			$stmtCheck = $conn->prepare(
				"SELECT COUNT(*) as total FROM transactions WHERE authorization=? AND idCompany=? AND country='COS' AND type='5'"
			);
			$stmtCheck->bind_param("ss", $nroTransaccion, $idCompany);
			$stmtCheck->execute();
			$resultadoCheck = $stmtCheck->get_result();

			if ($resultadoCheck === false) {
				$errMsg = $conn->error;
				watchDog::logError('Error en verificación de duplicado en transactions', [
					'error' => $errMsg,
					'nro_transaccion' => $nroTransaccion
				], $this->transactionId);
				$obj->codigoRespuesta = self::ERR_QUERY;
				return $obj;
			}

			$fila = $resultadoCheck->fetch_assoc();
			$stmtCheck->close();

			if ($fila['total'] > 0) {
				self::contarDuplicado('db');
				$conn->close();
				$obj->codigoRespuesta = self::TARJETA_APROBADO;
				return $obj;
			}
			
			$insertarParqueo=" INSERT INTO transactions
			(country,idCompany,user,type,description,method,authorization,amount,date)
			VALUES('COS','$idCompany','0','5','Parquimetro','Tarjeta','$nroTransaccion','$importeParqueo','$fechaInicioParqueo')";
			
			$ejecutarInsert = $conn->query($insertarParqueo);
			
			if (!$ejecutarInsert) {
				watchDog::logError('Error al insertar transacción', [
					'error' => $conn->error,
					'errno' => $conn->errno,
					'nro_transaccion' => $nroTransaccion
				], $this->transactionId);
			}

			$insertarParqueo=" INSERT INTO parking
			(date,startTime,endTime,time,platform,tipo,user,plate,place,minPrice,country,idCompany,free,count,authorization)
			VALUES(NOW(),'$fechaInicioParqueo','$fechaFinParqueo',$tiempoParqueo,1,'Parquimetro','0','Parquimetro','$zonaId','$minPrice','COS','$idCompany',0,1,'$nroTransaccion')";
			
			$ejecutarInsert = $conn->query($insertarParqueo);

			if (!$ejecutarInsert) {
				watchDog::logError('Error al insertar parqueo', [
					'error' => $conn->error,
					'errno' => $conn->errno,
					'nro_transaccion' => $nroTransaccion,
					'zona_id' => $zonaId
				], $this->transactionId);
				$obj->codigoRespuesta = self::ERR_QUERY;
				return $obj;
			}
			else{
				watchDog::logInsert([
					'plaza' => $plazaId, 'zona' => $zonaId,
					'nro' => $nroTransaccion, 'importe' => $importeParqueo,
					'inicio' => $fechaInicioParqueo, 'fin' => $fechaFinParqueo
				]);

				$conn->close();
				$obj->codigoRespuesta=self::TARJETA_APROBADO;
				return $obj;
			}
		}
		else{
			//Identificador Ingresado invalido
			$obj->codigoRespuesta = self::ERR_ID;
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
	$transactionId = watchDog::generateTransactionId();
	watchDog::logInfo('Consultando versión del servicio', [
		'transaction_id' => $transactionId
	], $transactionId);
	
	try {
		$obj = new Servicio();
		$result = $obj->consultarDisponibilidad();
		
		watchDog::logSuccess('Versión consultada exitosamente', [
			'version' => $result->codigoRespuesta,
			'transaction_id' => $transactionId
		], $transactionId);
		
		return $result;
	} catch (Exception $e) {
		watchDog::logError('Error al consultar versión', [
			'error_message' => $e->getMessage(),
			'error_code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'transaction_id' => $transactionId
		], $transactionId);
		
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
		$tiempoParqueo="",$importeParqueo="",$passwordCps="",
		$fechaInicioParqueo="",$fechaFinParqueo="",$nroTransaccion="",$fechaTransaccion=""){
	
	$parametros = array();
	$parametros['token'] = $token;
	$parametros['plazaId'] = $plazaId;
	$parametros['zonaId'] = $zonaId;
	$parametros['identificador'] = $identificador;
	$parametros['tiempoParqueo'] = $tiempoParqueo;
	$parametros['importeParqueo'] = $importeParqueo;
	$parametros['fechaInicioParqueo'] = $fechaInicioParqueo;
	$parametros['fechaFinParqueo'] = $fechaFinParqueo;
	$parametros['nroTransaccion'] = $nroTransaccion;
	$parametros['fechaTransaccion'] = $fechaTransaccion;

	try {
		$obj = new Servicio();
		return $obj->iniciarParqueoSetex($parametros);
	} catch (Exception $e) {
		watchDog::logError('Excepción en iniciarParqueo', [
			'error' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine()
		], null);
		$errorObj = new stdClass();
		$errorObj->codigoRespuesta = "ERROR: " . $e->getMessage();
		return $errorObj;
	}
}


?>
