<?php
// dashboard/web/cards52.php

session_start();

include __DIR__ . '/includes/header.php';

// --- definice 52 karet (klasické pokerové karty) ---
$cardsBase52 = [
    '2♣','3♣','4♣','5♣','6♣','7♣','8♣','9♣','10♣','J♣','Q♣','K♣','A♣',
    '2♦','3♦','4♦','5♦','6♦','7♦','8♦','9♦','10♦','J♦','Q♦','K♦','A♦',
    '2♥','3♥','4♥','5♥','6♥','7♥','8♥','9♥','10♥','J♥','Q♥','K♥','A♥',
    '2♠','3♠','4♠','5♠','6♠','7♠','8♠','9♠','10♠','J♠','Q♠','K♠','A♠',
];

/**
 * Přidá žolíky podle volby uživatele
 */
function cards52_add_jokers(array $deck, int $jokerCount): array
{
    if ($jokerCount === 1) {
        $deck[] = 'Joker★';
    } elseif ($jokerCount === 2) {
        $deck[] = 'Joker♥';
        $deck[] = 'Joker♠';
    }
    return $deck;
}

/**
 * Inicializace nového balíčku
 */
function cards52_init_deck(array $cardsBase52, int $jokerCount = 0): void
{
    $deck = cards52_add_jokers($cardsBase52, $jokerCount);
    shuffle($deck);

    $_SESSION['cards52_deck']   = $deck;
    $_SESSION['cards52_pos']    = 0;
    $_SESSION['cards52_jokers'] = $jokerCount;
}

/**
 * Získá balík z session, jinak vytvoří nový
 */
function cards52_get_deck(array $cardsBase52): array
{
    $jokerCount = $_SESSION['cards52_jokers'] ?? 0;

    if (!isset($_SESSION['cards52_deck'], $_SESSION['cards52_pos'])) {
        cards52_init_deck($cardsBase52, $jokerCount);
    }

    return $_SESSION['cards52_deck'];
}

/**
 * Počet sejmutých karet
 */
function cards52_get_pos(): int
{
    return $_SESSION['cards52_pos'] ?? 0;
}

/**
 * Sejímání vrchní karty
 */
function cards52_draw_card(array $deck): ?string
{
    $pos = cards52_get_pos();
    if ($pos >= count($deck)) {
        return null;
    }

    $_SESSION['cards52_pos'] = $pos + 1;
    return $deck[$pos];
}


// === Výchozí stav ===

$deck       = cards52_get_deck($cardsBase52);
$pos        = cards52_get_pos();
$total      = count($deck);
$remaining  = max(0, $total - $pos);

$canShuffle = ($pos === 0 || $remaining === 0);
$canDraw    = ($remaining > 0);

$lastDrawnCard = null;


// === POST akce ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // zamíchání balíčku
    if (isset($_POST['shuffle_deck']) && $canShuffle) {
        $jokerCount = isset($_POST['jokers']) ? (int)$_POST['jokers'] : 0;
        cards52_init_deck($cardsBase52, $jokerCount);
    }

    // sejmutí vrchní karty
    if (isset($_POST['draw_card']) && $canDraw) {
        $deck          = cards52_get_deck($cardsBase52);
        $lastDrawnCard = cards52_draw_card($deck);
    }

    // přepočítání
    $deck       = cards52_get_deck($cardsBase52);
    $pos        = cards52_get_pos();
    $total      = count($deck);
    $remaining  = max(0, $total - $pos);
    $canShuffle = ($pos === 0 || $remaining === 0);
    $canDraw    = ($remaining > 0);
}

$drawnCards = array_slice($deck, 0, $pos);

?>

<main class="page page-dnd">
    <section class="dnd-layout">

        <!-- LEVÁ STRANA -->
        <div class="dnd-column">
            <h1>52 karet – balíček</h1>
            <p>Míchání 52karetního balíčku (s volbou žolíků) a postupné sejímání karet.</p>

            <div class="card">
                <h2>Nastavení</h2>

                <form method="post">
                    <p><strong>Balíček:</strong> <?= $total ?> karet</p>
                    <p><strong>Zbývá:</strong> <?= $remaining ?> karet</p>

                    <!-- Volba žolíků -->
                    <label><input type="radio" name="jokers" value="0" <?= (!isset($_SESSION['cards52_jokers']) || $_SESSION['cards52_jokers'] == 0) ? 'checked' : '' ?>> Bez žolíků</label><br>
                    <label><input type="radio" name="jokers" value="1" <?= (isset($_SESSION['cards52_jokers']) && $_SESSION['cards52_jokers'] == 1) ? 'checked' : '' ?>> 1 žolík</label><br>
                    <label><input type="radio" name="jokers" value="2" <?= (isset($_SESSION['cards52_jokers']) && $_SESSION['cards52_jokers'] == 2) ? 'checked' : '' ?>> 2 žolíci</label><br><br>

                    <button type="submit"
                            name="shuffle_deck"
                            class="btn-primary"
                        <?= $canShuffle ? '' : 'disabled' ?>>
                        Zamíchat balíček
                    </button>

                    <button type="submit"
                            name="draw_card"
                            class="btn btn-secondary"
                        <?= $canDraw ? '' : 'disabled' ?>>
                        Sejmi vrchní kartu
                    </button>

                    <?php if (!$canShuffle): ?>
                        <p style="margin-top:0.7rem;color:#ffcf9f;font-size:0.9rem;">
                            Probíhá aktuální balík – zamíchat lze až po dohrání všech karet.
                        </p>
                    <?php endif; ?>

                    <?php if ($remaining === 0 && $pos > 0): ?>
                        <p style="margin-top:0.7rem;color:#ff9f9f;font-size:0.9rem;">
                            Balík je dobraný. Můžeš zamíchat nový.
                        </p>
                    <?php endif; ?>
                </form>

                <?php if ($lastDrawnCard): ?>
                    <p style="margin-top:1rem;">
                        Poslední sejmutá karta: <strong><?= htmlspecialchars($lastDrawnCard) ?></strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- PRAVÁ STRANA -->
        <div class="dnd-column">
            <h2>Sejmuté karty</h2>

            <div class="card">
                <?php if (empty($drawnCards)): ?>
                    <p>Zatím žádné.</p>
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
                <summary>Nápověda</summary>
                <p>
                    Po zamíchání lze sejímat karty do vyčerpání balíku.
                    Volba žolíků ovlivní celkový počet karet (52 / 53 / 54).
                </p>
            </details>

        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
