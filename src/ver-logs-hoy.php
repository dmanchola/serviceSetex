<?php
/**
 * Lector simple de logs de hoy
 */
echo "<h1>📋 Logs de Hoy - " . date('Y-m-d') . "</h1>";
echo "<style>pre{background:#f5f5f5;padding:10px;border:1px solid #ccc;overflow-x:auto;}</style>";

$logsDir = '../logs';
$today = date('Y-m-d');

// Buscar todos los archivos de hoy
$todayFiles = glob($logsDir . '/*' . $today . '*');

if (empty($todayFiles)) {
    echo "<p>❌ No hay archivos de log de hoy</p>";
} else {
    echo "<p>✅ Archivos encontrados: " . count($todayFiles) . "</p>";
    
    foreach ($todayFiles as $file) {
        $basename = basename($file);
        $size = filesize($file);
        $mtime = date('H:i:s', filemtime($file));
        
        echo "<hr>";
        echo "<h2>📄 {$basename}</h2>";
        echo "<p><strong>Tamaño:</strong> {$size} bytes | <strong>Modificado:</strong> {$mtime}</p>";
        
        $content = file_get_contents($file);
        if (strlen($content) > 5000) {
            // Si es muy largo, mostrar solo el final
            $content = "... (archivo truncado - mostrando últimos 5000 caracteres)\n" . substr($content, -5000);
        }
        
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    }
}

echo "<hr>";
echo "<p><strong>Actualizado:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='javascript:location.reload()'>🔄 Actualizar</a></p>";
?>