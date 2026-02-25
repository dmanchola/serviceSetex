<?php
include_once("setex-config.php");
/**
 * Clase de monitoreo de eventos de los Webservices
 * Mejorada con niveles de log y mejor manejo de errores
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

    /**
     * metodo statico para escribir un archivo de log con la informacion del evento
     *
     * @param string $logws_type
     * @param string $logws_text
     * @param string $logws_line
     * @param string $logws_file
     */
    static function writeLogFile($logws_type, $logws_text, $logws_line, $logws_file, $file) {
        $filename = $file . date("Y-m-d") . '.txt';
        $logws_text.=" Archivo:" . $logws_file . " Linea:" . $logws_line . " Fecha:" . date("Y-m-d h:i:s") . "\n";

        try {
            if (file_exists("../logs/".$filename)) {
              $myfile = fopen("../logs/".$filename, "a") or die("Unable to open file!");
              fwrite($myfile, $logws_text);
              fclose($myfile);
            } else {
              $myfile = fopen("../logs/".$filename, "w") or die("Unable to open file!");
              fwrite($myfile, $logws_text);
              fclose($myfile);
            }

        } catch (Exception $e) {
            // Log del error de escritura
            error_log("WatchDog Log Error: " . $e->getMessage());
            
            echo "<hr />";
            echo "Exception code:  <font style='color:blue'>" . $e->getCode() . "</font>";
            echo "<br />";
            echo "Exception message: <font style='color:blue'>" . nl2br($e->getMessage()) . "</font>";
            echo "<br />";
            echo "Thrown by: '" . $e->getFile() . "'";
            echo "<br />";
            echo "on line: '" . $e->getLine() . "'.";
            echo "<br />";
            echo "<br />";
            echo "Stack trace:";
            echo "<br />";
            echo nl2br($e->getTraceAsString());
            echo "<hr />";
        }
    }
    
    /**
     * Log con nivel específico
     */
    static function logWithLevel($level, $message, $context = [], $file = 'system') {
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        $logMessage = "[{$level}] {$message}{$contextStr}";
        
        $trace = debug_backtrace();
        $caller = $trace[1] ?? $trace[0];
        
        self::writeLogFile(
            strtolower($level),
            $logMessage,
            $caller['line'] ?? 0,
            $caller['file'] ?? 'unknown',
            $file
        );
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
