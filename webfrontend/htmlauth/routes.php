<?php
require_once "loxberry_web.php";
require_once "loxberry_system.php";

$L = LBSystem::readlanguage("language.ini");

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

$pluginLogRoot = getenv('LBPLOG') ?: $loxberryRoot . '/log/plugins';
$pluginLogRoot = rtrim($pluginLogRoot, '/');

$template_title = "Network Routes";
$helplink = "http://www.loxwiki.eu:80/x/2wzL";
$helptemplate = "help.html";

$navbar[1]['Name'] = 'Home';
$navbar[1]['URL'] = 'index.php';
$navbar[1]['active'] = false;
$navbar[2]['Name'] = 'Routes';
$navbar[2]['URL'] = 'routes.php';
$navbar[2]['active'] = true;
$navbar[3]['Name'] = 'MQTT Settings';
$navbar[3]['URL'] = 'mqtt.php';
$navbar[3]['active'] = false;
$navbar[4]['Name'] = 'DNS Server';
$navbar[4]['URL'] = 'dns.php';
$navbar[4]['active'] = false;

LBWeb::lbheader($template_title, $helplink, $helptemplate);

$logFile = $pluginLogRoot . '/network_plugin/network_routes.log';

function logMessage($message) {
    global $logFile;
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
}

function getRoutes() {
    logMessage("Fetching routes...");
    $output = shell_exec("ip route show 2>&1");
    logMessage("Routes output: " . trim($output));
    $routes = explode("\n", trim($output));
    return array_filter($routes);
}

function getOwnIP() {
    $output = shell_exec("ip -4 addr show eth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n1");
    return trim($output);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_route'])) {
        $route = trim($_POST['route']);
        $escapedRoute = escapeshellarg($route);
        logMessage("Deleting route: $route");
        $output = shell_exec("sudo ip route del $escapedRoute 2>&1");
        logMessage("Delete output: " . trim($output));
        $message = "Route verwijderd: $route";
    } elseif (isset($_POST['add_route'])) {
        $destination = trim($_POST['destination']);
        $gateway = trim($_POST['gateway']);
        $escapedDestination = escapeshellarg($destination);
        $escapedGateway = escapeshellarg($gateway);
        logMessage("Adding route: $destination via $gateway");
        $output = shell_exec("sudo ip route add $escapedDestination via $escapedGateway 2>&1");
        logMessage("Add output: " . trim($output));
        $message = "Route toegevoegd: $destination via $gateway";
    }
}

$routes = getRoutes();
$ownIP = getOwnIP();

?>

<style>
    :root {
        --bg: #f5f8ff;
        --card: #ffffff;
        --border: #dde7f1;
        --text: #203248;
        --muted: #5d738c;
        --primary: #2563eb;
        --danger: #b91c1c;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: var(--bg);
        color: var(--text);
        margin: 0;
        padding: 20px;
    }
    .section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: 0 4px 16px rgba(23, 42, 69, 0.08);
        padding: 24px;
        margin-bottom: 24px;
    }
    .section h1 {
        margin-top: 0;
        font-size: 1.7rem;
    }
    .info-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        margin-bottom: 18px;
    }
    .info-card {
        background: #f7fbff;
        border: 1px solid #e3edf8;
        border-radius: 14px;
        padding: 16px;
    }
    .info-card strong {
        display: block;
        margin-top: 10px;
        font-size: 1.4rem;
    }
    .message {
        background: #e3f2ff;
        border-left: 4px solid var(--primary);
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .form-inline {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .form-inline input,
    .form-inline button {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        font-size: 0.95rem;
    }
    .form-inline button {
        background: var(--primary);
        color: white;
        border: none;
        cursor: pointer;
    }
    .form-inline button:hover {
        background: #1d4fc1;
    }
    .route-table {
        width: 100%;
        border-collapse: collapse;
    }
    .route-table th,
    .route-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e3eaf2;
        text-align: left;
    }
    .route-table th {
        background: #f8fbff;
        font-weight: 700;
    }
    .route-table tr:hover {
        background: #f4f8ff;
    }
    .button-danger {
        background: var(--danger);
        color: white;
        border: none;
        padding: 10px 14px;
        border-radius: 10px;
        cursor: pointer;
    }
</style>

<div class="section">
    <h1>Network Routes</h1>
    <div class="info-grid">
        <div class="info-card">
            <div>Huidig eth0 IP-adres</div>
            <strong><?= htmlspecialchars($ownIP ?: 'Niet beschikbaar') ?></strong>
        </div>
        <div class="info-card">
            <div>Aantal routes</div>
            <strong><?= number_format(count($routes)) ?></strong>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" class="form-inline">
        <input type="text" name="destination" placeholder="Destination (bijv. 192.168.100.0/24)" required>
        <input type="text" name="gateway" placeholder="Gateway (bijv. 192.168.100.1)" required>
        <button type="submit" name="add_route">Add Route</button>
    </form>

    <div style="overflow-x:auto;">
        <table class="route-table">
            <thead>
                <tr>
                    <th>Route</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><?= htmlspecialchars($route) ?></td>
                        <td>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="route" value="<?= htmlspecialchars($route) ?>">
                                <button type="submit" name="delete_route" class="button-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
LBWeb::lbfooter();
?>
