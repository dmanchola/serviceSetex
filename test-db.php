<?php
// Test rápido de conexión a base de datos
require_once('src/setex-config.php');

echo "=== TEST DE CONFIGURACIÓN Y CONEXIÓN ===\n";

// Verificar carga del .env
echo "📋 VARIABLES DE ENTORNO:\n";
echo "SETEX_LOGS_PATH: " . (getenv('SETEX_LOGS_PATH') ?: 'NO DEFINIDA') . "\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NO DEFINIDA') . "\n";
echo "ENVIRONMENT: " . (getenv('ENVIRONMENT') ?: 'NO DEFINIDA') . "\n";
echo "\n";

try {
    // Probar con las credenciales del .env
    $dbHost = getenv('DB_HOST') ?: "alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com";
    $dbUser = getenv('DB_USER') ?: "userAlphaMsj"; 
    $dbPass = getenv('DB_PASS') ?: "alpha2000@";
    $dbName = getenv('DB_NAME') ?: "alpha_msj";
    $dbPort = getenv('DB_PORT') ?: 3306;
    
    echo "📞 INTENTANDO CONEXIÓN CON:\n";
    echo "Host: $dbHost\n";
    echo "User: $dbUser\n";  
    echo "DB: $dbName\n";
    echo "Port: $dbPort\n\n";

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

    if ($conn->connect_error) {
        echo "❌ ERROR DE CONEXIÓN: " . $conn->connect_error . "\n";
        exit(1);
    } else {
        echo "✅ CONEXIÓN EXITOSA\n";
        echo "✅ Servidor MySQL: " . $conn->server_info . "\n";
        echo "✅ Versión cliente: " . $conn->client_info . "\n";
        
        // Probar una consulta simple
        $result = $conn->query("SELECT 1 as test");
        if ($result) {
            echo "✅ CONSULTA DE PRUEBA: OK\n";
            $result->close();
        } else {
            echo "❌ ERROR EN CONSULTA: " . $conn->error . "\n";
        }
        
        $conn->close();
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>