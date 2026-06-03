<?php
require_once "loxberry_web.php";
require_once "loxberry_system.php";

$L = LBSystem::readlanguage("language.ini");
$template_title = "DNS Server";
$helplink = "http://www.loxwiki.eu:80/x/2wzL";
$helptemplate = "help.html";

$navbar[1]['Name'] = 'Home';
$navbar[1]['URL'] = 'index.php';
$navbar[1]['active'] = false;
$navbar[2]['Name'] = 'Routes';
$navbar[2]['URL'] = 'routes.php';
$navbar[2]['active'] = false;
$navbar[3]['Name'] = 'MQTT Settings';
$navbar[3]['URL'] = 'mqtt.php';
$navbar[3]['active'] = false;
$navbar[4]['Name'] = 'DNS Server';
$navbar[4]['URL'] = 'dns.php';
$navbar[4]['active'] = true;
LBWeb::lbheader($template_title, $helplink, $helptemplate);

$pluginName = 'network_plugin';
$loxberryRoot = getenv('LBHOMEDIR');
if (!$loxberryRoot) {
    $marker = '/webfrontend/htmlauth';
    if (strpos(__DIR__, $marker) !== false) {
        $loxberryRoot = substr(__DIR__, 0, strpos(__DIR__, $marker));
    } else {
        $loxberryRoot = dirname(__DIR__, 3);
    }
}
$loxberryRoot = rtrim($loxberryRoot, '/');

$pluginDataRoot = getenv('LBPDATA') ?: $loxberryRoot . '/data/plugins';
$pluginLogRoot = getenv('LBPLOG') ?: $loxberryRoot . '/log/plugins';
$pluginDataRoot = rtrim($pluginDataRoot, '/');
$pluginLogRoot = rtrim($pluginLogRoot, '/');

$config_file = $pluginDataRoot . '/network_plugin/dns_config.json';
$log_file = $pluginLogRoot . '/network_plugin/dns_settings.log';
$scan_file = $pluginDataRoot . '/network_plugin/scandata.dat';

function log_message($level, $message) {
    global $log_file;
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

function parseHostMap($text) {
    $result = [];
    $lines = preg_split('/\r?\n/', trim($text));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $ip) = array_map('trim', explode('=', $line, 2));
            if ($name !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $result[$name] = $ip;
            }
        }
    }
    return $result;
}

$dns_domain = 'local';
$dns_port = '5353';
$manual_hosts = "";
$saveMessage = '';

if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    if (isset($config['DNS'])) {
        $dns_domain = $config['DNS']['domain'] ?? $dns_domain;
        $dns_port = $config['DNS']['port'] ?? $dns_port;
        $manual_hosts = $config['DNS']['hosts'] ?? $manual_hosts;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dns_domain = trim($_POST['dns_domain']);
    $dns_port = trim($_POST['dns_port']);
    $manual_hosts = trim($_POST['manual_hosts']);

    if (!file_exists(dirname($config_file))) {
        mkdir(dirname($config_file), 0755, true);
    }

    $configData = [
        'DNS' => [
            'domain' => $dns_domain,
            'port' => $dns_port,
            'hosts' => $manual_hosts,
        ],
    ];

    file_put_contents($config_file, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    log_message('info', 'DNS configuration saved to ' . $config_file);
    $saveMessage = 'DNS-instellingen succesvol opgeslagen.';
}

$scanHosts = [];
if (file_exists($scan_file)) {
    $scan_data = json_decode(file_get_contents($scan_file), true);
    if (is_array($scan_data)) {
        foreach ($scan_data as $device) {
            if (!empty($device['ip'])) {
                $name = $device['dns_name'] ?? $device['hostname'] ?? '';
                if ($name && $name !== 'Unknown') {
                    $scanHosts[$name] = $device['ip'];
                }
            }
        }
    }
}

$manualMapping = parseHostMap($manual_hosts);

?>

<style>
    :root {
        --bg: #f8fafc;
        --card: #ffffff;
        --border: #d1d9e6;
        --text: #243447;
        --muted: #5f7285;
        --primary: #2563eb;
        --success: #1f7a3f;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: var(--bg);
        color: var(--text);
        margin: 0;
        padding: 20px;
    }
    .panel {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: 0 6px 18px rgba(31, 41, 55, 0.08);
        padding: 24px;
        max-width: 900px;
        margin-bottom: 24px;
    }
    .panel h1 {
        margin-top: 0;
        font-size: 1.8rem;
    }
    .panel p {
        margin: 8px 0 18px;
        color: var(--muted);
        line-height: 1.6;
    }
    .form-group {
        display: grid;
        gap: 8px;
        margin-bottom: 20px;
    }
    label {
        font-weight: 600;
        color: var(--text);
    }
    input, textarea {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #c2cbd8;
        font-size: 0.95rem;
        background: #f9fbff;
        color: var(--text);
    }
    textarea {
        min-height: 140px;
        resize: vertical;
    }
    button {
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 18px;
        cursor: pointer;
        font-size: 0.95rem;
    }
    button:hover {
        background: #1e52c8;
    }
    .message {
        background: #e9f7ed;
        border-left: 4px solid var(--success);
        color: var(--text);
        padding: 14px 16px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    .hint {
        color: var(--muted);
        font-size: 0.95rem;
    }
    .host-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
    }
    .host-table th, .host-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #edf2f7;
    }
    .host-table th {
        background: #f7fafc;
        text-transform: uppercase;
        font-size: 0.85rem;
        color: var(--muted);
    }
</style>

<div class="panel">
    <h1>DNS Server</h1>
    <p>Met deze functie kun je een eenvoudige lokale DNS-resolver configureren voor de namen die deze plugin ontdekt of die je handmatig toevoegt.</p>

    <?php if ($saveMessage): ?>
        <div class="message"><?= htmlspecialchars($saveMessage) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="dns_domain">DNS-domein</label>
            <input type="text" id="dns_domain" name="dns_domain" value="<?= htmlspecialchars($dns_domain) ?>" required>
            <span class="hint">Bijvoorbeeld: local of network.local. Query's voor <code>host.<?= htmlspecialchars($dns_domain) ?></code> worden naar de host gekoppeld.</span>
        </div>

        <div class="form-group">
            <label for="dns_port">DNS-poort</label>
            <input type="number" id="dns_port" name="dns_port" value="<?= htmlspecialchars($dns_port) ?>" required>
            <span class="hint">Standaard 5353 is handig als je geen root-toegang hebt. Gebruik 53 als je het DNS-serverproces als root kunt draaien.</span>
        </div>

        <div class="form-group">
            <label for="manual_hosts">Handmatige host mappings</label>
            <textarea id="manual_hosts" name="manual_hosts" placeholder="voorbeeld: printer=192.168.0.50\nserver=192.168.0.10"><?= htmlspecialchars($manual_hosts) ?></textarea>
            <span class="hint">Elke regel met <code>host=ip</code>. Dit wordt samen met de gescande apparaten gebruikt.</span>
        </div>

        <button type="submit">Opslaan</button>
    </form>

    <div class="hint" style="margin-top: 24px;">
        Om de DNS-server te starten, voer het volgende commando uit op de LoxBerry:
        <pre>loxberry php <?= htmlspecialchars($loxberryRoot . '/bin/plugins/' . $pluginName . '/dns_server.php') ?></pre>
        Gebruik <code>--port=53</code> alleen als de gebruiker voldoende rechten heeft.
    </div>
</div>

<div class="panel">
    <h2>Gescande hostnamen</h2>
    <?php if (empty($scanHosts)): ?>
        <p>Er zijn nog geen apparaten met DNS-naam beschikbaar. Voer een netwerk scan uit om apparaten te detecteren.</p>
    <?php else: ?>
        <table class="host-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>IP-adres</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scanHosts as $name => $ip): ?>
                    <tr>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($ip) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Handmatige DNS-mappings</h2>
    <?php if (empty($manualMapping)): ?>
        <p>Geen handmatige DNS-mappings geconfigureerd.</p>
    <?php else: ?>
        <table class="host-table">
            <thead>
                <tr>
                    <th>Host</th>
                    <th>IP-adres</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manualMapping as $name => $ip): ?>
                    <tr>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($ip) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
LBWeb::lbfooter();
?>