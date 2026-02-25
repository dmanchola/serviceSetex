<?php
/**
 * SETEX - Visor de logs en tiempo real
 */

echo "<!DOCTYPE html><html><head><title>SETEX Logs</title>";
echo "<meta http-equiv='refresh' content='5'>"; // Auto-refresh cada 5 segundos
echo "<style>body{font-family:monospace;background:#000;color:#0f0;padding:20px;}";
echo ".error{color:#f00;} .warning{color:#fa0;} .success{color:#0f0;} .info{color:#0af;}";
echo "pre{background:#111;padding:10px;border-radius:5px;overflow-x:auto;}";
echo "</style></head><body>";
echo "<h1>🔍 SETEX - Logs en Tiempo Real</h1>";
echo "<p>⏰ Actualizado: " . date('Y-m-d H:i:s') . " (actualiza cada 5 seg)</p>";

// Buscar logs de hoy
$today = date('Y-m-d');
$logFiles = [
    'soap_service' => "../logs/soap_service{$today}.txt",
    'servicio' => "../logs/servicio{$today}.txt", 
    'iniciarParqueoSetex' => "../logs/iniciarParqueoSetex{$today}.txt",
    'validation' => "../logs/validation{$today}.txt",
    'auth' => "../logs/auth{$today}.txt",
    'security' => "../logs/security{$today}.txt",
    'database' => "../logs/database{$today}.txt"
];

// También buscar logs de ayer por si acaso
$yesterday = date('Y-m-d', strtotime('-1 day'));
$logFiles["soap_service_ayer"] = "../logs/soap_service{$yesterday}.txt";
$logFiles["iniciarParqueoSetex_ayer"] = "../logs/iniciarParqueoSetex{$yesterday}.txt";

echo "<h2>📋 Archivos de log encontrados:</h2>";
foreach ($logFiles as $name => $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<p>✅ <strong>{$name}</strong>: {$file} ({$size} bytes)</p>";
        
        // Mostrar las últimas 20 líneas
        echo "<h3>📄 Últimas líneas de {$name}:</h3>";
        $lines = file($file);
        $lastLines = array_slice($lines, -20);
        
        echo "<pre>";
        foreach ($lastLines as $line) {
            $line = htmlspecialchars($line);
            // Colorear según el tipo
            if (strpos($line, 'ERROR') !== false) {
                echo "<span class='error'>{$line}</span>";
            } elseif (strpos($line, 'WARNING') !== false) {
                echo "<span class='warning'>{$line}</span>";
            } elseif (strpos($line, 'SUCCESS') !== false) {
                echo "<span class='success'>{$line}</span>";
            } elseif (strpos($line, 'INFO') !== false) {
                echo "<span class='info'>{$line}</span>";
            } else {
                echo $line;
            }
        }
        echo "</pre><hr>";
    } else {
        echo "<p>❌ <strong>{$name}</strong>: {$file} - No existe</p>";
    }
}

// Mostrar logs del sistema PHP
echo "<h2>🐛 Logs del sistema PHP:</h2>";
$phpErrorLog = ini_get('error_log');
if ($phpErrorLog && file_exists($phpErrorLog)) {
    echo "<p>✅ PHP Error Log: {$phpErrorLog}</p>";
    $phpLines = file($phpErrorLog);
    $recentPhpLines = array_slice($phpLines, -10);
    echo "<pre>";
    foreach ($recentPhpLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>❌ PHP Error Log no encontrado o no configurado</p>";
}

// Test rápido de escritura de logs
echo "<h2>🧪 Test de escritura de logs:</h2>";
try {
    // Probar escribir un log de prueba
    $testLogFile = "../logs/test_" . date('Y-m-d') . ".txt";
    $testMessage = "TEST LOG - " . date('Y-m-d H:i:s') . " - Visor de logs funcionando\n";
    
    if (file_put_contents($testLogFile, $testMessage, FILE_APPEND | LOCK_EX)) {
        echo "<p>✅ Escritura de logs funcionando correctamente</p>";
        echo "<p>Archivo test: {$testLogFile}</p>";
    } else {
        echo "<p>❌ Error escribiendo logs</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Excepción escribiendo logs: " . $e->getMessage() . "</p>";
}

// Información del directorio de logs
echo "<h2>📂 Información del directorio de logs:</h2>";
$logsDir = '../logs';
if (is_dir($logsDir)) {
    echo "<p>✅ Directorio de logs existe: {$logsDir}</p>";
    echo "<p>Permisos: " . substr(sprintf('%o', fileperms($logsDir)), -4) . "</p>";
    
    // Archivos más recientes
    $files = glob($logsDir . '/*');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    echo "<h3>📄 Archivos más recientes:</h3>";
    echo "<ul>";
    for ($i = 0; $i < min(10, count($files)); $i++) {
        $file = $files[$i];
        $basename = basename($file);
        $mtime = date('Y-m-d H:i:s', filemtime($file));
        $size = filesize($file);
        echo "<li><strong>{$basename}</strong> - {$mtime} ({$size} bytes)</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Directorio de logs no existe</p>";
}

echo "</body></html>";
?>