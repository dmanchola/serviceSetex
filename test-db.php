<?php
// Test rápido de conexión a base de datos
require_once('src/setex-config.php');

echo "=== TEST DE CONEXIÓN A BASE DE DATOS ===\n";

try {
    // Probar con las credenciales actuales
    $conn = new mysqli(
        "alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com",
        "userAlphaMsj",
        "alpha2000@",
        "alpha_msj",
        3306
    );

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