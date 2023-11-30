<?php
include_once("../vendor/autoload.php");
include_once("../../NitrAutoConfig.php");

try {
    $api = new \Nitrapi\Nitrapi(NitrAutoConf::NitrAPIToken);
    
    $server = $api->getServices();
    echo "<table border=\"1\"><th>Name</th><th>Address</th>";
    foreach ($server as $svc)
    {
        $details = $svc->getServiceDetails();
        if ($details["game"] == "ARK: Survival Ascended")
        {
            echo "<tr>";
            echo "<td style=\"padding: 2px\">" . $details["name"] . "</td>";
            echo "<td style=\"padding: 2px\">" . $details["address"] . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
} catch(\Exception $e) {
    var_dump($e);
}
?>