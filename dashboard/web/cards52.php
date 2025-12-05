<?php
// dashboard/web/cards52.php

session_start();

include __DIR__ . '/includes/header.php';

// --- definice 52 karet (klasické poker/kasíno) ---
$cards52 = [
    '2♣','3♣','4♣','5♣','6♣','7♣','8♣','9♣','10♣','J♣','Q♣','K♣','A♣',
    '2♦','3♦','4♦','5♦','6♦','7♦','8♦','9♦','10♦','J♦','Q♦','K♦','A♦',
    '2♥','3♥','4♥','5♥','6♥','7♥','8♥','9♥','10♥','J♥','Q♥','K♥','A♥',
    '2♠','3♠','4♠','5♠','6♠','7♠','8♠','9♠','10♠','J♠','Q♠','K♠','A♠',
];

/**
 * Inicializace nového balíčku do session.
 */
function cards52_init_deck(array $cards52): void
{
    $deck = $cards52;
    shuffle($deck);

    $_SESSION['cards52_deck'] = $deck;
    $_SESSION['cards52_pos']  = 0;
}

/**
 * Vrátí aktuální balíček nebo jej inicializuje.
 */
function cards52_get_deck(array $cards52): array
{
    if (!isset($_SESSION['cards52_deck'], $_SESSION['cards52_pos'])) {
        cards52_init_deck($cards52);
    }
    return $_SESSION['cards52_deck'];
}

/**
 * Vrátí počet sejmutých karet.
 */
function cards52_get_pos(): int
{
    return isset($_SESSION['cards52_pos']) ? (int)$_SESSION['cards52_pos'] : 0;
}

/**
 * Sejmi vrchní kartu.
 */
function cards52_draw_card(array $deck): ?string
{
    $pos   = cards52_get_pos();
    $count = count($deck);

    if ($pos >= $count) {
        return null;
    }

    $_SESSION['cards52_pos'] = $pos + 1;
    return $deck[$pos];
}

// --- výpočet stavu před akcí ---
$deck       = cards32_get_deck($cards32);
$pos        = cards32_get_pos();
$total      = count($deck);
$remaining  = max(0, $total - $pos);

$canShuffle = ($pos === 0 || $remaining === 0);
$canDraw    = ($remaining > 0);   // ← Tohle tam musí být!

$drawnCards = array_slice($deck, 0, $pos);

$lastDrawnCard = null;

// --- POST akce ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // nové zamíchání — jen pokud je povoleno
    if (isset($_POST['shuffle_deck']) && $canShuffle) {
        cards52_init_deck($cards52);
    }

    // sejmi vrchní kartu
    if (isset($_POST['draw_card']) && $remaining > 0) {
        $deck = cards52_get_deck($cards52);
        $lastDrawnCard = cards52_draw_card($deck);
    }

    // přepočítání po akci
    $deck       = cards52_get_deck($cards52);
    $pos        = cards52_get_pos();
    $remaining  = max(0, $total - $pos);
    $canShuffle = ($pos === 0 || $remaining === 0);
    $canDraw    = ($remaining > 0);
}

$drawnCards = array_slice($deck, 0, cards52_get_pos());
?>

<main class="page page-dnd">
    <section class="dnd-layout">

        <!-- LEVÁ STRANA -->
        <div class="dnd-column">
            <h1>52 karet – balíček</h1>
            <p>Míchání klasického 52karetního balíčku a postupné sejímání karet.</p>

            <div class="card">
                <h2>Nastavení</h2>

                <form method="post">
                    <p>
                        <strong>Balíček:</strong> 52 karet (2–A ve 4 barvách)<br>
                        <strong>Zbývá:</strong> <?= $remaining ?> karet
                    </p>

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

                    <?php if (!$canShuffle && $remaining > 0): ?>
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
                        Poslední sejmutá karta:
                        <strong><?= htmlspecialchars($lastDrawnCard) ?></strong>
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
                    Po zamíchání lze kartu sejímat do vyčerpání balíku.
                    Balík je uchován v session — po dohrání se znovu zamíchá.
                </p>
            </details>
        </div>

    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
:wq
