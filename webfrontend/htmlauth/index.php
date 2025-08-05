<?php
require_once "loxberry_web.php";
require_once "loxberry_system.php";

// This will read your language files to the array $L
$L = LBSystem::readlanguage("language.ini");

$template_title = "network_plugin";
$helplink = "http://www.loxwiki.eu:80/x/2wzL";
$helptemplate = "help.html";

// The Navigation Bar
$navbar[1]['Name'] = 'Home';
$navbar[1]['URL'] = 'index.php';
$navbar[1]['active'] = true;
$navbar[2]['Name'] = 'Routes';
$navbar[2]['URL'] = 'routes.php';
$navbar[2]['active'] = false;
$navbar[3]['Name'] = 'MQTT Settings';
$navbar[3]['URL'] = 'mqtt.php';
$navbar[3]['active'] = false;
LBWeb::lbheader($template_title, $helplink, $helptemplate);
?>

<h1>Home</h1>

<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Pragma: no-cache');

// Include de scanner functionaliteit
include_once('/opt/loxberry/bin/plugins/network_plugin/scanner.php');

// Logbestand pad
$logFile = "/opt/loxberry/log/plugins/network_plugin/network_scan.log";
$datFile = "/opt/loxberry/data/plugins/network_plugin/scandata.dat";

// Functie om de logs uit het logbestand te laden
function loadLogs($logFile) {
    clearstatcache();  // Clear de cache zodat PHP het bestand vers leest
    if (file_exists($logFile)) {
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($logs, -10); // Laatste 10 logs
    }
    return [];
}

// Laad logs (tot de laatste 10 regels)
$logs = loadLogs($logFile);

// Lees de opgeslagen gegevens uit het .dat bestand
$storedData = loadExistingData();

// Als de scan is getriggerd, voer de scan uit en werk de logs bij
if (isset($_POST['scan'])) {
    list($changes, $newDevices) = performScan();
    $logs = loadLogs($logFile); // Logs verversen na de scan
    $storedData = $newDevices; // Update de opgeslagen gegevens met de laatste scan
    echo "<meta http-equiv='refresh' content='0;url=index.php'>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Scan</title>
    <style>
        /* Stijl voor de tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }

        /* Log viewer styling */
        #log-viewer {
            width: 100%;
            height: 300px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 20px;
        }
        .log-info {
            color: #000000;
        }
        .log-warning {
            color: #FF9800;
        }
        .log-error {
            color: #F44336;
        }
        .log-info, .log-warning, .log-error {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }

        /* Stijl voor apparaat veranderingen */
        .new-device {
            background-color: #d4f7d4; /* Light Green */
        }
        .removed-device {
            background-color: #f7d4d4; /* Light Red */
        }
    </style>
</head>
<body>
    <h2>Network Scan</h2>
    
    <!-- Knop om scan te starten -->
    <form method="post">
        <button type="submit" name="scan">Scan Network</button>
    </form>

    <h3>Current Network Devices</h3>
    <table>
        <tr>
            <th>Hostname</th>
            <th>IP Address</th>
            <th>MAC Address</th>
            <th>Vendor</th>
            <th>Last Seen</th>
        </tr>
        <?php foreach ($storedData as $mac => $device): ?>
            <?php
            // Highlight veranderingen
            $rowClass = '';
            if (isset($changes) && in_array("Device JOINED NETWORK: {$device['hostname']} ({$device['ip']}) [MAC: $mac]", $changes)) {
                $rowClass = 'new-device'; // Nieuw apparaat
            } elseif (isset($changes) && in_array("Device LEFT NETWORK: {$device['hostname']} ({$device['ip']}) [MAC: $mac]", $changes)) {
                $rowClass = 'removed-device'; // Verwijderd apparaat
            }
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?php echo htmlspecialchars($device['hostname'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($device['ip'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($device['mac'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($device['vendor'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($device['last_seen'] ?? 'Unknown'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Log viewer sectie -->
    <h3>Log Viewer</h3>
    <div id="log-viewer">
    <?php 
    // Omgekeerde logs weergeven (nieuwste bovenaan)
    $logs = array_reverse($logs);
    foreach ($logs as $line):
        $logClass = '';
        if (strpos($line, '[error]') !== false) {
            $logClass = 'log-error';
        } elseif (strpos($line, '[warning]') !== false) {
            $logClass = 'log-warning';
        } else {
            $logClass = 'log-info';
        }
    ?>
        <div class="<?= $logClass ?>">
            <?= htmlspecialchars($line) ?>
        </div>
    <?php endforeach; ?>
</div>


</body>
</html>

<?php  
// Footer afdrukken
LBWeb::lbfooter();
?>
