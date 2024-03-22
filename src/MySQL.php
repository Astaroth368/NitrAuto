<?php
include_once("../NitrAutoConfig.php");

class MySQLServer
{
    private $sqlServer;

    public function Connect()
    {
        $this->sqlServer = @new mysqli(NitrAutoConfig::Host, NitrAutoConfig::Username, NitrAutoConfig::Password);
        $this->sqlServer->select_db(NitrAutoConfig::Database);
        return ($this->sqlServer->connect_error ? false : true);
    }

    public function Disconnect()
    {
        $this->sqlServer->close();
    }

    public function Query($query)
    {
        //TODO: Validate query
        $result = $this->sqlServer->query($query);
        if ($result->num_rows > 0)
        {
            return $row = $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }

    public function GetScheduledTasks()
    {
        $query = "SELECT * FROM scheduledtasks WHERE Enabled = 1";
        return $this->sqlServer->Query($query);
    }

    public function GetDynamicConfig(int $configID)
    {
        $query = "SELECT * FROM dynamicconfigs WHERE ID = $configID LIMIT 1";
        return $this->Query($query)[0];
    }

    public function GetGameServers()
    {
        $query = "SELECT * FROM servers";
        return $this->Query($query);
    }
}
?>