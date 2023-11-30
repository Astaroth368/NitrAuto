<?php

enum PacketType : int
{
    case ServerAuth = 3;
    case ServerAuthResponseOrExecCommand = 2;
    case ServerResponseValue = 0;
}

?>