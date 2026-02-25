<?php
/**
 * SETEX Environment Loader
 * Carga variables de entorno desde archivo .env
 */

class SetexEnvLoader {
    
    private static $loaded = false;
    
    /**
     * Carga variables desde archivo .env
     */
    public static function load($envFile = null) {
        if (self::$loaded) {
            return;
        }
        
        // Determinar ruta del archivo .env
        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }
        
        // Si no existe .env, intentar con .env.example
        if (!file_exists($envFile)) {
            $exampleFile = dirname(__DIR__) . '/.env.example';
            if (file_exists($exampleFile)) {
                $envFile = $exampleFile;
            }
        }
        
        // Cargar archivo si existe
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentarios
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parsear línea KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remover comillas si existen
                    if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                        (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                        $value = substr($value, 1, -1);
                    }
                    
                    // Solo establecer si no existe ya como variable de entorno del sistema
                    if (!getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtiene valor de variable de entorno con fallback
     */
    public static function get($key, $default = null) {
        self::load();
        
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $default;
        }
        
        return $value;
    }
    
    /**
     * Obtiene valor booleano
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }
    
    /**
     * Obtiene valor entero
     */
    public static function getInt($key, $default = 0) {
        $value = self::get($key);
        return $value !== null ? (int)$value : $default;
    }
}

// Auto-cargar al incluir este archivo
SetexEnvLoader::load();
?>