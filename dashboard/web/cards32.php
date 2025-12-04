<?php
// dashboard/web/cards32.php

session_start();

include __DIR__ . '/includes/header.php';

// --- definice 32 karet (prší / mariáš)
// pořadí je jedno, stejně se bude míchat
$cards32 = [
    '7♣', '8♣', '9♣', '10♣', 'J♣', 'Q♣', 'K♣', 'A♣',
    '7♦', '8♦', '9♦', '10♦', 'J♦', 'Q♦', 'K♦', 'A♦',
    '7♥', '8♥', '9♥', '10♥', 'J♥', 'Q♥', 'K♥', 'A♥',
    '7♠', '8♠', '9♠', '10♠', 'J♠', 'Q♠', 'K♠', 'A♠',
];

/**
 * Inicializace nového balíčku do session.
 */
function cards32_init_deck(array $cards32): void
{
    $deck = $cards32;
    shuffle($deck);

    $_SESSION['cards32_deck'] = $deck;
    $_SESSION['cards32_pos']  = 0;   // kolik karet už bylo „sejmuto“
}

/**
 * Získá aktuální balíček z session; pokud neexistuje, vytvoří nový.
 */
function cards32_get_deck(array $cards32): array
{
    if (!isset($_SESSION['cards32_deck'], $_SESSION['cards32_pos'])) {
        cards32_init_deck($cards32);
    }

    return $_SESSION['cards32_deck'];
}

/**
 * Vrátí počet již sebraných karet.
 */
function cards32_get_pos(): int
{
    return isset($_SESSION['cards32_pos']) ? (int)$_SESSION['cards32_pos'] : 0;
}

/**
 * Sejme vrchní kartu – posune pozici o 1, pokud ještě nějaké karty zbývají.
 */
function cards32_draw_card(array $deck): ?string
{
    $pos = cards32_get_pos();
    $count = count($deck);

    if ($pos >= $count) {
        // balík je dobraný
        return null;
    }

    $card = $deck[$pos];
    $_SESSION['cards32_pos'] = $pos + 1;

    return $card;
}

// --- zpracování POST akcí -----------------------------------------------

$deck = cards32_get_deck($cards32);
$lastDrawnCard = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // nový balík
    if (isset($_POST['shuffle_deck'])) {
        cards32_init_deck($cards32);
        $deck = cards32_get_deck($cards32);
    }

    // sejmi vrchní kartu
    if (isset($_POST['draw_card'])) {
        $deck = cards32_get_deck($cards32);
        $lastDrawnCard = cards32_draw_card($deck);
    }
}

// po případném draw/shuffle znovu načteme aktuální stav
$deck      = cards32_get_deck($cards32);
$pos       = cards32_get_pos();
$total     = count($deck);
$remaining = max(0, $total - $pos);

// karty, které už byly sejmuty (pro log)
$drawnCards = array_slice($deck, 0, $pos);
?>

<main class="page page-dnd">
    <section class="dnd-layout">
        <div class="dnd-column">
            <h1>32 karet – balíček</h1>
            <p>Jednou zamícháš, pak postupně sejmíváš vrchní kartu, dokud se balík nedobere.</p>

            <div class="card">
                <h2>Nastavení balíčku</h2>

                <form method="post">
                    <p>
                        <strong>Balíček:</strong> 32 listových karet (7–A, 4 barvy)<br>
                        <strong>Zbývá v balíčku:</strong> <?= (int)$remaining ?> karet
                    </p>

                    <button type="submit" name="shuffle_deck" class="btn-primary">
                        Zamíchat nový balíček
                    </button>

                    <button type="submit"
                            name="draw_card"
                            class="btn-secondary"
                            <?= $remaining === 0 ? 'disabled' : '' ?>>
                        Sejmi vrchní kartu
                    </button>

                    <?php if ($remaining === 0): ?>
                        <p style="margin-top:0.75rem; color:#ff9f9f;">
                            Balík je dobraný. Zamíchej nový balík, pokud chceš pokračovat.
                        </p>
                    <?php endif; ?>
                </form>

                <?php if ($lastDrawnCard !== null): ?>
                    <p style="margin-top:1rem;">
                        Poslední sejmutá karta: <strong><?= htmlspecialchars($lastDrawnCard) ?></strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="dnd-column">
            <h2>Historie sejmutých karet</h2>

            <div class="card">
                <?php if (empty($drawnCards)): ?>
                    <p>Zatím nebyla sejmutá žádná karta.</p>
                <?php else: ?>
                    <table class="tombola-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Karta</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($drawnCards as $i => $card): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($card) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <details style="margin-top:1.5rem;">
                <summary>Nápověda &amp; info</summary>
                <p>
                    Balíček se zamíchá jednou (pomocí Quantum RNG) a pak se z něj postupně sejímá karta
                    po kartě, dokud nedojdou všechny. Hodí se pro RPG systémy používající karty místo kostek
                    nebo jako „deck RNG“ pro různé experimenty.
                </p>
            </details>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
