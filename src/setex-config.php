<?php
// SETEX Configuration - Compatible con EC2, Docker y desarrollo local
// Usa variables de entorno cuando estén disponibles

// Configuración flexible de rutas y URLs
$servidor = getenv('SETEX_SERVER_HOST') ?: '52.39.146.172';  // IP por defecto EC2
$protocolo = getenv('SETEX_PROTOCOL') ?: 'http';
$proyecto = getenv('SETEX_PROJECT_NAME') ?: 'serviceSetex';

// Configuración de rutas
$conf["rooturl"] = $protocolo . "://" . $servidor . "/" . $proyecto;
$conf["rootpath"] = getenv('SETEX_ROOT_PATH') ?: "/var/www/html/" . $proyecto;
$conf["rootverisign"] = getenv('SETEX_VERISIGN_PATH') ?: "";
$conf["roottemp"] = getenv('SETEX_LOGS_PATH') ?: "/logs";

// Si estamos en desarrollo local, ajustar rutas
if ($servidor === 'localhost' || $servidor === '127.0.0.1') {
    $conf["rootpath"] = getenv('SETEX_ROOT_PATH') ?: dirname(__DIR__);
    $conf["roottemp"] = $conf["rootpath"] . "/logs";
}

// Definir constantes para compatibilidad
define("ROOTPATH", $conf["rootpath"]);
define("LIBSPATH", $conf["rootpath"] . "/libs/");
define("RUTA_LOGS_WS", $conf["roottemp"]);

// Configuración adicional para EC2
if (getenv('AWS_REGION')) {
    // Estamos en EC2, configurar parámetros específicos
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
        'environment' => getenv('ENVIRONMENT') ?: 'production'
    ], 'config');
}
?>
