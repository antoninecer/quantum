<?php
// includes/tombola_lib.php

$dsn      = 'mysql:host=localhost;dbname=quantum;charset=utf8mb4';
$dbUser   = 'quantum';
$dbPass   = 'QuantumHeslo1*';

$options  = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die('DB error: ' . htmlspecialchars($e->getMessage()));
}

$QUANTUM_API_URL = getenv('QUANTUM_API_URL') ?: 'https://quantum.api.ventureout.cz/random';

/**
 * Volání Quantum API – vrátí jedno náhodné číslo v daném rozsahu.
 * Používá formát requestu/resultu dle README (type=int, count=1, range[min,max]) :contentReference[oaicite:0]{index=0}
 */
function quantum_random_int($min, $max, $apiUrl)
{
    $payload = [
        'request' => [
            [
                'random' => [
                    'type'     => 'int',
                    'count'    => 1,
                    'unique'   => false,
                    'range'    => [(int)$min, (int)$max],
                    'alphabet' => null,
                ],
            ],
        ],
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 5,
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || $code < 200 || $code >= 300) {
        return null;
    }

    $data = json_decode($resp, true);
    if (!isset($data['result'][0][0])) {
        return null;
    }

    return (int)$data['result'][0][0];
}

function draw_unique_ticket(PDO $pdo, array $event, $apiUrl)
{
    $min = (int)$event['ticket_from'];
    $max = (int)$event['ticket_to'];

    // už použité lístky v dané akci
    $stmt = $pdo->prepare('SELECT ticket_number FROM tombola_draws WHERE event_id = ?');
    $stmt->execute([$event['id']]);
    $used = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $usedSet = [];
    foreach ($used as $u) {
        $usedSet[(int)$u] = true;
    }

    $poolSize = $max - $min + 1;
    if (count($usedSet) >= $poolSize) {
        return null; // nic už nezbylo
    }

    // pár pokusů přes Quantum API
    for ($i = 0; $i < 20; $i++) {
        $candidate = quantum_random_int($min, $max, $apiUrl);
        if ($candidate === null) {
            break; // API zdechlo, zkusíme fallback
        }
        if (!isset($usedSet[$candidate])) {
            return $candidate;
        }
    }

    // fallback – čistě PHP náhoda z volných čísel (pořád lepší než nic)
    $free = [];
    for ($n = $min; $n <= $max; $n++) {
        if (!isset($usedSet[$n])) {
            $free[] = $n;
        }
    }
    if (!$free) {
        return null;
    }

    return $free[array_rand($free)];
}

/**
 * Pomocná funkce – spočítá kolik už je „valid“ výher pro danou cenu.
 */
function count_valid_wins(PDO $pdo, $prizeId)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tombola_draws WHERE prize_id = ? AND status = "valid"');
    $stmt->execute([$prizeId]);
    return (int)$stmt->fetchColumn();
}






?>