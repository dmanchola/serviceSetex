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
        return substr(uniqid(), -6) . sprintf('%03d', rand(0, 999));
    }

    /**
     * Escribe en el log unificado con rotación automática
     */
    static function writeUnifiedLog($level, $message, $context, $line, $file, $transactionId = null) {
        // Cargar configuración
        if (!getenv('SETEX_LOGS_PATH') && file_exists('env-loader.php')) {
            try {
                require_once('env-loader.php');
                SetexEnvLoader::load();
            } catch (Exception $e) {
                // Ignorar errores de carga
            }
        }
        
        $logsPath = getenv('SETEX_LOGS_PATH') ?: './logs';
        $logsPath = rtrim($logsPath, '/') . '/';
        $logFile = $logsPath . self::UNIFIED_LOG_FILE;
        
        // Crear directorio si no existe
        if (!is_dir($logsPath)) {
            @mkdir($logsPath, 0777, true);
        }
        
        // Rotación simple: verificar edad del archivo
        if (file_exists($logFile)) {
            $fileAge = time() - filemtime($logFile);
            if ($fileAge > (self::LOG_RETENTION_DAYS * 24 * 60 * 60)) {
                @unlink($logFile); // Eliminar archivo viejo
            }
        }
        
        // Generar transaction ID si no se proporciona
        if (!$transactionId) {
            $transactionId = self::generateTransactionId();
        }
        
        // Formato unificado: [timestamp] [LEVEL] [tx:id] mensaje | context | file:line
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '{}' : json_encode($context);
        $fileName = basename($file);
        
        $logLine = "[{$timestamp}] [{$level}] [tx:{$transactionId}] {$message} | {$contextStr} | {$fileName}:{$line}\n";
        
        try {
            if (is_writable(dirname($logFile)) || !file_exists($logFile)) {
                file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            error_log("WatchDog Unified Log Error: " . $e->getMessage());
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
     * Log con nivel específico - VERSION UNIFICADA
     */
    static function logWithLevel($level, $message, $context = [], $transactionId = null) {
        $trace = debug_backtrace();
        $caller = $trace[1] ?? $trace[0];
        
        // Usar log unificado
        self::writeUnifiedLog(
            $level,
            $message,
            $context,
            $caller['line'] ?? 0,
            $caller['file'] ?? 'unknown',
            $transactionId
        );
    }
    
    /**
     * Log de errores
     */
    static function logError($message, $context = [], $transactionId = null) {
        self::logWithLevel(self::LOG_ERROR, $message, $context, $transactionId);
    }
    
    /**
     * Log de advertencias
     */
    static function logWarning($message, $context = [], $transactionId = null) {
        self::logWithLevel(self::LOG_WARNING, $message, $context, $transactionId);
    }
    
    /**
     * Log de información
     */
    static function logInfo($message, $context = [], $transactionId = null) {
        self::logWithLevel(self::LOG_INFO, $message, $context, $transactionId);
    }
    
    /**
     * Log de debug
     */
    static function logDebug($message, $context = [], $transactionId = null) {
        self::logWithLevel(self::LOG_DEBUG, $message, $context, $transactionId);
    }
    
    /**
     * Log de operaciones exitosas
     */
    static function logSuccess($message, $context = [], $transactionId = null) {
        self::logWithLevel(self::LOG_SUCCESS, $message, $context, $transactionId);
    }
    
    /**
     * Log de validación de parámetros
     */
    static function logValidation($result, $params = [], $transactionId = null) {
        $level = $result === 0 ? self::LOG_SUCCESS : self::LOG_ERROR;
        $message = $result === 0 ? 'Validación exitosa' : "Error de validación: código {$result}";
        self::logWithLevel($level, $message, $params, $transactionId);
    }
    
    /**
     * Log de operaciones de base de datos
     */
    static function logDatabase($operation, $query, $success, $error = null, $transactionId = null) {
        $level = $success ? self::LOG_SUCCESS : self::LOG_ERROR;
        $message = $success ? "DB {$operation} exitosa" : "DB {$operation} falló: {$error}";
        $context = ['query' => substr($query, 0, 200), 'success' => $success];
        if ($error) {
            $context['error'] = $error;
        }
        self::logWithLevel($level, $message, $context, $transactionId);
    }
    
    /**
     * Log de autenticación
     */
    static function logAuth($token, $success, $transactionId = null) {
        $level = $success ? self::LOG_SUCCESS : self::LOG_WARNING;
        $message = $success ? 'Autenticación exitosa' : 'Fallo de autenticación';
        $context = ['token_provided' => !empty($token), 'success' => $success];
        self::logWithLevel($level, $message, $context, $transactionId);
    }
}

?>
