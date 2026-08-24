<?php
include_once('setex-config.php');
include_once('watchdog.php');
include_once('conexion.class.php');

$conn = conexion();

if ($conn) {
    echo "Conexion exitosa";
    $conn->close();
} else {
    echo "Fallo la conexion - revisar log conexionSQL" . date("Y-m-d") . ".txt";
}
?>
