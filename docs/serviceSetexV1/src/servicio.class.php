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
				return $codigoError;
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
		if($token!=self::AUTH_WS_ACCOUNT){
			$obj->codigoRespuesta=self::ERR_TOKEN;
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



		//watchDog::writeLogFile("validation", $parametrosEntrada, __LINE__, __FILE__, "iniciarParqueoSetex");

		//Validacion de Parametros
		$returnValidacion = $this->validarParametros($this->parametrosWS);
		if ($returnValidacion == self::ERR_PARAM) {
			$obj->codigoRespuesta = $returnValidacion;
			watchDog::writeLogFile("validation","Validacion de Parametros". $returnValidacion, __LINE__, __FILE__, "iniciarParqueoSetex");
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
			#Pagos con tarjeta de credito

			#watchDog::writeLogFile("validation",'voy a insertar tnx', __LINE__, __FILE__, "pruebaSQL");
			$insertarParqueo=" INSERT INTO transactions
			(country,idCompany,user,type,description,method,authorization,amount,date)
			VALUES('COS','$idCompany','0','5','Parquimetro','Tarjeta','$nroTransaccion','$importeParqueo','$fechaInicioParqueo')";
			#watchDog::writeLogFile("validation",$insertarParqueo, __LINE__, __FILE__, "pruebaSQL");
			$ejecutarInsert = $conn->query($insertarParqueo);
			#watchDog::writeLogFile("validation",'lo inserte', __LINE__, __FILE__, "pruebaSQL");

			$insertarParqueo=" INSERT INTO parking
			(date,startTime,endTime,time,platform,tipo,user,plate,place,minPrice,country,idCompany,free,count,authorization)
			VALUES(NOW(),'$fechaInicioParqueo','$fechaFinParqueo',$tiempoParqueo,1,'Parquimetro','0','Parquimetro','$zonaId','$minPrice','COS','$idCompany',0,1,'$nroTransaccion')";
			$ejecutarInsert = $conn->query($insertarParqueo);

			//watchDog::writeLogFile("validation", $insertarParqueo, __LINE__, __FILE__, "iniciarParqueoSetex");

			if (!$ejecutarInsert) {
				$ErrorMsg = $conn->connect_error;
				watchDog::writeLogFile("validation",$ErrorMsg, __LINE__, __FILE__, "iniciarParqueoSetex");

				$obj->codigoRespuesta = self::ERR_QUERY;
				return $obj;
			}
			else{

				//Cerrar Conexion
				$conn->close();
				$obj->codigoRespuesta=self::TARJETA_APROBADO;
				//watchDog::writeLogFile("validation", "CODIGO RESPUESTA: ".$obj->codigoRespuesta, __LINE__, __FILE__, "iniciarParqueoSetex");

				return $obj;
			}
		}
		else{
			//Identificador Ingresado invalido
			$obj->codigoRespuesta = self::ERR_ID;
			watchDog::writeLogFile("validation","Identificador Ingresado invalido".self::ERR_ID, __LINE__, __FILE__, "iniciarParqueoSetex");
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

	$obj = new Servicio();
	return $obj->consultarDisponibilidad();
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

	$enableLog = false;

	if ($enableLog) {
		watchDog::writeLogFile("data",json_encode($parametros), __LINE__, __FILE__, "iniciarParqueo");
	}

	$obj = new Servicio();
	return $obj->iniciarParqueoSetex($parametros);
}




?>
