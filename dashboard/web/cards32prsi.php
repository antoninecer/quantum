<?php
// dashboard/web/cards32.php
//
// Demo: 32karetní balíček (7–A ve 4 barvách) + rozdání pro prší (2–4 hráči)
// používá Quantum Random API na zamíchání balíčku.

include __DIR__ . '/includes/header.php';

// URL Quantum API
const QUANTUM_API_URL = 'https://quantum.api.ventureout.cz/random';

// kolik karet do ruky pro prší
const CARDS_PER_PLAYER = 4;

// připravíme proměnné pro výsledky a chyby
$error   = null;
$info    = [];
$result  = null;

// definice 32 karet – 7,8,9,10,J,Q,K,A ve 4 barvách
$deck = [
    '7♣','8♣','9♣','10♣','J♣','Q♣','K♣','A♣',  // kříže
    '7♦','8♦','9♦','10♦','J♦','Q♦','K♦','A♦',  // káry
    '7♥','8♥','9♥','10♥','J♥','Q♥','K♥','A♥',  // srdce
    '7♠','8♠','9♠','10♠','J♠','Q♠','K♠','A♠',  // piky
];

$playerCount = 3; // default

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playerCount = (int)($_POST['players'] ?? 3);

    // validace počtu hráčů
    if ($playerCount < 2 || $playerCount > 4) {
        $error = 'Počet hráčů musí být mezi 2 a 4.';
    } else {
        // kolik karet celkem potřebujeme pro rozdání + jednu na stůl
        $needed = $playerCount * CARDS_PER_PLAYER + 1;
        if ($needed > count($deck)) {
            $error = 'Pro zadaný počet hráčů a karet do ruky není dost karet v balíčku.';
        }
    }

    if ($error === null) {
        // připravíme payload pro Quantum API – permutace 0..31
        $payload = [
            'request' => [
                [
                    'random' => [
                        'type'     => 'int',
                        'count'    => count($deck),
                        'unique'   => true,
                        'range'    => [0, count($deck) - 1],
                        'alphabet' => null,
                    ],
                ],
            ],
        ];

        $ch = curl_init(QUANTUM_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 5,
        ]);

        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 0;
        curl_close($ch);

        if ($resp === false) {
            $error = 'Chyba při volání Quantum API: ' . ($curlErr ?: 'neznámá chyba');
        } elseif ($httpCode >= 400) {
            $error = 'Quantum API vrátilo HTTP ' . $httpCode . '.';
        } else {
            $data = json_decode($resp, true);
            if (!is_array($data) || !isset($data['result'][0]) || !is_array($data['result'][0])) {
                $error = 'Neočekávaná odpověď z Quantum API.';
            } else {
                $idxes = $data['result'][0];

                if (count($idxes) < count($deck)) {
                    $error = 'Quantum API vrátilo méně indexů, než je karet v balíčku.';
                } else {
                    // Vygenerujeme zamíchaný balíček
                    $shuffled = [];
                    foreach ($idxes as $i) {
                        if (!array_key_exists($i, $deck)) {
                            // pokud přijde index mimo rozsah, přeskočíme
                            continue;
                        }
                        $shuffled[] = $deck[$i];
                    }

                    if (count($shuffled) < $needed) {
                        $error = 'V zamíchaném balíčku není dost karet pro rozdání.';
                    } else {
                        // rozdání karet
                        $players = [];
                        $pos = 0;
                        for ($p = 0; $p < $playerCount; $p++) {
                            $players[$p] = array_slice($shuffled, $pos, CARDS_PER_PLAYER);
                            $pos += CARDS_PER_PLAYER;
                        }

                        // jedna karta na stůl
                        $startCard = $shuffled[$pos] ?? null;
                        $pos++;

                        // zbytek talon
                        $talon = array_slice($shuffled, $pos);

                        if ($startCard === null) {
                            $error = 'Nepodařilo se získat startovní kartu ze zamíchaného balíčku.';
                        } else {
                            $result = [
                                'players'    => $players,
                                'start_card' => $startCard,
                                'talon'      => $talon,
                                'idxes'      => $idxes,
                            ];
                            $info[] = 'Balíček úspěšně zamíchán přes Quantum API.';
                        }
                    }
                }
            }
        }
    }
}
?>

<main class="page page-dnd">
    <section class="dnd-layout">
        <div class="dnd-column">
            <h1>32 karet – prší demo</h1>
            <p>
                Jednoduché prší pro 2–4 hráče.<br>
                Balíček 32 karet (7–A ve 4 barvách) se zamíchá pomocí Quantum Random API
                a karty se rozdají z vršku balíčku.
            </p>

            <form method="post" class="card" style="margin-top: 1rem;">
                <h2>Nastavení rozdání</h2>

                <label>
                    Počet hráčů (2–4):<br>
                    <input type="number"
                           name="players"
                           min="2"
                           max="4"
                           value="<?= htmlspecialchars((string)$playerCount) ?>"
                           style="margin-top: 0.25rem; padding: 0.25rem 0.5rem;">
                </label>

                <p style="margin-top: 0.75rem;">
                    Každý hráč dostane <?= CARDS_PER_PLAYER ?> karty, jedna karta půjde na stůl,
                    zbytek tvoří talon.
                </p>

                <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">
                    Zamíchat &amp; rozdat
                </button>
            </form>

            <?php if ($error): ?>
                <div class="card" style="margin-top: 1rem; border-left: 4px solid #dc2626;">
                    <h2>Chyba</h2>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="card" style="margin-top: 1rem; border-left: 4px solid #16a34a;">
                    <h2>Info</h2>
                    <ul>
                        <?php foreach ($info as $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="dnd-column">
            <?php if ($result): ?>
                <div class="card">
                    <h2>Rozdané karty</h2>

                    <?php foreach ($result['players'] as $i => $cards): ?>
                        <h3>Hráč <?= $i + 1 ?></h3>
                        <p><?= htmlspecialchars(implode(' · ', $cards)) ?></p>
                    <?php endforeach; ?>

                    <hr style="margin: 1rem 0; border: none; border-top: 1px solid #374151;">

                    <p>
                        <strong>Startovní karta na stole:</strong>
                        <?= htmlspecialchars($result['start_card']) ?>
                    </p>
                    <p>
                        <strong>Počet karet v talonu:</strong>
                        <?= count($result['talon']) ?>
                    </p>
                </div>

                <div class="card" style="margin-top: 1rem;">
                    <h2>Debug – permutace z Quantum API</h2>
                    <p style="font-size: 0.85rem;">
                        Toto je pořadí indexů (0–31), které vrátilo Quantum API
                        jako zamíchaný balíček:
                    </p>
                    <pre style="font-size: 0.8rem; white-space: pre-wrap;">
<?= htmlspecialchars(json_encode($result['idxes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?>
                    </pre>
                </div>
            <?php else: ?>
                <div class="card">
                    <h2>Čekám na rozdání</h2>
                    <p>
                        Zvol počet hráčů vlevo a klikni na
                        <strong>„Zamíchat &amp; rozdat“</strong>.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
