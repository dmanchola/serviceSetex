<?php
// SETEX SOAP Web Service - Punto de entrada principal
// Compatible con PHP 8 y despliegue en EC2
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', '0');

// Log de acceso al servicio
if (file_exists('watchdog.php')) {
    require_once('watchdog.php');
    watchDog::logInfo('Acceso al dashboard SETEX', [
        'php_version' => PHP_VERSION,
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        'note' => 'Dashboard de diagnóstico - Arquitectura principal: setex-wsdl.php'
    ], 'dashboard_access');
}

// NOTA: Este archivo es un DASHBOARD de diagnóstico solamente
// La arquitectura principal permanece: Cliente SOAP → setex-wsdl.php → servicio.class.php
// No se interceptan peticiones SOAP aquí para preservar la arquitectura original

$isWebAccess = true; // Siempre mostrar dashboard web
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SETEX Web Service</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; padding: 15px; background-color: #ecf0f1; border-radius: 5px; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .status.ok { background-color: #d5f4e6; color: #27ae60; border-left: 4px solid #27ae60; }
        .status.error { background-color: #fadbd8; color: #e74c3c; border-left: 4px solid #e74c3c; }
        .status.warning { background-color: #fdf2e9; color: #e67e22; border-left: 4px solid #e67e22; }
        code { background-color: #34495e; color: white; padding: 2px 6px; border-radius: 3px; }
        pre { background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .wsdl-link { background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .wsdl-link:hover { background-color: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 SETEX Web Service - Dashboard</h1>
            <p>Sistema de Parqueo - Diagnóstico y Monitoreo</p>
            <p><small>⚠️ Este es un dashboard de diagnóstico. El endpoint SOAP principal es: <strong>old_setex-wsdl.php</strong></small></p>
        </div>

        <?php
        // Verificar PHP version
        $phpVersion = PHP_VERSION;
        $isPhp8Compatible = version_compare($phpVersion, '8.0.0', '>=');
        ?>

        <div class="section">
            <h3>📊 Estado del Sistema</h3>
            <div class="status <?php echo $isPhp8Compatible ? 'ok' : 'warning'; ?>">
                <strong>PHP Version:</strong> <?php echo $phpVersion; ?>
                <?php if ($isPhp8Compatible): ?>
                    ✓ Compatible con PHP 8
                <?php else: ?>
                    ⚠️ Versión legacy - considerar actualización
                <?php endif; ?>
            </div>

            <?php
            // Verificar extensiones necesarias
            $extensionsCheck = [
                'mysqli' => extension_loaded('mysqli'),
                'soap' => extension_loaded('soap'),
                'xml' => extension_loaded('xml'),
                'simplexml' => extension_loaded('simplexml')
            ];
            
            foreach ($extensionsCheck as $ext => $loaded):
            ?>
            <div class="status <?php echo $loaded ? 'ok' : 'error'; ?>">
                <strong>Extensión <?php echo strtoupper($ext); ?>:</strong> 
                <?php echo $loaded ? '✓ Cargada' : '❌ No disponible'; ?>
            </div>
            <?php endforeach; ?>

            <?php
            // Verificar archivos necesarios
            $files = [
                'setex-config.php' => file_exists('setex-config.php'),
                'servicio.class.php' => file_exists('servicio.class.php'),
                'conexion.class.php' => file_exists('conexion.class.php'),
                'connect.php' => file_exists('connect.php'),
                'watchdog.php' => file_exists('watchdog.php'),
                'libs/nusoap/lib/nusoap.php' => file_exists('../libs/nusoap/lib/nusoap.php') || file_exists('libs/nusoap/lib/nusoap.php')
            ];
            
            foreach ($files as $file => $exists):
            ?>
            <div class="status <?php echo $exists ? 'ok' : 'error'; ?>">
                <strong><?php echo $file ?>:</strong> 
                <?php echo $exists ? '✓ Disponible' : '❌ No encontrado'; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h3>🔧 Servicios Disponibles</h3>
            <ul>
                <li><strong>iniciarParqueo:</strong> Inicia una sesión de parqueo</li>
                <li><strong>getVersion:</strong> Obtiene la versión del servicio</li>
            </ul>
            
            <a href="?wsdl" class="wsdl-link">📋 Ver WSDL</a>
        </div>

        <div class="section">
            <h3>🌐 URLs del Servicio</h3>
            <p><strong>🔥 Endpoint SOAP Principal:</strong></p>
            <pre><?php echo str_replace('/testphp.php', '/setex-wsdl.php', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?></pre>
            
            <p><strong>📋 WSDL Principal:</strong></p>
            <pre><?php echo str_replace('/testphp.php', '/setex-wsdl.php?wsdl', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?></pre>
            
            <p><strong>🔧 Dashboard Diagnóstico (este archivo):</strong></p>
            <pre><?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></pre>
        </div>

        <div class="section">
            <h3>📝 Ejemplo de Uso</h3>
            <p><strong>Endpoint SOAP Principal:</strong> Use <code>setex-wsdl.php</code> para todas las peticiones SOAP</p>
            <p><strong>Arquitectura:</strong> Cliente SOAP → setex-wsdl.php → servicio.class.php → Base de datos</p>
            <p>Este dashboard es solo para diagnóstico. Los clientes SOAP deben conectar directamente al endpoint principal.</p>
        </div>

        <?php if (function_exists('watchDog::logInfo')): ?>
        <div class="section">
            <h3>📊 Sistema de Logs</h3>
            <div class="status ok">
                <strong>Sistema de logs:</strong> ✓ Activo y funcionando
            </div>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h3>ℹ️ Información Técnica</h3>
            <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
            <p><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Environment:</strong> <?php echo getenv('ENVIRONMENT') ?: 'Production'; ?></p>
        </div>
    </div>
</body>
</html>