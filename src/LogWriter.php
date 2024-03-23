<?php
include_once("../NitrAutoConfig.php");

class LogWriter
{
    private const LOG_FILE_PATH = "../tmp/cronlog.txt";
    private $logFile;

    public function __construct()
    {
        $this->OpenLogFile();
    }

    public function __destruct()
    {
        $this->CloseLogFile();
    }

    private function OpenLogFile()
    {
        $this->logFile = fopen(self::LOG_FILE_PATH, 'a+');
    }
    
    public function LogMessage($message)
    {
        fwrite($this->logFile, "[" . date("Y-m-d H:i:s") . "] $message\n"); 
        echo (NitrAutoConfig::DEBUG ? $message . "<br />" : "");
    }

    public function WriteDebug($message)
    {
        if (NitrAutoConfig::DEBUG)
        {
            fwrite($this->logFile, "[" . date("Y-m-d H:i:s") . "] DEBUG: $message\n"); 
            echo (NitrAutoConfig::DEBUG ? "DEBUG: " . $message . "<br />" : "");
        }
    }
    
    private function CloseLogFile()
    {
        if (!is_null($this->logFile))
        {
            fclose($this->logFile);
        }
    }
}

?>