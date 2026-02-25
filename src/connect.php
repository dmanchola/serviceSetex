<?php
	$dbhost = "alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com";
	$dbport = "3306";
	$dbname = "alpha_msj";
	$charset = 'utf8';

	$dsn = "mysql:host={$dbhost};port={$dbport};dbname={$dbname};charset={$charset}";
	$username = "userAlphaMsj";
	$password = "alpha2000@";

	$mysqli_connection = new mysqli($dbhost, $username, $password, $dbname, 3306);
	echo "1";
	if ($mysqli_connection->connect_error) {
   		echo "Not connected, error: " . $mysqli_connection->connect_error;
	}
	else {
   		echo "Connected.";
	}
?>
