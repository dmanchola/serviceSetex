<?php
include_once('setex-config.php');
include_once(LIBSPATH.'adodb/adodb.inc.php');

    function conexion() {
        $dbhost = "alpha-msj-db-server.celntjvopzqm.us-west-2.rds.amazonaws.com";
        $dbport = "3306";
        $dbname = "alpha_msj";
        $charset = 'utf8';

        $dsn = "mysql:host={$dbhost};port={$dbport};dbname={$dbname};charset={$charset}";
        $username = "userAlphaMsj";
        $password = "%kv3JCZn<CRN*F+b";

        $conn = new mysqli($dbhost, $username, $password, $dbname, 3306);

        if ($conn->connect_error) {
            echo "Error in connection: " . $conn->connect_error;
            return false;
        }
        else {
            return $conn;
        }
    }

?>
