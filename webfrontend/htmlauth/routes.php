<?php
require_once "loxberry_web.php";
require_once "loxberry_system.php";

$L = LBSystem::readlanguage("language.ini");

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

LBWeb::lbheader($template_title, $helplink, $helptemplate);

function logMessage($message) {
    file_put_contents('/tmp/network_routes.log', date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
}

function getRoutes() {
    logMessage("Fetching routes...");
    $output = shell_exec("ip route show 2>&1");
    logMessage("Routes output: " . $output);
    $routes = explode("\n", trim($output));
    return array_filter($routes);
}

function getOwnIP() {
    // Verkrijg het IP-adres van de eth0 interface
    $output = shell_exec("ip a show eth0 | grep inet | awk '{ print $2 }' | cut -d/ -f1");
    return trim($output);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_route'])) {
        // Haal het eerste deel van de route op (tot de eerste spatie)
        $route = $_POST['route'];
        $route_parts = explode(' ', $route); // Splitst de route op basis van spaties
        $route = $route_parts[0]; // Neem alleen het eerste deel van de route
    
        logMessage("Deleting route: $route");
        $output = shell_exec("sudo ip route del $route 2>&1");
        logMessage("Delete output: " . $output);
        echo "<meta http-equiv='refresh' content='0;url=routes.php'>";
        exit;
    
    } elseif (isset($_POST['add_route'])) {
        $destination = escapeshellarg($_POST['destination']);
        $gateway = escapeshellarg($_POST['gateway']);
        logMessage("Adding route: $destination via $gateway");
        $output = shell_exec("sudo ip route add $destination via $gateway 2>&1");
        logMessage("Add output: " . $output);
        echo "<meta http-equiv='refresh' content='0;url=routes.php'>";
        exit;
    }
}

$routes = getRoutes();
$ownIP = getOwnIP(); // Verkrijg je IP-adres van eth0

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Routes</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .form-inline { display: flex; gap: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Network Routes</h2>
    <p><strong>My IP Address (eth0): </strong> <?= htmlspecialchars($ownIP) ?></p> <!-- Toon je eigen IP-adres van eth0 -->

    <form method="post" class="form-inline">
        <input type="text" name="destination" placeholder="Destination" required>
        <input type="text" name="gateway" placeholder="Gateway" required>
        <button type="submit" name="add_route">Add Route</button>
    </form>

    <table>
        <tr>
            <th>Route</th>
            <th>Action</th>
        </tr>
        <?php foreach ($routes as $route): ?>
            <tr>
                <td><?= htmlspecialchars($route) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="route" value="<?= htmlspecialchars($route) ?>">
                        <button type="submit" name="delete_route">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

<?php  
LBWeb::lbfooter();
?>
