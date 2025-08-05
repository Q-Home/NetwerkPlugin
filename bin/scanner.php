<?php
$logFile = "/opt/loxberry/log/plugins/network_plugin/network_scan.log";
$datFile = "/opt/loxberry/data/plugins/network_plugin/scandata.dat";
$mqttConfigFile = "/opt/loxberry/webfrontend/htmlauth/plugins/network_plugin/mqtt_config.ini";

require('/opt/loxberry/bin/plugins/network_plugin/phpMQTT/phpMQTT.php');

// Logging functie
function logChange($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Functie om MAC-adres op te halen via IP (ARP)
function getMacAddressFromIp($ip) {
    $mac = trim(shell_exec("ip link show eth0 | awk '/ether/ {print $2}'"));
    return !empty($mac) ? strtoupper($mac) : "Unknown";
}

function getLoxberryMac() {
    $mac = trim(shell_exec("ip link show eth0 | awk '/ether/ {print $2}'"));
    return !empty($mac) ? strtoupper($mac) : "Unknown";
}

// Netwerkscan functie met nmap
function scanNetwork() {
    $command = "sudo nmap -sn $(ip route | awk '/default/ {print $3}')/24 | awk '/Nmap scan report|MAC Address/ {print}'";
    $output = shell_exec($command);

    if (!$output) {
        logChange("Error: nmap command failed or returned empty output.");
        return [];
    }

    $lines = explode("\n", trim($output));
    $newDevices = [];
    $device = [];

    foreach ($lines as $line) {
        if (preg_match('/^Nmap scan report for\s+([^\s]+)(?:\s+\(([\d\.]+)\))?$/', $line, $matches)) {
            if (!empty($device)) {
                if (!isset($device['mac']) || $device['mac'] === "Unknown") {
                    // Haal MAC-adres op via het IP
                    $device['mac'] = getMacAddressFromIp($device['ip']);
                    if (!$device['mac']) {
                        $device['mac'] = getLoxberryMac(); // Voeg het eigen MAC-adres toe als MAC onbekend is
                    }
                }
                $device['last_seen'] = date('Y-m-d H:i:s');
                $key = ($device['mac'] !== "Unknown") ? strtoupper($device['mac']) : $device['ip'];
                $newDevices[$key] = $device;
            }
            $device = [];
            $device['hostname'] = isset($matches[2]) ? $matches[1] : $matches[1];
            $device['ip'] = $matches[2] ?? $matches[1];
        } elseif (preg_match('/^MAC Address:\s+([0-9A-Fa-f:]+)\s+\((.+)\)$/', $line, $matches)) {
            $device['mac'] = strtoupper($matches[1]);
            $device['vendor'] = trim($matches[2]);
        }
    }

    // Zorg ervoor dat het laatste apparaat wordt toegevoegd
    if (!empty($device)) {
        if (!isset($device['mac']) || $device['mac'] === "Unknown") {
            $device['mac'] = getMacAddressFromIp($device['ip']);
            if (!$device['mac']) {
                $device['mac'] = getLoxberryMac(); // Voeg het eigen MAC-adres toe als MAC onbekend is
            }
        }
        $device['last_seen'] = date('Y-m-d H:i:s');
        $key = ($device['mac'] !== "Unknown") ? strtoupper($device['mac']) : $device['ip'];
        $newDevices[$key] = $device;
    }

    return $newDevices;
}

// Laad MQTT-configuratie
function loadMQTTConfig() {
    global $mqttConfigFile;

    // Standaardwaarden voor MQTT-configuratie
    $mqtt_host = '192.168.0.169';
    $mqtt_port = 1883;
    $mqtt_user = 'loxberry';
    $mqtt_password = 'loxberry';
    $mqtt_topic_prefix = 'network/changes';  // Verander dit naar je gewenste standaard topic

    // Controleer of het configuratiebestand bestaat en lees de waarden
    if (file_exists($mqttConfigFile)) {
        $config = parse_ini_file($mqttConfigFile, true);

        // Controleer of de MQTT-sectie bestaat en lees de waarden
        if (isset($config['MQTT'])) {
            $mqtt_host = $config['MQTT']['host'] ?? $mqtt_host;
            $mqtt_port = $config['MQTT']['port'] ?? $mqtt_port;
            $mqtt_user = $config['MQTT']['user'] ?? $mqtt_user;
            $mqtt_password = $config['MQTT']['password'] ?? $mqtt_password;
            $mqtt_topic_prefix = $config['MQTT']['topic_prefix'] ?? $mqtt_topic_prefix;
        }
    }

    // Retourneer de configuratie als een associatieve array
    return [
        'host' => $mqtt_host,
        'port' => $mqtt_port,
        'user' => $mqtt_user,
        'password' => $mqtt_password,
        'topic_prefix' => $mqtt_topic_prefix,
    ];
}

// Publiceer gegevens naar MQTT
function publishToMQTT($topic, $message) {
    $config = loadMQTTConfig();
    
    // Controleer of de configuratie goed is geladen
    if (empty($config)) return;

    // Maak verbinding met de MQTT-broker en publiceer het bericht
    $mqtt = new Bluerhinos\phpMQTT($config['host'], $config['port'], "loxberry_network_scanner");
    if ($mqtt->connect(true, NULL, $config['user'], $config['password'])) {
        // Voeg het topic-prefix toe aan het opgegeven topic
        $fullTopic = $config['topic_prefix'] . $topic;
        $mqtt->publish($fullTopic, $message, 0);
        $mqtt->close();
    } else {
        logChange("Failed to connect to MQTT broker.");
    }
}

// Laad bestaande scangegevens
function loadExistingData() {
    global $datFile;
    if (!file_exists($datFile)) {
        logChange("No previous scan data found.");
        return [];
    }

    $data = file_get_contents($datFile);
    return json_decode($data, true) ?: [];
}

// Vergelijk bestaande apparaten met nieuwe apparaten, en detecteer IP-wijzigingen
function compareDevices($existingDevices, $newDevices) {
    $changes = [];

    // Controleer op nieuwe of verwijderde apparaten
    foreach ($newDevices as $key => $device) {
        if (!isset($existingDevices[$key])) {
            // Nieuw apparaat, log en stuur een bericht
            $changes[] = "New device found: " . json_encode($device);
        } else {
            // Controleer op wijziging van MAC-adres of IP-adres voor hetzelfde apparaat
            if ($existingDevices[$key]['mac'] !== $device['mac']) {
                $changes[] = "MAC address change detected for " . $device['ip'] . ": " . $existingDevices[$key]['mac'] . " -> " . $device['mac'];
            }
            if ($existingDevices[$key]['ip'] !== $device['ip']) {
                $changes[] = "IP address change detected for " . $device['mac'] . ": " . $existingDevices[$key]['ip'] . " -> " . $device['ip'];
            }
        }
    }

    // Controleer voor verwijderde apparaten
    foreach ($existingDevices as $key => $device) {
        if (!isset($newDevices[$key])) {
            $changes[] = "Device removed: " . json_encode($device);
        }
    }

    return $changes;
}

// Sla scanresultaten op in een gegevensbestand
function saveToDatFile($newDevices) {
    global $datFile;
    $data = json_encode($newDevices, JSON_PRETTY_PRINT);
    file_put_contents($datFile, $data);
}

// Voer de netwerkscan uit en publiceer de resultaten
function performScan() {
    $timestamp = date('Y-m-d H:i:s');
    
    // Laad bestaande apparaten en voer de scan uit
    $existingDevices = loadExistingData();
    $newDevices = scanNetwork();

    if (empty($newDevices)) {
        logChange("No new devices found or nmap scan failed.");
        return;
    }

    // Vergelijk de bestaande apparaten met de nieuwe apparaten
    $changes = compareDevices($existingDevices, $newDevices);

    // Log en publiceer alleen als er veranderingen zijn
    if (!empty($changes)) {
        foreach ($changes as $change) {
            logChange($change);
            publishToMQTT("network/changes", json_encode(["timestamp" => $timestamp, "message" => $change]));
        }

        // Als er veranderingen zijn, sla dan de nieuwe data op in het .dat-bestand
        saveToDatFile($newDevices);
        logChange("Network scan complete. Data updated.");
    }
}

// Uitvoeren van de netwerkscan via de commandoregel
if (php_sapi_name() == "cli") {
    echo "Running Network Scan...\n";
    performScan();
    echo "Scan complete. Results logged and published to MQTT.\n";
}
?>
