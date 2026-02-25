<?php
// SETEX Configuration - Compatible con EC2, Docker y desarrollo local
// Usa archivo .env para configuración

// Cargar variables de entorno desde .env
require_once(dirname(__FILE__) . '/env-loader.php');

// Configuración flexible usando .env
$servidor = SetexEnvLoader::get('SETEX_SERVER_HOST', '52.39.146.172');
$protocolo = SetexEnvLoader::get('SETEX_PROTOCOL', 'http');
$proyecto = SetexEnvLoader::get('SETEX_PROJECT_NAME', 'serviceSetex');

// Configuración de rutas
$conf["rooturl"] = $protocolo . "://" . $servidor . "/" . $proyecto;
$conf["rootpath"] = SetexEnvLoader::get('SETEX_ROOT_PATH', "/var/www/html/" . $proyecto);
$conf["rootverisign"] = SetexEnvLoader::get('SETEX_VERISIGN_PATH', "");
$conf["roottemp"] = SetexEnvLoader::get('SETEX_LOGS_PATH', "/logs");

// Si estamos en desarrollo local, ajustar rutas
if ($servidor === 'localhost' || $servidor === '127.0.0.1') {
    $conf["rootpath"] = SetexEnvLoader::get('SETEX_ROOT_PATH', dirname(__DIR__));
    $conf["roottemp"] = $conf["rootpath"] . "/logs";
}

// Definir constantes para compatibilidad
define("ROOTPATH", $conf["rootpath"]);
define("LIBSPATH", $conf["rootpath"] . "/libs/");
define("RUTA_LOGS_WS", $conf["roottemp"]);

// Configuración adicional para EC2
if (SetexEnvLoader::get('ENVIRONMENT') === 'production') {
    ini_set('default_socket_timeout', 60);
    ini_set('max_execution_time', 300);
}

// Log de configuración cargada
if (file_exists(dirname(__FILE__) . '/watchdog.php')) {
    require_once(dirname(__FILE__) . '/watchdog.php');
    watchDog::logInfo('Configuración SETEX cargada', [
        'root_url' => $conf["rooturl"],
        'root_path' => $conf["rootpath"],
        'libs_path' => LIBSPATH,
        'logs_path' => RUTA_LOGS_WS,
        'environment' => SetexEnvLoader::get('ENVIRONMENT', 'production'),
        'config_source' => '.env file'
    ], 'config');
}
?>
