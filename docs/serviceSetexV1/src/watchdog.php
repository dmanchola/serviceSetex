<?php
include_once("setex-config.php");
/**
 * Clase de monitoreo de eventos de los Webservices
 */
Class watchDog {

    public $logws_type = FALSE;
    public $logws_text = FALSE;

    /**
     * metodo statico para escribir un archivo de log con la informacion del evento
     *
     * @param string $logws_type
     * @param string $logws_text
     * @param string $logws_line
     * @param string $logws_file
     */
    static function writeLogFile($logws_type, $logws_text, $logws_line, $logws_file, $file) {
        $filename = $file . date("Y-m-d") . '.txt';
        $logws_text.=" Archivo:" . $logws_file . " Linea:" . $logws_line . " Fecha:" . date("Y-m-d h:i:s") . "\n";

        try {
            if (file_exists("../logs/".$filename)) {
              $myfile = fopen("../logs/".$filename, "a") or die("Unable to open file!");
              fwrite($myfile, $logws_text);
              fclose($myfile);
            } else {
              $myfile = fopen("../logs/".$filename, "w") or die("Unable to open file!");
              fwrite($myfile, $logws_text);
              fclose($myfile);
            }

        } catch (Exception $e) {

            echo "<hr />";
            echo "Exception code:  <font style='color:blue'>" . $e->getCode() . "</font>";
            echo "<br />";
            echo "Exception message: <font style='color:blue'>" . nl2br($e->getMessage()) . "</font>";
            echo "<br />";
            echo "Thrown by: '" . $e->getFile() . "'";
            echo "<br />";
            echo "on line: '" . $e->getLine() . "'.";
            echo "<br />";
            echo "<br />";
            echo "Stack trace:";
            echo "<br />";
            echo nl2br($e->getTraceAsString());
            echo "<hr />";
        }
    }
}

?>
