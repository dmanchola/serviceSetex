<?php
include_once("setex-config.php");
/**
 * Clase de monitoreo de eventos de los Webservices
 * Sistema de logging unificado con transaction ID
 */
Class watchDog {

    public $logws_type = FALSE;
    public $logws_text = FALSE;
    
    // Niveles de log
    const LOG_ERROR = 'ERROR';
    const LOG_WARNING = 'WARNING';
    const LOG_INFO = 'INFO';
    const LOG_DEBUG = 'DEBUG';
    const LOG_SUCCESS = 'SUCCESS';
    
    // Log unificado
    const UNIFIED_LOG_FILE = 'setex-info.log';
    const LOG_RETENTION_DAYS = 8;
    
    /**
     * Genera un ID único de transacción para seguimiento
     */
    static function generateTransactionId() {
        try {
            return substr(uniqid(), -6) . sprintf('%03d', rand(0, 999));
        } catch (Exception $e) {
            // Fallback simple
            return substr(md5(microtime()), 0, 9);
        }
    }

    /**
     * Escribe en el log unificado - COMPLETAMENTE OPCIONAL 
     */
    static function writeUnifiedLog($level, $message, $context, $line, $file, $transactionId = null) {
        // Completamente opcional - si falla, no debe afectar nada
        try {
            $logsPath = '../logs';
            if (function_exists('getenv')) {
                $envPath = @getenv('SETEX_LOGS_PATH');
                if (!empty($envPath)) {
                    $logsPath = $envPath;
                }
            }
            
            $logsPath = @rtrim($logsPath, '/') . '/';
            $logFile = $logsPath . self::UNIFIED_LOG_FILE;
            
            // Solo proceder si el directorio es escribible o se puede crear
            if (@is_dir($logsPath) || @mkdir($logsPath, 0777, true)) {
                // Rotación opcional
                if (@file_exists($logFile)) {
                    $fileAge = time() - @filemtime($logFile);
                    if ($fileAge > (self::LOG_RETENTION_DAYS * 24 * 60 * 60)) {
                        @unlink($logFile);
                    }
                }
                
                // Generar transaction ID simple
                if (empty($transactionId)) {
                    $transactionId = @substr(@uniqid(), -6);
                }
                
                // Formato simple y seguro 
                $timestamp = @date('Y-m-d H:i:s');
                $contextStr = '{}';
                if (!empty($context) && @is_array($context)) {
                    $contextStr = @json_encode($context);
                    if ($contextStr === false) {
                        $contextStr = '{}';
                    }
                }
                $fileName = @basename($file);
                
                $logLine = "[{$timestamp}] [{$level}] [tx:{$transactionId}] {$message} | {$contextStr} | {$fileName}:{$line}\n";
                
                // Escribir solo si es posible
                @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            // Completamente silencioso - no debe impactar funcionalidad
        }
    }
    
    /**
     * metodo statico para escribir un archivo de log con la informacion del evento
     * DEPRECATED: Mantener compatibilidad, redirige a log unificado
     */
    static function writeLogFile($logws_type, $logws_text, $logws_line, $logws_file, $file) {
        // Convertir al sistema unificado
        $level = strtoupper($logws_type);
        $message = $logws_text;
        $context = ['legacy_file' => $file, 'legacy_type' => $logws_type];
        
        // Usar log unificado 
        self::writeUnifiedLog($level, $message, $context, $logws_line, $logws_file);
    }
    
    /**
     * Log con nivel específico
     */
    static function logWithLevel($level, $message, $context = [], $file = 'system') {
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        $logMessage = "[{$level}] {$message}{$contextStr}";
        
        $trace = debug_backtrace();
        $caller = $trace[1] ?? $trace[0];
        
        // Comportamiento original - MANTENER SIEMPRE
        self::writeLogFile(
            strtolower($level),
            $logMessage,
            $caller['line'] ?? 0,
            $caller['file'] ?? 'unknown',
            $file
        );
        
        // BONUS: También escribir al log unificado si está disponible
        try {
            if (method_exists('watchDog', 'writeUnifiedLog')) {
                @self::writeUnifiedLog($level, $message, $context, $caller['line'] ?? 0, $caller['file'] ?? 'unknown', null);
            }
        } catch (Exception $e) {
            // Ignorar errores del log unificado - no debe afectar funcionalidad original
        }
    }
    
    /**
     * Log de errores
     */
    static function logError($message, $context = [], $file = 'errors') {
        self::logWithLevel(self::LOG_ERROR, $message, $context, $file);
    }
    
    /**
     * Log de advertencias
     */
    static function logWarning($message, $context = [], $file = 'warnings') {
        self::logWithLevel(self::LOG_WARNING, $message, $context, $file);
    }
    
    /**
     * Log de información
     */
    static function logInfo($message, $context = [], $file = 'info') {
        self::logWithLevel(self::LOG_INFO, $message, $context, $file);
    }
    
    /**
     * Log de debug
     */
    static function logDebug($message, $context = [], $file = 'debug') {
        self::logWithLevel(self::LOG_DEBUG, $message, $context, $file);
    }
    
    /**
     * Log de operaciones exitosas
     */
    static function logSuccess($message, $context = [], $file = 'success') {
        self::logWithLevel(self::LOG_SUCCESS, $message, $context, $file);
    }
    
    /**
     * Log de validación de parámetros
     */
    static function logValidation($result, $params = [], $file = 'validation') {
        $level = $result === 0 ? self::LOG_SUCCESS : self::LOG_ERROR;
        $message = $result === 0 ? 'Validación exitosa' : "Error de validación: código {$result}";
        self::logWithLevel($level, $message, $params, $file);
    }
    
    /**
     * Log de operaciones de base de datos
     */
    static function logDatabase($operation, $query, $success, $error = null, $file = 'database') {
        $level = $success ? self::LOG_SUCCESS : self::LOG_ERROR;
        $message = $success ? "DB {$operation} exitosa" : "DB {$operation} falló: {$error}";
        $context = ['query' => substr($query, 0, 200), 'success' => $success];
        if ($error) {
            $context['error'] = $error;
        }
        self::logWithLevel($level, $message, $context, $file);
    }
    
    /**
     * Log de autenticación
     */
    static function logAuth($token, $success, $file = 'auth') {
        $level = $success ? self::LOG_SUCCESS : self::LOG_WARNING;
        $message = $success ? 'Autenticación exitosa' : 'Fallo de autenticación';
        $context = ['token_provided' => !empty($token), 'success' => $success];
        self::logWithLevel($level, $message, $context, $file);
    }
}

?>
