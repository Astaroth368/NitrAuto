<?php
include_once('PacketType.php');

class RconPacket
{
    private static int $idCounter = 1;

    public $Id;
    public int $Type;
    public $Body;

    public function GetContentSize()
    {
        //Return the length of the packet exluding the Size byte
        return strlen($this) - 4;
    }

    public function __construct($type, $body, $id = -1)
    {
        ($id == -1 ? $this->Id = RconPacket::$idCounter++ : $this->Id = $id);
        $this->Type = $type;
        $this->Body = $body;
    }

    public function __toString(): string
    {
        $packet = pack('V2a*', $this->Id, $this->Type, $this->Body);
        $packet = $packet . "\x00\x00"; //Null string terminators
        $packet = pack('V', strlen($packet)) . $packet; //Prepend with size of packet (exclusing the size byte itself)
        return $packet;
    }

    public static function ConvertToRconPacket(string $data): ?RconPacket
    {
        $unpacked = unpack('V1id/V1type/a*body', $data);
        if ($unpacked !== false) return new RconPacket($unpacked['type'], $unpacked['body'], $unpacked['id']);
        return null;
    }
}

?>