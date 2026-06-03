<?php
$pluginName = 'network_plugin';
$loxberryRoot = getenv('LBHOMEDIR');
if (!$loxberryRoot) {
    $marker = '/bin/plugins/';
    if (strpos(__DIR__, $marker) !== false) {
        $loxberryRoot = substr(__DIR__, 0, strpos(__DIR__, $marker));
    } else {
        $loxberryRoot = dirname(__DIR__, 3);
    }
}
$loxberryRoot = rtrim($loxberryRoot, '/');

$pluginDataRoot = getenv('LBPDATA') ?: $loxberryRoot . '/data/plugins';
$pluginBin = getenv('LBPBIN') ?: $loxberryRoot . '/bin/plugins';
$pluginDataRoot = rtrim($pluginDataRoot, '/');
$pluginBin = rtrim($pluginBin, '/');

$configFile = $pluginDataRoot . '/network_plugin/dns_config.json';
$dataFile = $pluginDataRoot . '/network_plugin/scandata.dat';

$domain = 'local';
$port = 5353;
$manualHosts = [];

if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (isset($config['DNS'])) {
        $domain = trim($config['DNS']['domain'] ?? $domain, '. ');
        $port = intval($config['DNS']['port'] ?? $port) ?: $port;
        $rawHosts = $config['DNS']['hosts'] ?? '';
        $lines = preg_split('/\r?\n/', $rawHosts);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $ip) = array_map('trim', explode('=', $line, 2));
                if ($name !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $manualHosts[$name] = $ip;
                }
            }
        }
    }
}

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    fwrite(STDERR, "[$timestamp] $message\n");
}

function loadScanHosts($dataFile) {
    $hosts = [];
    if (!file_exists($dataFile)) {
        return $hosts;
    }
    $data = json_decode(file_get_contents($dataFile), true);
    if (!is_array($data)) {
        return $hosts;
    }
    foreach ($data as $device) {
        if (empty($device['ip'])) {
            continue;
        }
        $name = $device['dns_name'] ?? $device['hostname'] ?? '';
        if ($name && $name !== 'Unknown') {
            $hosts[$name] = $device['ip'];
        }
    }
    return $hosts;
}

function normalizeQueryName($name) {
    return rtrim(strtolower($name), '.');
}

function parseDnsName($packet, $offset) {
    $labels = [];
    $originalOffset = $offset;
    $length = ord($packet[$offset]);
    while ($length > 0) {
        if (($length & 0xC0) === 0xC0) {
            $pointer = (ord($packet[$offset]) & 0x3F) << 8 | ord($packet[$offset + 1]);
            list($pointerName) = parseDnsName($packet, $pointer);
            $labels[] = $pointerName;
            $offset += 2;
            return [implode('.', $labels), $offset];
        }
        $offset++;
        $labels[] = substr($packet, $offset, $length);
        $offset += $length;
        $length = ord($packet[$offset]);
    }
    return [implode('.', $labels), $offset + 1];
}

function buildDnsName($name) {
    $parts = explode('.', $name);
    $result = '';
    foreach ($parts as $part) {
        $result .= chr(strlen($part)) . $part;
    }
    return $result . chr(0);
}

function buildResponse($query, $name, $type, $class, $answerIp, $hasAnswer, $rcode) {
    $id = substr($query, 0, 2);
    $flags = 0x8000 | 0x0400 | 0x0100;
    if (!$hasAnswer) {
        $flags = 0x8003 | 0x0100;
    }
    $header = $id . pack('n*', $flags, 1, $hasAnswer ? 1 : 0, 0, 0);
    $question = substr($query, 12, strlen($query) - 12);
    if ($hasAnswer && $answerIp) {
        $answer = chr(0xC0) . chr(0x0C) . pack('n*', 1, 1, 60, 4) . inet_pton($answerIp);
        return $header . $question . $answer;
    }
    return $header . $question;
}

if (!extension_loaded('sockets')) {
    logMessage('ERROR: PHP sockets extension is required.');
    exit(1);
}

$options = getopt('', ['port::', 'domain::']);
if (isset($options['port']) && intval($options['port']) > 0) {
    $port = intval($options['port']);
}
if (isset($options['domain']) && trim($options['domain']) !== '') {
    $domain = trim($options['domain'], '. ');
}

$hostTable = array_merge(loadScanHosts($dataFile), $manualHosts);
$hostTable = array_change_key_case($hostTable, CASE_LOWER);

$domain = strtolower($domain);
$bindAddress = '0.0.0.0';

logMessage("Starting DNS server on {$bindAddress}:{$port} for domain {$domain}");

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
if ($sock === false) {
    logMessage('ERROR: Could not create UDP socket');
    exit(1);
}

if (!socket_bind($sock, $bindAddress, $port)) {
    logMessage('ERROR: Could not bind to port ' . $port . '. Use root for port 53 or choose another port.');
    exit(1);
}

while (true) {
    $buf = '';
    $from = '0.0.0.0';
    $portFrom = 0;

    $bytes = socket_recvfrom($sock, $buf, 512, 0, $from, $portFrom);
    if ($bytes === false || $bytes === 0) {
        continue;
    }

    if (strlen($buf) < 12) {
        continue;
    }

    $qdcount = unpack('n', substr($buf, 4, 2))[1];
    if ($qdcount < 1) {
        continue;
    }

    list($qname, $offset) = parseDnsName($buf, 12);
    $question = substr($buf, $offset, 4);
    $type = unpack('n', substr($question, 0, 2))[1];
    $class = unpack('n', substr($question, 2, 2))[1];

    $qname = normalizeQueryName($qname);
    $answerIp = null;
    $hasAnswer = false;

    if ($type === 1 && $class === 1) {
        $suffix = '.' . $domain;
        if (substr($qname, -strlen($suffix)) === $suffix) {
            $short = substr($qname, 0, -strlen($suffix));
            if ($short === '') {
                $short = $domain;
            }
            if (isset($hostTable[$qname])) {
                $answerIp = $hostTable[$qname];
                $hasAnswer = true;
            } elseif (isset($hostTable[$short])) {
                $answerIp = $hostTable[$short];
                $hasAnswer = true;
            }
        }
    }

    $response = buildResponse($buf, $qname, $type, $class, $answerIp, $hasAnswer, $hasAnswer ? 0 : 3);
    socket_sendto($sock, $response, strlen($response), 0, $from, $portFrom);
    logMessage("DNS query from {$from}:{$portFrom} {$qname} -> " . ($hasAnswer ? $answerIp : 'NXDOMAIN'));
}
