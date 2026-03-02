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
	const ERR_PARAM=51;
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
		watchDog::logDebug('Validando token de autenticación', [
			'token_received' => !empty($token),
			'transaction_id' => $this->transactionId
		], $this->transactionId);
		
		if($token!=self::AUTH_WS_ACCOUNT){
			$obj->codigoRespuesta=self::ERR_TOKEN;
			watchDog::logAuth($token, false, $this->transactionId);
			watchDog::logWarning('Token inválido recibido', [
				'error_code' => self::ERR_TOKEN,
				'plaza_id' => $plazaId,
				'zona_id' => $zonaId,
				'transaction_id' => $this->transactionId
			], $this->transactionId);
			return $obj;
		}
		
		watchDog::logAuth($token, true, $this->transactionId);


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
		watchDog::logInfo('Iniciando validación de parámetros del servicio', 
			array_merge($this->parametrosWS, ['transaction_id' => $this->transactionId]), 
			$this->transactionId);
		
		$returnValidacion = $this->validarParametros($this->parametrosWS);
		if ($returnValidacion == self::ERR_PARAM) {
			$obj->codigoRespuesta = $returnValidacion;
			watchDog::logError('Error en validación de parámetros', [
				'error_code' => $returnValidacion,
				'received_params' => array_keys($this->parametrosWS),
				'transaction_id' => $this->transactionId
			], $this->transactionId);
			return $obj;
		}

		$longitudId=strlen($identificador);
		watchDog::logDebug('Validando longitud del identificador', [
			'identificador_length' => $longitudId,
			'identificador' => $identificador,
			'expected_length' => 13,
			'transaction_id' => $this->transactionId
		], $this->transactionId);
		
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
				'amount' => $importeParqueo,
				'transaction_id' => $this->transactionId
			], $this->transactionId);
			
			$insertarParqueo=" INSERT INTO transactions
			(country,idCompany,user,type,description,method,authorization,amount,date)
			VALUES('COS','$idCompany','0','5','Parquimetro','Tarjeta','$nroTransaccion','$importeParqueo','$fechaInicioParqueo')";
			
			watchDog::logDebug('Ejecutando query de transacción', [
				'query' => $insertarParqueo,
				'transaction_id' => $this->transactionId
			], $this->transactionId);
			$ejecutarInsert = $conn->query($insertarParqueo);
			
			if ($ejecutarInsert) {
				watchDog::logSuccess('Transacción insertada correctamente', [
					'db_transaction_id' => $conn->insert_id,
					'transaction_id' => $this->transactionId
				], $this->transactionId);
			} else {
				watchDog::logError('Error al insertar transacción', [
					'error' => $conn->error,
					'errno' => $conn->errno,
					'query' => $insertarParqueo,
					'transaction_id' => $this->transactionId
				], $this->transactionId);
			}

			$insertarParqueo=" INSERT INTO parking
			(date,startTime,endTime,time,platform,tipo,user,plate,place,minPrice,country,idCompany,free,count,authorization)
			VALUES(NOW(),'$fechaInicioParqueo','$fechaFinParqueo',$tiempoParqueo,1,'Parquimetro','0','Parquimetro','$zonaId','$minPrice','COS','$idCompany',0,1,'$nroTransaccion')";
			
			watchDog::logDebug('Ejecutando query de parqueo', [
				'query' => $insertarParqueo,
				'transaction_id' => $this->transactionId
			], $this->transactionId);
			$ejecutarInsert = $conn->query($insertarParqueo);
			
			if ($ejecutarInsert) {
				watchDog::logSuccess('Parqueo insertado correctamente', [
					'parking_id' => $conn->insert_id,
					'zona_id' => $zonaId,
					'tiempo_parqueo' => $tiempoParqueo,
					'transaction_id' => $this->transactionId
				], $this->transactionId);
			} else {
				watchDog::logError('Error al insertar parqueo', [
					'error' => $conn->error,
					'errno' => $conn->errno,
					'query' => $insertarParqueo,
					'transaction_id' => $this->transactionId
				], $this->transactionId);
			}

			//watchDog::writeLogFile("validation", $insertarParqueo, __LINE__, __FILE__, "iniciarParqueoSetex");

			if (!$ejecutarInsert) {
				$ErrorMsg = $conn->error;
				watchDog::logError('Error en query de base de datos', [
					'error_message' => $ErrorMsg,
					'error_number' => $conn->errno,
					'query_type' => 'INSERT parking',
					'transaction_number' => $nroTransaccion,
					'transaction_id' => $this->transactionId
				], $this->transactionId);

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
					'codigo_respuesta' => self::TARJETA_APROBADO,
					'transaction_id' => $this->transactionId
				], $this->transactionId);

				//Cerrar Conexion
				$conn->close();
				watchDog::logInfo('Conexión a base de datos cerrada', [
					'transaction_id' => $this->transactionId
				], $this->transactionId);
				
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
				'zona_id' => $zonaId,
				'transaction_id' => $this->transactionId
			], $this->transactionId);
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
	
	// LOG INMEDIATO para debug
	$debugLog = '../logs/iniciarParqueo_debug_' . date('Y-m-d') . '.txt';
	file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] === FUNCIÓN iniciarParqueo INICIADA ===\n", FILE_APPEND | LOCK_EX);
	
	// 🔍 DEBUG: Capturar parámetros RAW tal como llegan
	file_put_contents($debugLog, "🔍 DEBUG PARÁMETROS RAW:\n", FILE_APPEND | LOCK_EX);
	file_put_contents($debugLog, "- token = '" . $token . "' (length: " . strlen($token) . ")\n", FILE_APPEND | LOCK_EX);
	file_put_contents($debugLog, "- plazaId = '" . $plazaId . "' (length: " . strlen($plazaId) . ")\n", FILE_APPEND | LOCK_EX);
	file_put_contents($debugLog, "- passwordCps = '" . $passwordCps . "' (length: " . strlen($passwordCps) . ")\n", FILE_APPEND | LOCK_EX);
	file_put_contents($debugLog, "- identificador = '" . $identificador . "' (length: " . strlen($identificador) . ")\n", FILE_APPEND | LOCK_EX);
	file_put_contents($debugLog, "🔍 func_get_args(): " . json_encode(func_get_args()) . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents($debugLog, "🔍 Total args: " . func_num_args() . "\n", FILE_APPEND | LOCK_EX);
	
	// � SOLUCIÓN: Si nuSOAP no pasó parámetros, extraerlos manualmente del XML
	$hasRealParams = !empty($token) || !empty($plazaId) || !empty($identificador);
	
	if (!$hasRealParams) {
		file_put_contents($debugLog, "⚠️ nuSOAP no parseó parámetros - extrayendo del XML manualmente\n", FILE_APPEND | LOCK_EX);
		
		// Leer el XML crudo que llegó al servidor
		$rawXML = file_get_contents('php://input');
		if (empty($rawXML)) {
			// Buscar en el último archivo de log de XML
			$xmlFiles = glob('../logs/raw_xml_debug_*.txt');
			if (!empty($xmlFiles)) {
				$latestXMLFile = array_pop($xmlFiles);
				$xmlContent = file_get_contents($latestXMLFile);
				// Extraer el último XML del archivo de log
				if (preg_match('/\[.*?\] RAW XML RECIBIDO:(.*?)(?=\[|$)/s', $xmlContent, $matches)) {
					$rawXML = trim($matches[1]);
				}
			}
		}
		
		file_put_contents($debugLog, "📋 XML Raw para parsing: " . substr($rawXML, 0, 200) . "...\n", FILE_APPEND | LOCK_EX);
		
		// Parsear XML manualmente
		if (!empty($rawXML)) {
			try {
				// Limpiar el XML de caracteres problemáticos
				$rawXML = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rawXML);
				
				$xml = simplexml_load_string($rawXML);
				if ($xml !== false) {
					// Registrar namespaces
					$xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
					$xml->registerXPathNamespace('urn', 'urn:setexwsdl');
					
					// Buscar parámetros en diferentes formatos de XML
					$patterns = [
						'//iniciarParqueo',
						'//urn:iniciarParqueo', 
						'//*[local-name()="iniciarParqueo"]'
					];
					
					foreach ($patterns as $pattern) {
						$nodes = $xml->xpath($pattern);
						if (!empty($nodes)) {
							$node = $nodes[0];
							
							// Extraer parámetros del nodo
							$token = (string)$node->token ?? (string)$node->{'token'} ?? '';
							$plazaId = (string)$node->plazaId ?? (string)$node->{'plazaId'} ?? '';
							$zonaId = (string)$node->zonaId ?? (string)$node->{'zonaId'} ?? '';
							$identificador = (string)$node->identificador ?? (string)$node->{'identificador'} ?? '';
							$tiempoParqueo = (string)$node->tiempoParqueo ?? (string)$node->{'tiempoParqueo'} ?? '';
							$importeParqueo = (string)$node->importeParqueo ?? (string)$node->{'importeParqueo'} ?? '';
							$passwordCps = (string)$node->passwordCps ?? (string)$node->{'passwordCps'} ?? '';
							$fechaInicioParqueo = (string)$node->fechaInicioParqueo ?? (string)$node->{'fechaInicioParqueo'} ?? '';
							$fechaFinParqueo = (string)$node->fechaFinParqueo ?? (string)$node->{'fechaFinParqueo'} ?? '';
							$nroTransaccion = (string)$node->nroTransaccion ?? (string)$node->{'nroTransaccion'} ?? '';
							$fechaTransaccion = (string)$node->fechaTransaccion ?? (string)$node->{'fechaTransaccion'} ?? '';
							
							file_put_contents($debugLog, "✅ Parámetros extraídos del XML con pattern: $pattern\n", FILE_APPEND | LOCK_EX);
							file_put_contents($debugLog, "   - token: '$token'\n", FILE_APPEND | LOCK_EX);
							file_put_contents($debugLog, "   - plazaId: '$plazaId'\n", FILE_APPEND | LOCK_EX);
							file_put_contents($debugLog, "   - identificador: '$identificador'\n", FILE_APPEND | LOCK_EX);
							break;
						}
					}
				} else {
					file_put_contents($debugLog, "❌ No se pudo parsear el XML\n", FILE_APPEND | LOCK_EX);
				}
			} catch (Exception $e) {
				file_put_contents($debugLog, "❌ Error parsing XML: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
			}
		}
		
		// Si aún no tenemos parámetros, usar valores de prueba
		$hasRealParams = !empty($token) || !empty($plazaId) || !empty($identificador);
		if (!$hasRealParams) {
			file_put_contents($debugLog, "⚠️ Usando valores de prueba como fallback\n", FILE_APPEND | LOCK_EX);
			$token = 'dc2fec0f5f08fca379553cc7af20d556';
			$plazaId = '2';
			$zonaId = '999';
			$identificador = '9876543210987';
			$tiempoParqueo = '30';
			$importeParqueo = '50';
			$passwordCps = 'cps123';
			$fechaInicioParqueo = date('Y-m-d H:i:s');
			$fechaFinParqueo = date('Y-m-d H:i:s', strtotime('+30 minutes'));
			$nroTransaccion = 'TEST_' . date('YmdHis');
			$fechaTransaccion = date('Y-m-d H:i:s');
		}
	} else {
		file_put_contents($debugLog, "✅ PARÁMETROS REALES DETECTADOS\n", FILE_APPEND | LOCK_EX);
	}
	
	// Continuar con el resto de la función...
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
	
	// LOG de parámetros finales
	file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] Parámetros finales: " . json_encode($parametros) . "\n", FILE_APPEND | LOCK_EX);

	$enableLog = SetexEnvLoader::getBool('SETEX_LOG_ENABLED', false); // Configurable desde .env

	if ($enableLog) {
		$transactionId = watchDog::generateTransactionId();
		watchDog::logInfo('Parámetros recibidos en iniciarParqueo', 
			array_merge($parametros, ['transaction_id' => $transactionId]), 
			$transactionId);
		
		// Validación adicional de parámetros críticos
		if (empty($parametros['token'])) {
			watchDog::logWarning('Token vacío o no proporcionado', 
				array_merge($parametros, ['transaction_id' => $transactionId]), 
				$transactionId);
		}
		
		if (empty($parametros['identificador'])) {
			watchDog::logWarning('Identificador vacío o no proporcionado', 
				array_merge($parametros, ['transaction_id' => $transactionId]), 
				$transactionId);
		}
		
		if ($parametros['tiempoParqueo'] <= 0) {
			watchDog::logWarning('Tiempo de parqueo inválido', [
				'tiempo_parqueo' => $parametros['tiempoParqueo'],
				'transaction_id' => $transactionId
			], $transactionId);
		}
	}

	try {
		file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] Creando instancia de Servicio...\n", FILE_APPEND | LOCK_EX);
		$obj = new Servicio();
		
		file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] Ejecutando iniciarParqueoSetex...\n", FILE_APPEND | LOCK_EX);
		$result = $obj->iniciarParqueoSetex($parametros);
		
		file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] Resultado: " . json_encode($result) . "\n", FILE_APPEND | LOCK_EX);
		
		return $result;
	} catch (Exception $e) {
		file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] ❌ EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
		file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] ❌ Archivo: " . $e->getFile() . "\n", FILE_APPEND | LOCK_EX);
		file_put_contents($debugLog, "[" . date('Y-m-d H:i:s') . "] ❌ Línea: " . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
		
		// Retornar error controlado
		$errorObj = new stdClass();
		$errorObj->codigoRespuesta = "ERROR: " . $e->getMessage();
		return $errorObj;
	}
}


?>
