<?php
include_once('../NitrAutoConfig.php');
include_once('RconPacket.php');

//TODO: Better error handling. Return error from constructor and handle in main app.
if (!extension_loaded('sockets')) {
    die('The sockets extension is not loaded.');
}

class Rcon
{
    private $host;
    private $port;
    private $password;
    private $socket;
    private $authenticated = false;

    public function __construct($host, $port, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;

        //TODO: Validate parameters
    }

    function IsConnected()
    {
        return $this->authenticated;
    }

    public function Connect()
    {
        echo "Connecting<br />";
        $this->Disconnect();

        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, NitrAutoConfig::RCONTimeout);

        if ($this->socket === false)
        {
            echo "fsockopen() failed: " . $errstr . "<br />";
            return false;
        }

        echo "Socket created<br />";

        stream_set_timeout($this->socket, NitrAutoConfig::RCONTimeout, 0);

        return $this->Authenticate();
    }

    public function Authenticate()
    {
        echo "Authenticating...<br />";
        $authPacket = new RconPacket(PacketType::ServerAuth->value, $this->password);
        $response = $this->SendReceive($authPacket);

        $this->authenticated = (!empty($response) && $response->Id == $authPacket->Id ? true : false);

        return $this->authenticated;
    }

    public function SendCommand(string $command): ?RconPacket
    {
        //TODO: Validate command
        if (!$this->IsConnected()) $this->Connect();
        if (!$this->IsConnected()) return null;
        return $this->SendReceive(new RconPacket(PacketType::ServerAuthResponseOrExecCommand->value, $command));
    }

    public function SendServerChat(string $message): ?RconPacket
    {
        return $this->SendCommand("serverchat " . $message);
    }

    public function SendBroadcast(string $message): ?RconPacket
    {
        return $this->SendCommand("broadcast " . $message);
    }

    public function WipeWildDinos(): ?RconPacket
    {
        $this->SaveWorld(); //Save before wiping dinos in case of server crash etc.
        $this->SendServerChat("Wiping wild dinos..."); //Inform the players
        return $this->SendCommand("DestroyWildDinos"); //Destroy wild dinos
    }

    public function SaveWorld(): ?RconPacket
    {
        $this->SendServerChat("Performing world save...");
        return $this->SendCommand("SaveWorld");
    }

    public function SendReceive(RconPacket $packet) : ?RconPacket
    {
        //TODO: Validate $packet

        if (is_null($this->socket)) return null;

        if (fwrite($this->socket, $packet, strlen($packet)) !== false)
        {

            $i = 1;
            do
            {
                echo "Reading Response<br />";
                //usleep(50); //Testing
                //Read size of packet being received
                $sizeData = fread($this->socket, 4);
                (strlen($sizeData) > 0 ? $size = unpack('V1size', $sizeData)['size'] : $size = 0);

                //Parse packet
                if ($size > 0)
                {
                    $data = fread($this->socket, $size);
                    $response = unpack('V1id/V1type/a*body', $data);
                    $responsePacket = new RconPacket($response['type'], $response['body'], $response['id']);
                }
            } while ((empty($response) || $response['id'] != $packet->Id) && $i++ < 3);

            // echo "Response:" . var_dump((!empty($responsePacket) ? $responsePacket : null));
            return (!empty($responsePacket) ? $responsePacket : null);

        } else
        {
            echo "Failed to send packet " . socket_last_error($this->socket) . "<br />";
        }

        return null;
    }

    public function Disconnect()
    {
        if (!is_null($this->socket))
        {
            fclose($this->socket);
            $this->authenticated = false;
        }
    }

}

?>