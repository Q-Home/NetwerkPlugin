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

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Pragma: no-cache');

include_once('/opt/loxberry/bin/plugins/network_plugin/scanner.php');

$logFile = "/opt/loxberry/log/plugins/network_plugin/network_scan.log";

function loadLogs($logFile) {
    clearstatcache();
    if (file_exists($logFile)) {
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($logs, -12);
    }
    return [];
}

$logs = loadLogs($logFile);
$storedData = loadExistingData();
$scanMessage = '';
$changes = [];
$newDeviceCount = 0;
$removedDeviceCount = 0;
$lastScan = 'Nog geen scan uitgevoerd';

if (isset($_POST['scan'])) {
    list($changes, $newDevices) = performScan();
    $storedData = loadExistingData();
    $logs = loadLogs($logFile);

    if (!empty($changes)) {
        foreach ($changes as $change) {
            if (stripos($change, 'New device found') !== false) {
                $newDeviceCount++;
            } elseif (stripos($change, 'Device removed') !== false) {
                $removedDeviceCount++;
            }
        }
        $scanMessage = "Scan voltooid. $newDeviceCount nieuwe apparaten, $removedDeviceCount verwijderde apparaten.";
    } else {
        $scanMessage = "Scan voltooid. Geen wijzigingen gedetecteerd.";
    }
}

foreach (array_reverse($logs) as $line) {
    if (strpos($line, 'Network scan complete') !== false) {
        $lastScan = substr($line, 1, 19);
        break;
    }
}
?>

<style>
    :root {
        --bg: #f3f6fb;
        --card: #ffffff;
        --border: #d7e1ec;
        --text: #22313f;
        --muted: #6a7a8c;
        --primary: #2054a6;
        --success: #1f7a51;
        --warning: #d9821f;
        --danger: #c0392b;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: var(--bg);
        color: var(--text);
        margin: 0;
        padding: 0 20px 24px;
    }
    .page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        margin: 20px 0 10px;
    }
    .page-header h1 {
        margin: 0;
        font-size: 1.9rem;
    }
    .button-primary {
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 18px;
        font-size: 0.95rem;
        cursor: pointer;
    }
    .button-primary:hover {
        background: #18458a;
    }
    .meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .meta-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 4px 12px rgba(20, 40, 60, 0.08);
    }
    .meta-card h2 {
        margin: 0;
        font-size: 0.95rem;
        color: var(--muted);
    }
    .meta-card strong {
        display: block;
        margin-top: 12px;
        font-size: 1.7rem;
    }
    .alert {
        background: #e8f1ff;
        border-left: 4px solid var(--primary);
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 24px;
    }
    .card-panel {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 3px 10px rgba(20, 40, 60, 0.05);
        margin-bottom: 24px;
    }
    .card-panel h2 {
        margin-top: 0;
        font-size: 1.2rem;
    }
    .device-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }
    .device-table th,
    .device-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #eef2f7;
    }
    .device-table th {
        background: #f7fafc;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        font-size: 0.9rem;
    }
    .device-table tr:hover {
        background: #f2f6fb;
    }
    .log-panel {
        margin-top: 12px;
    }
    #log-viewer {
        background: #f9fbff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        max-height: 320px;
        overflow-y: auto;
    }
    .log-entry {
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.95rem;
        line-height: 1.55;
        padding: 10px 0;
        border-bottom: 1px solid #ebf0f6;
    }
    .log-entry:last-child {
        border-bottom: none;
    }
    .log-info { color: var(--text); }
    .log-warning { color: var(--warning); }
    .log-error { color: var(--danger); }
</style>

<div class="page-header">
    <div>
        <h1>Network Scan</h1>
        <p>Voer scans uit, bekijk apparaten en controleer logs met één overzichtelijke interface.</p>
    </div>
    <form method="post" style="margin:0;">
        <button type="submit" name="scan" class="button-primary">Start nieuwe scan</button>
    </form>
</div>

<div class="meta-grid">
    <div class="meta-card">
        <h2>Totaal gedetecteerde apparaten</h2>
        <strong><?= number_format(count($storedData)) ?></strong>
    </div>
    <div class="meta-card">
        <h2>Laatste scan</h2>
        <strong><?= htmlspecialchars($lastScan) ?></strong>
    </div>
    <div class="meta-card">
        <h2>Nieuwe apparaten</h2>
        <strong><?= number_format($newDeviceCount) ?></strong>
    </div>
    <div class="meta-card">
        <h2>Verwijderde apparaten</h2>
        <strong><?= number_format($removedDeviceCount) ?></strong>
    </div>
</div>

<?php if ($scanMessage): ?>
    <div class="alert"><?= htmlspecialchars($scanMessage) ?></div>
<?php endif; ?>

<div class="card-panel">
    <h2>Netwerkapparaten</h2>
    <?php if (empty($storedData)): ?>
        <p>Er zijn nog geen apparaten opgeslagen. Klik op "Start nieuwe scan" om apparaten te ontdekken.</p>
    <?php else: ?>
        <table class="device-table">
            <thead>
                <tr>
                    <th>Hostname</th>
                    <th>IP</th>
                    <th>MAC</th>
                    <th>Vendor</th>
                    <th>Last seen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($storedData as $mac => $device): ?>
                    <tr>
                        <td><?= htmlspecialchars($device['hostname'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($device['ip'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($device['mac'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($device['vendor'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($device['last_seen'] ?? 'Unknown') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card-panel log-panel">
    <h2>Recente logs</h2>
    <div id="log-viewer">
        <?php if (empty($logs)): ?>
            <div class="log-entry log-info">Geen logregels gevonden.</div>
        <?php else: ?>
            <?php foreach ($logs as $line): ?>
                <?php
                    $styleClass = 'log-info';
                    if (stripos($line, 'error') !== false) {
                        $styleClass = 'log-error';
                    } elseif (stripos($line, 'warning') !== false) {
                        $styleClass = 'log-warning';
                    }
                ?>
                <div class="log-entry <?= $styleClass ?>"><?= htmlspecialchars($line) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
LBWeb::lbfooter();
?>

<?php  
// Footer afdrukken
LBWeb::lbfooter();
?>
