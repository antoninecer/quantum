<?php
// dashboard/web/cards32.php

session_start();

include __DIR__ . '/includes/header.php';

// --- definice 32 karet (prší / mariáš) ---
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
    $_SESSION['cards32_pos']  = 0;   // kolik karet už bylo sejmutých
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
 * Vrátí počet již sejmutých karet.
 */
function cards32_get_pos(): int
{
    return isset($_SESSION['cards32_pos']) ? (int)$_SESSION['cards32_pos'] : 0;
}

/**
 * Sejme vrchní kartu – posune pozici o 1, pokud ještě nějaké karty zbývají.
 * Vrátí text karty, nebo null, pokud je balík dobraný.
 */
function cards32_draw_card(array $deck): ?string
{
    $pos   = cards32_get_pos();
    $count = count($deck);

    if ($pos >= $count) {
        return null; // balík je dobraný
    }

    $card = $deck[$pos];
    $_SESSION['cards32_pos'] = $pos + 1;

    return $card;
}

// --- stav před zpracováním POSTu ---
$deck      = cards32_get_deck($cards32);
$pos       = cards32_get_pos();
$total     = count($deck);
$remaining = max(0, $total - $pos);
$canShuffle = ($pos === 0 || $remaining === 0);  // smíme míchat jen na začátku / po dohrání

$lastDrawnCard = null;

// --- zpracování formuláře ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // nový balíček – povoleno jen když $canShuffle = true
    if (isset($_POST['shuffle_deck']) && $canShuffle) {
        cards32_init_deck($cards32);
        $deck      = cards32_get_deck($cards32);
        $pos       = cards32_get_pos();
        $total     = count($deck);
        $remaining = max(0, $total - $pos);
        $canShuffle = ($pos === 0 || $remaining === 0);
    }

    // sejmi vrchní kartu – jen pokud ještě nějaká je
    if (isset($_POST['draw_card']) && $remaining > 0) {
        $deck          = cards32_get_deck($cards32);
        $lastDrawnCard = cards32_draw_card($deck);

        $pos       = cards32_get_pos();
        $total     = count($deck);
        $remaining = max(0, $total - $pos);
        $canShuffle = ($pos === 0 || $remaining === 0);
    }
}

// po akcích znovu spočítáme sejmuté karty
$deck       = cards32_get_deck($cards32);
$pos        = cards32_get_pos();
$total      = count($deck);
$remaining  = max(0, $total - $pos);

$canShuffle = ($pos === 0 || $remaining === 0);
$canDraw    = ($remaining > 0);

$drawnCards = array_slice($deck, 0, $pos);

?>
<main class="page page-dnd">
    <section class="dnd-layout">
        <div class="dnd-column">
            <h1>32 karet – balíček</h1>
            <p>Jednou zamícháš, pak postupně snímáš vrchní kartu, dokud se balík nedobere.</p>

            <div class="card">
                <h2>Nastavení balíčku</h2>

                <form method="post">
                    <p>
                        <strong>Balíček:</strong> 32 listových karet (7–A, 4 barvy)<br>
                        <strong>Zbývá v balíčku:</strong> <?= (int)$remaining ?> karet
                    </p>

                    <button type="submit"
                            name="shuffle_deck"
                            class="btn-primary"
                            <?= $canShuffle ? '' : 'disabled' ?>>
                        Zamíchat nový balíček
                    </button>

                    <button type="submit"
                            name="draw_card"
                            class="btn btn-secondary"
                            <?= $canDraw ? '' : 'disabled' ?>>
                        Sejmi kartu
                    </button>

                    <?php if (!$canShuffle && $remaining > 0): ?>
                        <p style="margin-top:0.75rem; color:#ffcf9f; font-size:0.9rem;">
                            Probíhá aktuální balíček (zbývá <?= (int)$remaining ?> karet).<br>
                            Nově zamíchat lze až po dohrání všech karet.
                        </p>
                    <?php endif; ?>

                    <?php if ($remaining === 0 && $pos > 0): ?>
                        <p style="margin-top:0.75rem; color:#ff9f9f; font-size:0.9rem;">
                            Balík je dobraný. Můžeš zamíchat nový balíček.
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
                    <div class="cards-grid">
                        <?php foreach ($drawnCards as $i => $card): ?>
                            <div class="cards-grid-item">
                                <span class="cards-grid-index"><?= $i + 1 ?></span>
                                <span class="cards-grid-value"><?= htmlspecialchars($card) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <details style="margin-top:1.5rem;">
                <summary>Nápověda &amp; info</summary>
                <p>
                    Balíček se zamíchá jednou (pomocí náhody z Quantum RNG) a pak se z něj postupně sejímá
                    karta po kartě, dokud nedojdou všechny. Hodí se pro RPG systémy používající karty místo
                    kostek, nebo jako univerzální „deck RNG“ pro různé hry a experimenty.
                </p>
            </details>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
