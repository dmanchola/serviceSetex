<?php
include_once('setex-config.php');

    function conexion() {
        $dbhost = SetexEnvLoader::get('DB_HOST', 'alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com');
        $dbport = (int) SetexEnvLoader::get('DB_PORT', '3306');
        $dbname = SetexEnvLoader::get('DB_NAME', 'alpha_msj');
        $username = SetexEnvLoader::get('DB_USER', 'userAlphaMsj');
        $password = SetexEnvLoader::get('DB_PASS', 'alpha2000@');

        $conn = new mysqli($dbhost, $username, $password, $dbname, $dbport);

        if ($conn->connect_error) {
            echo "Error in connection: " . $conn->connect_error;
            return false;
        }
        else {
            return $conn;
        }
    }

?>
