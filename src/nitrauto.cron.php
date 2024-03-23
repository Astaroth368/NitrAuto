<?php
include_once('LogWriter.php');
include_once('MySQL.php');
include_once('Rcon.php');
include_once('vendor/autoload.php');

$logWriter = new LogWriter();

try
{
    $sql = new MySQLServer();
    $rconServers = array();

    if ($sql->Connect())
    {
        $tasks = $sql->GetScheduledTasks();
        foreach ($tasks as $task)
        {
            //If this is a one off task, we can ignore the Hour and Minute columns
            if ($task['OneOffTime'] !== null)
            {
                $taskTime = strtotime($task['OneOffTime']);
            }
            //If the scheduled hour has already passed, check the following day
            //E.g. If the current time is 23:30 there could be a task scheduled at 00:00 tomorrow (I.e. in 30 minutes)
            else if (date("H") > $task['Hour'])
            {
                $taskTime = strtotime(date("Y-m-") . date("d", time() + 24 * 60 * 60) . $task['Hour'] . ":" . $task['Minute'] . ":00");
            } else
            {
                $taskTime = strtotime(date("Y-m-d ") . $task['Hour'] . ":" . $task['Minute'] . ":00");
            }

            //Check if the task is scheduled to run on the day in $taskTime
            //Bit field format: 0 Sun Sat Fri Thur Wed Tue Mon
            if ($task['DaysOfWeek'] & (1 << (int)date("N", $taskTime) - 1)
            || ($task['OneOffTime'] !== null && $taskTime >= time() && $taskTime <= time() + 24 * 60 * 60))
            {
                foreach (explode(",", $task['MessageAtMinute']) as $mins)
                {
                    $alertTime = $taskTime - ($mins * 60); //Calculate the time X minutes before task time
                    $curTime = strtotime(date("Y-m-d H:i:00")); //Remove any seconds from the current time
        
                    //If an alert should be sent now
                    if ($alertTime == $curTime
                    || ($curTime > $alertTime - (NitrAutoConfig::CRONIntervalSeconds / 2)
                    && $curTime < $alertTime + (NitrAutoConfig::CRONIntervalSeconds / 2)))
                    {
                        $logWriter->LogMessage("Alert is due now");

                        //Get Nitrado game servers
                        $nitrAPI = new \Nitrapi\Nitrapi(NitrAutoConfig::NitrAPIToken); //Prepare Nitrado API/NitrAPI connection
                        $nServers = $nitrAPI->getServices();

                        //Get discovered game servers
                        $dServers = $sql->GetGameServers();

                        foreach ($nServers as $nServer)
                        {
                            $details = $nServer->getDetails();
                            $address = $details->getIP();
                            $port = $details->getPort();
                            $rconPort = $details->getRconPort();

                            foreach ($dServers as $dServer)
                            {
                                if ($dServer['Address'] == $address && $dServer['GamePort'] == $port)
                                {
                                    $logWriter->LogMessage("Found matching server $address:$port");

                                    //Prepare RCON connection
                                    if (empty($rconServers[$dServer['ID']]))
                                    {
                                        $rconServers[$dServer['ID']] = new Rcon($address, $rconPort, NitrAutoConfig::RCONPassword);
                                    }

                                    if (!empty($task['Message']))
                                    {
                                        //Prepare the message
                                        $timeRemaining = "";
                                        switch ((int)$mins)
                                        {
                                            case 0:
                                                $timeRemaining = "NOW";
                                                break;
                                            case 1:
                                                $timeRemaining = "in $mins minute";
                                                break;
                                            default:
                                                $timeRemaining = "in $mins minutes";
                                                break;
                                        }
                                        $message = str_ireplace("%remaining%", $timeRemaining, $task["Message"]); //Replace %remaining% message variable
                
                                        switch ($task['MessageType'])
                                        {
                                            case 'SERVERCHAT':
                                                //Remove any carriage returns and split the message to send each line separately
                                                $msg = str_ireplace("\r\n", "\n", $message);
                                                foreach (explode("\n", $msg) as $m)
                                                {
                                                    $logWriter->LogMessage("Sending ServerChat: $m");
                                                    $rconServers[$dServer['ID']]->SendServerChat($m);
                                                }
                                                break;
                                            case 'BROADCAST':
                                                $logWriter->LogMessage("Sending Broadcast: $message");
                                                $rconServers[$dServer['ID']]->SendBroadcast($message);
                                                break;
                                            case 'NONE':
                                                break;
                                            default:
                                                break;
                                        }
                                    }
                
                                    //Check if any tasks need to be performed
                                    if ((int)$mins == 0)
                                    {
                                        if ($task['SwitchToConfigID'] > 0)
                                        {
                                            $cfgFile = @fopen(NitrAutoConfig::DynamicConfigFile, 'w');
                                            $cfgText = $sql->GetDynamicConfig($task['SwitchToConfigID']);
                                            @fwrite($cfgFile, $cfgText['Settings']);
                                            @fclose($cfgFile);
                                            $rconServers[$dServer['ID']]->SendCommand("ForceUpdateDynamicConfig");
                                        }

                                        if (!empty($task['AltCommand']))
                                        {
                                            $logWriter->LogMessage("Performing custom command...");
                                            $rconServers[$dServer['ID']]->SendCommand($task['AltCommand']);
                                        }

                                        if ((int)$task['DinoWipe'] == 1)
                                        {
                                            $logWriter->LogMessage("Wiping wild dinos...");
                                            $rconServers[$dServer['ID']]->WipeWildDinos();
                                        }
                
                                        if ((int)$task['SaveWorld'] == 1)
                                        {
                                            $logWriter->LogMessage("Saving world...");
                                            $rconServers[$dServer['ID']]->SaveWorld();
                                        }
                
                                        if ((int)$task['RestartServer'] == 1)
                                        {
                                            $logWriter->LogMessage("Restarting server...");
                                            $nServer->doRestart("Performing scheduled restart", "Performing scheduled restart");
                                        }
                                    }
                                }
                            }
                            
                        }
                    }
                    
                }
            }
            echo (NitrAutoConfig::DEBUG ? "Task Scheduled at " . date("H:i d-m-Y", $taskTime) ."<br />" : "");
        }
    }
} catch (Exception $e) {
    $logWriter->LogMessage($e->getMessage());
    die();
}
?>