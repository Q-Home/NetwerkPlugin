<?php
require_once "loxberry_web.php";
require_once "loxberry_system.php";

$L = LBSystem::readlanguage("language.ini");
$template_title = "MQTT Settings";
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
$navbar[3]['active'] = true;
LBWeb::lbheader($template_title, $helplink, $helptemplate);

$config_file = '/opt/loxberry/data/plugins/network_plugin/mqtt_config.ini';
$log_file = '/opt/loxberry/log/plugins/network_plugin/mqtt_settings.log';

function log_message($level, $message) {
    global $log_file;
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

$mqtt_host = '192.168.0.169';
$mqtt_port = '1883';
$mqtt_user = 'loxberry';
$mqtt_password = 'loxberry';
$mqtt_topic_prefix = 'network/changes';
$saveMessage = '';

if (file_exists($config_file)) {
    $config = parse_ini_file($config_file, true);
    if (isset($config['MQTT'])) {
        $mqtt_host = $config['MQTT']['host'] ?? $mqtt_host;
        $mqtt_port = $config['MQTT']['port'] ?? $mqtt_port;
        $mqtt_user = $config['MQTT']['user'] ?? $mqtt_user;
        $mqtt_password = $config['MQTT']['password'] ?? $mqtt_password;
        $mqtt_topic_prefix = $config['MQTT']['topic_prefix'] ?? $mqtt_topic_prefix;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mqtt_host = trim($_POST['mqtt_host']);
    $mqtt_port = trim($_POST['mqtt_port']);
    $mqtt_user = trim($_POST['mqtt_user']);
    $mqtt_password = trim($_POST['mqtt_password']);
    $mqtt_topic_prefix = trim($_POST['mqtt_topic_prefix']);

    log_message('info', "Received MQTT settings: host=$mqtt_host, port=$mqtt_port, user=$mqtt_user, topic_prefix=$mqtt_topic_prefix");

    if (!file_exists(dirname($config_file))) {
        mkdir(dirname($config_file), 0755, true);
    }

    $config_data = "[MQTT]\n";
    $config_data .= "host=$mqtt_host\n";
    $config_data .= "port=$mqtt_port\n";
    $config_data .= "user=$mqtt_user\n";
    $config_data .= "password=$mqtt_password\n";
    $config_data .= "topic_prefix=$mqtt_topic_prefix\n";

    file_put_contents($config_file, $config_data);
    log_message('info', 'MQTT configuration saved to ' . $config_file);

    $saveMessage = 'MQTT-instellingen succesvol opgeslagen.';
}
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
        max-width: 820px;
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
    input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #c2cbd8;
        font-size: 0.95rem;
        background: #f9fbff;
        color: var(--text);
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
</style>

<div class="panel">
    <h1>MQTT Settings</h1>
    <p>Configureer hier de verbinding met je MQTT-broker. Deze instellingen worden veilig opgeslagen in de data-map van de plugin.</p>

    <?php if ($saveMessage): ?>
        <div class="message"><?= htmlspecialchars($saveMessage) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="mqtt_host">MQTT Broker Address</label>
            <input type="text" id="mqtt_host" name="mqtt_host" value="<?= htmlspecialchars($mqtt_host) ?>" required>
            <span class="hint">Bijvoorbeeld: 192.168.0.169 of broker.example.com</span>
        </div>

        <div class="form-group">
            <label for="mqtt_port">Port</label>
            <input type="number" id="mqtt_port" name="mqtt_port" value="<?= htmlspecialchars($mqtt_port) ?>" required>
        </div>

        <div class="form-group">
            <label for="mqtt_user">Username</label>
            <input type="text" id="mqtt_user" name="mqtt_user" value="<?= htmlspecialchars($mqtt_user) ?>">
        </div>

        <div class="form-group">
            <label for="mqtt_password">Password</label>
            <input type="password" id="mqtt_password" name="mqtt_password" value="<?= htmlspecialchars($mqtt_password) ?>">
        </div>

        <div class="form-group">
            <label for="mqtt_topic_prefix">MQTT Topic Prefix</label>
            <input type="text" id="mqtt_topic_prefix" name="mqtt_topic_prefix" value="<?= htmlspecialchars($mqtt_topic_prefix) ?>" required>
            <span class="hint">Bijvoorbeeld: network/changes</span>
        </div>

        <button type="submit">Save Settings</button>
    </form>
</div>

<?php
LBWeb::lbfooter();
?>
