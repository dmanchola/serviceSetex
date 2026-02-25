<?php
/**
 * Cliente de prueba para los servicios web SETEX
 * Incluye manejo mejorado de errores y logs
 */

require_once('libs/nusoap/nusoap.php');
require_once('src/watchdog.php');

class SetexClientTest {
    
    private $client;
    private $serviceUrl;
    
    public function __construct($serviceUrl) {
        $this->serviceUrl = $serviceUrl;
        $this->initClient();
    }
    
    /**
     * Inicializar cliente SOAP
     */
    private function initClient() {
        try {
            watchDog::logInfo('Inicializando cliente SOAP', ['service_url' => $this->serviceUrl], 'client_test');
            
            $this->client = new nusoap_client($this->serviceUrl . '?wsdl', true);
            
            if ($this->client->getError()) {
                throw new Exception('Error al crear cliente SOAP: ' . $this->client->getError());
            }
            
            watchDog::logSuccess('Cliente SOAP inicializado correctamente', [], 'client_test');
            
        } catch (Exception $e) {
            watchDog::logError('Error al inicializar cliente SOAP', [
                'error_message' => $e->getMessage(),
                'service_url' => $this->serviceUrl
            ], 'client_test');
            throw $e;
        }
    }
    
    /**
     * Probar servicio getVersion
     */
    public function testGetVersion($valor = 'test_client') {
        watchDog::logInfo('Iniciando prueba de getVersion', ['valor' => $valor], 'client_test');
        
        try {
            $params = array('valor' => $valor);
            
            $startTime = microtime(true);
            $result = $this->client->call('getVersion', $params);
            $endTime = microtime(true);
            
            $responseTime = round(($endTime - $startTime) * 1000, 2); // en millisegundos
            
            if ($this->client->fault) {
                watchDog::logError('Error SOAP en getVersion', [
                    'fault_string' => $this->client->faultstring,
                    'fault_code' => $this->client->faultcode ?? 'N/A',
                    'response_time_ms' => $responseTime
                ], 'client_test');
                return false;
            }
            
            watchDog::logSuccess('getVersion ejecutado correctamente', [
                'result' => $result,
                'response_time_ms' => $responseTime,
                'valor_enviado' => $valor
            ], 'client_test');
            
            return $result;
            
        } catch (Exception $e) {
            watchDog::logError('Excepción en testGetVersion', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 'client_test');
            return false;
        }
    }
    
    /**
     * Probar servicio iniciarParqueo
     */
    public function testIniciarParqueo($testData = null) {
        // Datos de prueba por defecto
        $defaultData = [
            'token' => 'dc2fec0f5f08fca379553cc7af20d556',
            'plazaId' => 1,
            'zonaId' => 101,
            'identificador' => '1234567890123', // 13 caracteres
            'tiempoParqueo' => 60,
            'importeParqueo' => 1600,
            'passwordCps' => 'test_password',
            'fechaInicioParqueo' => date('Y-m-d H:i:s'),
            'fechaFinParqueo' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'nroTransaccion' => 'TEST_' . date('YmdHis') . '_' . rand(1000, 9999),
            'fechaTransaccion' => date('Y-m-d H:i:s')
        ];
        
        $params = $testData ?? $defaultData;
        
        watchDog::logInfo('Iniciando prueba de iniciarParqueo', [
            'params' => $params,
            'test_type' => $testData ? 'custom_data' : 'default_data'
        ], 'client_test');
        
        try {
            // Validaciones previas
            $this->validateParqueoParams($params);
            
            $startTime = microtime(true);
            $result = $this->client->call('iniciarParqueo', $params);
            $endTime = microtime(true);
            
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            if ($this->client->fault) {
                watchDog::logError('Error SOAP en iniciarParqueo', [
                    'fault_string' => $this->client->faultstring,
                    'fault_code' => $this->client->faultcode ?? 'N/A',
                    'response_time_ms' => $responseTime,
                    'params_sent' => $params
                ], 'client_test');
                return false;
            }
            
            // Análisis de la respuesta
            $this->analyzeParqueoResponse($result, $params, $responseTime);
            
            return $result;
            
        } catch (Exception $e) {
            watchDog::logError('Excepción en testIniciarParqueo', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'params' => $params
            ], 'client_test');
            return false;
        }
    }
    
    /**
     * Validar parámetros antes de enviar
     */
    private function validateParqueoParams($params) {
        $requiredFields = [
            'token', 'plazaId', 'zonaId', 'identificador', 
            'tiempoParqueo', 'importeParqueo', 'fechaInicioParqueo', 
            'fechaFinParqueo', 'nroTransaccion', 'fechaTransaccion'
        ];
        
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            watchDog::logWarning('Campos requeridos faltantes en parámetros', [
                'missing_fields' => $missingFields,
                'provided_fields' => array_keys($params)
            ], 'client_test');
        }
        
        // Validación específica del identificador
        if (strlen($params['identificador']) !== 13) {
            watchDog::logWarning('Longitud de identificador incorrecta', [
                'identificador' => $params['identificador'],
                'longitud_actual' => strlen($params['identificador']),
                'longitud_esperada' => 13
            ], 'client_test');
        }
        
        // Validación de tiempo de parqueo
        if ($params['tiempoParqueo'] <= 0) {
            watchDog::logWarning('Tiempo de parqueo inválido', [
                'tiempo_parqueo' => $params['tiempoParqueo']
            ], 'client_test');
        }
        
        // Validación de plaza ID
        if (!in_array($params['plazaId'], [1, 2, 3, 4])) {
            watchDog::logWarning('Plaza ID no reconocida', [
                'plaza_id' => $params['plazaId'],
                'plazas_validas' => [1, 2, 3, 4]
            ], 'client_test');
        }
    }
    
    /**
     * Analizar respuesta del servicio iniciarParqueo
     */
    private function analyzeParqueoResponse($result, $params, $responseTime) {
        if (isset($result['iniciarParqueoReturn']['codigoRespuesta'])) {
            $codigo = $result['iniciarParqueoReturn']['codigoRespuesta'];
            
            switch ($codigo) {
                case 6: // TARJETA_APROBADO o ERR_PARAM
                    if ($params['tiempoParqueo'] > 0 && strlen($params['identificador']) === 13) {
                        watchDog::logSuccess('Parqueo aprobado exitosamente', [
                            'codigo_respuesta' => $codigo,
                            'response_time_ms' => $responseTime,
                            'transaction_number' => $params['nroTransaccion'],
                            'plaza_id' => $params['plazaId']
                        ], 'client_test');
                    } else {
                        watchDog::logError('Error de parámetros', [
                            'codigo_respuesta' => $codigo,
                            'response_time_ms' => $responseTime,
                            'error_type' => 'ERR_PARAM'
                        ], 'client_test');
                    }
                    break;
                    
                case 52: // ERR_TOKEN
                    watchDog::logError('Token de autenticación inválido', [
                        'codigo_respuesta' => $codigo,
                        'response_time_ms' => $responseTime,
                        'token_enviado' => substr($params['token'], 0, 8) . '...'
                    ], 'client_test');
                    break;
                    
                case 53: // ERR_QUERY
                    watchDog::logError('Error en consulta de base de datos', [
                        'codigo_respuesta' => $codigo,
                        'response_time_ms' => $responseTime
                    ], 'client_test');
                    break;
                    
                case 54: // ERR_OFFLINE
                    watchDog::logError('Servicio fuera de línea o error de conexión', [
                        'codigo_respuesta' => $codigo,
                        'response_time_ms' => $responseTime
                    ], 'client_test');
                    break;
                    
                case 57: // ERR_ID
                    watchDog::logError('Identificador inválido', [
                        'codigo_respuesta' => $codigo,
                        'response_time_ms' => $responseTime,
                        'identificador' => $params['identificador'],
                        'longitud_identificador' => strlen($params['identificador'])
                    ], 'client_test');
                    break;
                    
                default:
                    watchDog::logWarning('Código de respuesta desconocido', [
                        'codigo_respuesta' => $codigo,
                        'response_time_ms' => $responseTime
                    ], 'client_test');
            }
        } else {
            watchDog::logWarning('Respuesta sin código de respuesta esperado', [
                'result' => $result,
                'response_time_ms' => $responseTime
            ], 'client_test');
        }
    }
    
    /**
     * Ejecutar batería completa de pruebas
     */
    public function runFullTest() {
        watchDog::logInfo('Iniciando batería completa de pruebas', [], 'client_test');
        
        $results = [];
        
        // Prueba 1: GetVersion
        echo "🧪 Probando getVersion...\n";
        $results['getVersion'] = $this->testGetVersion();
        
        // Prueba 2: IniciarParqueo con datos válidos
        echo "🧪 Probando iniciarParqueo con datos válidos...\n";
        $results['iniciarParqueo_valid'] = $this->testIniciarParqueo();
        
        // Prueba 3: IniciarParqueo con token inválido
        echo "🧪 Probando iniciarParqueo con token inválido...\n";
        $invalidTokenData = [
            'token' => 'token_invalido',
            'plazaId' => 1,
            'zonaId' => 101,
            'identificador' => '1234567890123',
            'tiempoParqueo' => 60,
            'importeParqueo' => 1600,
            'passwordCps' => 'test_password',
            'fechaInicioParqueo' => date('Y-m-d H:i:s'),
            'fechaFinParqueo' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'nroTransaccion' => 'TEST_INVALID_' . date('YmdHis'),
            'fechaTransaccion' => date('Y-m-d H:i:s')
        ];
        $results['iniciarParqueo_invalid_token'] = $this->testIniciarParqueo($invalidTokenData);
        
        // Prueba 4: IniciarParqueo con identificador inválido
        echo "🧪 Probando iniciarParqueo con identificador inválido...\n";
        $invalidIdData = [
            'token' => 'dc2fec0f5f08fca379553cc7af20d556',
            'plazaId' => 1,
            'zonaId' => 101,
            'identificador' => '123', // Muy corto
            'tiempoParqueo' => 60,
            'importeParqueo' => 1600,
            'passwordCps' => 'test_password',
            'fechaInicioParqueo' => date('Y-m-d H:i:s'),
            'fechaFinParqueo' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'nroTransaccion' => 'TEST_INVALID_ID_' . date('YmdHis'),
            'fechaTransaccion' => date('Y-m-d H:i:s')
        ];
        $results['iniciarParqueo_invalid_id'] = $this->testIniciarParqueo($invalidIdData);
        
        // Resumen de resultados
        echo "\n📊 Resumen de pruebas:\n";
        foreach ($results as $test => $result) {
            $status = $result !== false ? "✅ EXITOSO" : "❌ FALLIDO";
            echo "- {$test}: {$status}\n";
        }
        
        watchDog::logInfo('Batería de pruebas completada', [
            'results_summary' => array_map(function($r) { return $result !== false; }, $results)
        ], 'client_test');
        
        return $results;
    }
}

// Ejemplo de uso
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // Detección automática de entorno y URL
    function detectServiceUrl() {
        // Prioridad 1: Variable de entorno
        $envUrl = getenv('SETEX_SERVICE_URL');
        if ($envUrl) {
            return rtrim($envUrl, '/');
        }
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                    || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        
        // En CLI, probar múltiples opciones
        if (php_sapi_name() === 'cli') {
            $cliOptions = [
                'http://localhost/setex/src/setex-wsdl.php',     // PRINCIPAL - Nombre original
                'http://localhost/src/setex-wsdl.php',          // Local principal
                'http://localhost:8080/src/setex-wsdl.php',     // Docker principal
                'http://localhost/setex/src/testphp.php',       // Dashboard/diagnóstico
                'http://localhost/src/testphp.php'             // Local diagnóstico
            ];
            
            // Probar cada opción
            foreach ($cliOptions as $url) {
                if (@get_headers($url)) {
                    return $url;
                }
            }
            
            // Fallback CLI
            return 'http://localhost/src/testphp.php';
        }
        
        // En web, construir basado en el contexto actual
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $currentPath = dirname($_SERVER['REQUEST_URI'] ?? '/');
        
        // Rutas a probar según el entorno
        $webOptions = [
            $currentPath . '/src/setex-wsdl.php',         // PRINCIPAL - Nombre original
            '/setex/src/setex-wsdl.php',                 // Path absoluto EC2 principal
            $currentPath . '/src/testphp.php',            // Dashboard/diagnóstico
            '/setex/src/testphp.php'                     // EC2 diagnóstico
        ];
        
        // Probar cada ruta
        foreach ($webOptions as $path) {
            $url = $protocol . $host . $path;
            // Verificar si la URL responde (simplificado para evitar timeouts)
            $headers = @get_headers($url, 1);
            if ($headers && strpos($headers[0], '200') !== false) {
                return $url;
            }
        }
        
        // URL por defecto - MANTENER ARQUITECTURA ORIGINAL
        return $protocol . $host . '/src/setex-wsdl.php';
    }
    
    // Configuración automática
    $serviceUrl = detectServiceUrl();
    
    // Mostrar información del entorno
    $isWeb = php_sapi_name() !== 'cli';
    $environment = '';
    
    if (getenv('AWS_REGION')) {
        $environment = '☁️ AWS EC2';
    } elseif (file_exists('/.dockerenv')) {
        $environment = '🐳 Docker';
    } else {
        $environment = '💻 Local';
    }
    
    if ($isWeb) {
        echo "<h1>🧪 SETEX - Cliente de Prueba</h1>";
        echo "<h2>$environment</h2>";
        echo "<p><strong>URL del servicio:</strong> $serviceUrl</p>";
        echo "<hr>";
        echo "<pre>";
    } else {
        echo "🚀 SETEX - Cliente de Prueba ($environment)\n";
        echo "URL del servicio: $serviceUrl\n\n";
    }
    
    try {
        $testClient = new SetexClientTest($serviceUrl);
        $results = $testClient->runFullTest();
        
        if ($isWeb) {
            echo "\n</pre>";
            echo "<hr>";
            echo "<h3>✅ Pruebas completadas</h3>";
            echo "<p>Revisa los logs en el directorio <code>logs/</code> para más detalles.</p>";
            
            // Mostrar enlaces útiles en modo web
            echo "<h3>🔗 Enlaces Útiles:</h3>";
            echo "<ul>";
            echo "<li><a href='check-php8.php' target='_blank'>🔍 Verificación PHP 8</a></li>";
            echo "<li><a href='src/servicio.php?wsdl' target='_blank'>📋 WSDL del Servicio</a></li>";
            echo "<li><a href='docs/como-probar.md' target='_blank'>📖 Documentación</a></li>";
            echo "</ul>";
            
            // Información adicional para EC2
            if ($environment === '☁️ AWS EC2') {
                echo "<h3>💡 EC2 Info:</h3>";
                echo "<ul>";
                echo "<li>Verificar Security Groups para puerto 80</li>";
                echo "<li>Configurar variables de entorno si es necesario</li>";
                echo "<li>Revisar logs: <code>/var/www/html/logs/</code></li>";
                echo "</ul>";
            }
        } else {
            echo "\n✅ Pruebas completadas. Revisa los logs para más detalles.\n";
        }
        
    } catch (Exception $e) {
        $errorMsg = "❌ Error fatal: " . $e->getMessage();
        
        if ($isWeb) {
            echo "\n</pre>";
            echo "<div style='color: red; border: 1px solid red; padding: 10px; background: #ffeeee;'>";
            echo "<h3>❌ Error Fatal</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<h4>🔧 Troubleshooting:</h4>";
            echo "<ul>";
            echo "<li>Verificar que el archivo <code>src/servicio.php</code> existe</li>";
            echo "<li>Verificar conexión a base de datos</li>";
            echo "<li>Revisar logs de errores del servidor</li>";
            echo "<li>Verificar configuración de nuSOAP</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "$errorMsg\n";
            echo "\n🔧 Troubleshooting:\n";
            echo "   - Verificar archivo src/servicio.php\n";
            echo "   - Probar conexión BD: php src/connect.php\n";
            echo "   - Revisar logs de Apache\n";
        }
        
        watchDog::logError('Error fatal en cliente de prueba', [
            'error_message' => $e->getMessage(),
            'service_url' => $serviceUrl,
            'environment' => $environment
        ], 'client_test');
    }
}
?>