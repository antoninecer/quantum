<?php
// dashboard/web/tombola.php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/tombola_lib.php';

/**
 * Načte akce a ceny pro selecty
 */
$events = $pdo->query('SELECT * FROM tombola_events ORDER BY created_at DESC')->fetchAll();

$currentEventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$currentPrizeId = isset($_GET['prize_id']) ? (int)$_GET['prize_id'] : null;

$currentEvent = null;
$currentPrize = null;
$prizes       = [];
$lastDraw     = null;
$message      = null;
$error        = null;

// Logika – vytvoření akce + cen, losování, opakované losování (POST i GET)
$request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$action  = $request['action'] ?? '';

if ($action !== '') {

    if ($action === 'create_event') {
        // vytvoření nové tomboly + cen
        $name        = trim($_POST['event_name'] ?? '');
        $ticketFrom  = (int)($_POST['ticket_from'] ?? 1);
        $ticketTo    = (int)($_POST['ticket_to'] ?? 100);
        $prizeMode   = $_POST['prize_mode'] ?? 'count';
        $prizeCount  = (int)($_POST['prize_count'] ?? 0);
        $prizeList   = trim($_POST['prize_list'] ?? '');

        if ($name === '' || $ticketFrom <= 0 || $ticketTo <= 0 || $ticketFrom > $ticketTo) {
            $error = 'Zkontroluj název akce a rozsah lístků.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO tombola_events (name, ticket_from, ticket_to) VALUES (?, ?, ?)');
                $stmt->execute([$name, $ticketFrom, $ticketTo]);
                $eventId = (int)$pdo->lastInsertId();

                $prizeRows = [];

                if ($prizeMode === 'count' && $prizeCount > 0) {
                    for ($i = 1; $i <= $prizeCount; $i++) {
                        $prizeRows[] = [
                            'name'          => 'Cena ' . $i,
                            'quantity_total'=> 1,
                            'sort_order'    => $i,
                        ];
                    }
                } elseif ($prizeMode === 'list' && $prizeList !== '') {
                    $lines = preg_split('/\r\n|\r|\n/', $prizeList);
                    $order = 1;
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }
                        // jednoduchý formát: "Název|3" => množství 3, jinak 1
                        $qty  = 1;
                        $nameLine = $line;
                        if (strpos($line, '|') !== false) {
                            [$nameLine, $qtyStr] = array_map('trim', explode('|', $line, 2));
                            $qty = max(1, (int)$qtyStr);
                        }
                        $prizeRows[] = [
                            'name'          => $nameLine,
                            'quantity_total'=> $qty,
                            'sort_order'    => $order++,
                        ];
                    }
                }

                if ($prizeRows) {
                    $stmtP = $pdo->prepare(
                        'INSERT INTO tombola_prizes (event_id, name, quantity_total, sort_order) VALUES (?, ?, ?, ?)'
                    );
                    foreach ($prizeRows as $row) {
                        $stmtP->execute([
                            $eventId,
                            $row['name'],
                            $row['quantity_total'],
                            $row['sort_order'],
                        ]);
                    }
                }

                $pdo->commit();
                header('Location: tombola.php?event_id=' . $eventId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Chyba při ukládání akce: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'draw' || $action === 'redraw') {
        $currentEventId = (int)($_POST['event_id'] ?? 0);
        $currentPrizeId = (int)($_POST['prize_id'] ?? 0);

        if ($currentEventId && $currentPrizeId) {
            // načtení eventu + ceny
            $stmt = $pdo->prepare('SELECT * FROM tombola_events WHERE id = ?');
            $stmt->execute([$currentEventId]);
            $currentEvent = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT * FROM tombola_prizes WHERE id = ?');
            $stmt->execute([$currentPrizeId]);
            $currentPrize = $stmt->fetch();

            if (!$currentEvent || !$currentPrize) {
                $error = 'Vybraná akce nebo cena neexistuje.';
            } else {
                // pokud je to "redraw" – poslední platný los pro tuto cenu označíme jako no_show
                if ($action === 'redraw') {
                    $stmt = $pdo->prepare(
                        'SELECT * FROM tombola_draws WHERE prize_id = ? AND status = "valid" ORDER BY created_at DESC LIMIT 1'
                    );
                    $stmt->execute([$currentPrizeId]);
                    $lastValid = $stmt->fetch();
                    if ($lastValid) {
                        $stmtU = $pdo->prepare('UPDATE tombola_draws SET status = "no_show" WHERE id = ?');
                        $stmtU->execute([$lastValid['id']]);
                    }
                }

                // kolik už máme platných výherců pro tuto cenu
                $wins = count_valid_wins($pdo, $currentPrizeId);
                if ($wins >= (int)$currentPrize['quantity_total']) {
                    $error = 'Pro tuto cenu už jsou rozdané všechny kusy.';
                } else {
                    $ticket = draw_unique_ticket($pdo, $currentEvent, $QUANTUM_API_URL);
                    if ($ticket === null) {
                        $error = 'Došly volné lístky v rozsahu akce.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO tombola_draws (event_id, prize_id, ticket_number, status) VALUES (?, ?, ?, "valid")'
                        );
                        $stmt->execute([$currentEventId, $currentPrizeId, $ticket]);

                        $lastDraw = [
                            'ticket_number' => $ticket,
                            'prize_name'    => $currentPrize['name'],
                            'event_name'    => $currentEvent['name'],
                        ];

                        $message = 'Výherní lístek: ' . $ticket;
                    }
                }
            }
        } else {
            $error = 'Vyber akci a cenu, pro kterou chceš losovat.';
        }
    }
    if ($action === 'redraw_draw') {
        $drawId = (int)($request['draw_id'] ?? 0);

        if ($drawId > 0) {
            // vytáhneme původní los
            $stmt = $pdo->prepare('SELECT * FROM tombola_draws WHERE id = ?');
            $stmt->execute([$drawId]);
            $draw = $stmt->fetch();

            if ($draw) {
                $currentEventId = (int)$draw['event_id'];
                $currentPrizeId = (int)$draw['prize_id'];

                // označíme tenhle los jako "no_show", pokud byl valid
                if ($draw['status'] === 'valid') {
                    $stmt = $pdo->prepare('UPDATE tombola_draws SET status = "no_show" WHERE id = ?');
                    $stmt->execute([$drawId]);
                }

            // dál už je to stejná logika jako v tvé větvi "redraw":
            // 1) načíst event a prize
            $stmt = $pdo->prepare('SELECT * FROM tombola_events WHERE id = ?');
            $stmt->execute([$currentEventId]);
            $currentEvent = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT * FROM tombola_prizes WHERE id = ? AND event_id = ?');
            $stmt->execute([$currentPrizeId, $currentEventId]);
            $currentPrize = $stmt->fetch();

            if ($currentEvent && $currentPrize) {
                // zkontrolovat, jestli ještě zbývá kusů
                $wins = count_valid_wins($pdo, $currentPrizeId);
                if ($wins >= (int)$currentPrize['quantity_total']) {
                    $error = 'Pro tuto cenu už jsou rozdané všechny kusy.';
                } else {
                    // vylosovat nový lístek
                    $ticket = draw_unique_ticket($pdo, $currentEvent, $QUANTUM_API_URL);
                    if ($ticket === null) {
                        $error = 'Došly volné lístky v rozsahu akce.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO tombola_draws (event_id, prize_id, ticket_number, status)
                             VALUES (?, ?, ?, "valid")'
                        );
                        $stmt->execute([$currentEventId, $currentPrizeId, $ticket]);

                        $lastDraw = [
                            'ticket_number' => $ticket,
                            'prize_name'    => $currentPrize['name'],
                            'event_name'    => $currentEvent['name'],
                            'status'        => 'valid',
                        ];

                        $message = 'Přelosování úspěšné, nový lístek: ' . $ticket;
                    }
                }
            }
        }
    }
}

}

// pokud máme vybranou akci z GET, načti její ceny + poslední los
if ($currentEventId) {
    $stmt = $pdo->prepare('SELECT * FROM tombola_events WHERE id = ?');
    $stmt->execute([$currentEventId]);
    $currentEvent = $stmt->fetch();

    if ($currentEvent) {
        $stmt = $pdo->prepare('SELECT * FROM tombola_prizes WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$currentEventId]);
        $prizes = $stmt->fetchAll();
        
        $stmt = $pdo->prepare('SELECT * FROM tombola_prizes WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$currentEventId]);
        $prizes = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT d.*, p.name AS prize_name FROM tombola_draws d JOIN tombola_prizes p ON p.id = d.prize_id WHERE d.event_id = ? ORDER BY d.created_at DESC' );
        $stmt->execute([$currentEventId]);
        $draws = $stmt->fetchAll();
        
        // >>> DOPLNIT – aby fungovala podmínka if ($currentPrize) <<<
        if ($currentPrizeId) {
            foreach ($prizes as $pr) {
                if ((int)$pr['id'] === $currentPrizeId) {
                    $currentPrize = $pr;
                    break;
                }
            }
        }
        // <<< KONEC DOPLNĚNÍ >>>


        if ($currentPrizeId) {
            $stmt = $pdo->prepare(
                'SELECT d.*, p.name AS prize_name
                 FROM tombola_draws d
                 JOIN tombola_prizes p ON p.id = d.prize_id
                 WHERE d.prize_id = ?
                 ORDER BY d.created_at DESC
                 LIMIT 1'
            );
            $stmt->execute([$currentPrizeId]);
            $lastDraw = $stmt->fetch();
        }
    }
}
?>

<main class="page page-dnd">
    <section class="dnd-hero">
        <div class="dnd-hero-text">
            <h1>Tombola &nbsp;<span>powered by Quantum RNG</span></h1>
            <p>
                Losování cen pomocí kvantové náhody – bez opakovaných lístků,
                s možností znovu losovat, když se výherce nepřihlásí.
            </p>
        </div>
        <div class="dnd-hero-dice">
            <div class="dice-orbit">
                <div class="dice dice-d20">🎟</div>
                <div class="dice dice-d12">🎁</div>
                <div class="dice dice-d8">🎉</div>
            </div>
        </div>
    </section>

    <section class="dnd-layout">
        <!-- LEVÝ PANEL – nastavení akce a cen -->
        <div class="dnd-panel dnd-config">
            <h2>Správa tomboly</h2>

            <h3>Nová akce</h3>
            <form method="post" class="dnd-form">
                <input type="hidden" name="action" value="create_event">

                <div class="form-group">
                    <label for="event_name">Název akce</label>
                    <input id="event_name" name="event_name" type="text" required
                           placeholder="Firemní večírek 2025">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ticket_from">Lístky od</label>
                        <input id="ticket_from" name="ticket_from" type="number" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="ticket_to">Lístky do</label>
                        <input id="ticket_to" name="ticket_to" type="number" min="1" value="100" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Definice cen</label>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="radio" id="prize_mode_count" name="prize_mode" value="count" checked>
                            <label for="prize_mode_count">Jen počet, očíslované ceny</label>
                            <input type="number" name="prize_count" min="1" max="500" value="10">
                            <p class="hint">Vytvoří se Cena 1, Cena 2, …</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="radio" id="prize_mode_list" name="prize_mode" value="list">
                        <label for="prize_mode_list">Seznam cen (copy &amp; paste)</label>
                        <textarea name="prize_list" rows="4" placeholder="Každá cena na nový řádek&#10;Tričko XL|3&#10;Hrnek Quantum|5"></textarea>
                        <p class="hint">
                            Formát: <code>Název</code> nebo <code>Název|množství</code>.
                            Např. <code>Tričko XL|3</code> = 3 kusy stejné ceny.
                        </p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Vytvořit akci</button>
                </div>
            </form>

            <?php if ($events): ?>
                <hr>
                <h3>Existující akce</h3>
                <form method="get" class="dnd-form">
                    <div class="form-group">
                        <label for="event_id">Vyber akci</label>
                        <select id="event_id" name="event_id" onchange="this.form.submit()">
                            <option value="">– vyber –</option>
                            <?php foreach ($events as $ev): ?>
                                <option value="<?= (int)$ev['id'] ?>"
                                    <?= $currentEventId == $ev['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ev['name']) ?> (<?= (int)$ev['ticket_from'] ?>–<?= (int)$ev['ticket_to'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- PRAVÝ PANEL – losování -->
        <div class="dnd-panel dnd-result">
            <h2>Losování</h2>

            <?php if ($error): ?>
                <div class="result-total" style="color:#c00;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="result-total">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($currentEvent): ?>
                <div class="result-summary">
                    <div class="result-label">Aktuální akce:</div>
                    <div class="result-title">
                        <?= htmlspecialchars($currentEvent['name']) ?>
                        &nbsp; <span>(lístky <?= (int)$currentEvent['ticket_from'] ?>–<?= (int)$currentEvent['ticket_to'] ?>)</span>
                    </div>
                </div>

                <?php if ($prizes): ?>
                    <form method="get" class="dnd-form">
                        <input type="hidden" name="event_id" value="<?= (int)$currentEvent['id'] ?>">
                        <div class="form-group">
                            <label for="prize_id">Cena</label>
                            <select id="prize_id" name="prize_id" onchange="this.form.submit()">
                                <option value="">– vyber cenu –</option>
                                <?php foreach ($prizes as $pr): ?>
                                    <?php
                                    $wins = count_valid_wins($pdo, $pr['id']);
                                    $left = (int)$pr['quantity_total'] - $wins;
                                    ?>
                                    <option value="<?= (int)$pr['id'] ?>"
                                        <?= $currentPrizeId == $pr['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pr['name']) ?>
                                        (zbývá <?= max(0, $left) ?>/<?= (int)$pr['quantity_total'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint">
                                V závorce vidíš, kolik kusů dané ceny ještě zbývá rozlosovat
                                (pořádně i když jsi musel přelosovat kvůli „no show“).
                            </p>
                        </div>
                    </form>

                    <?php if ($currentPrize): ?>
                        <div class="result-summary">
                            <div class="result-label">Aktuální cena:</div>
                            <div class="result-title">
                                <?= htmlspecialchars($currentPrize['name']) ?>
                                &nbsp;<span>(celkem <?= (int)$currentPrize['quantity_total'] ?> ks)</span>
                            </div>
                        </div>

                        <?php
                        $wins = count_valid_wins($pdo, $currentPrize['id']);
                        $left = (int)$currentPrize['quantity_total'] - $wins;
                        ?>

                        <div class="result-total">
                            Zbývá rozlosovat: <strong><?= max(0, $left) ?></strong> ks
                        </div>

                        <?php if ($lastDraw): ?>
                            <div class="dice-row">
                                <div class="dice dice-d20">
                                    <?= (int)$lastDraw['ticket_number'] ?>
                                </div>
                            </div>
                            <div class="result-total">
                                Poslední los:
                                <strong><?= htmlspecialchars($currentPrize['name']) ?></strong>
                                – lístek
                                <strong><?= (int)$lastDraw['ticket_number'] ?></strong>
                                <?php if ($lastDraw['status'] === 'no_show'): ?>
                                    <span>(nevyzvednuto)</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="result-total">
                                Zatím se pro tuto cenu nelosovalo.
                            </div>
                        <?php endif; ?>

                        <form method="post" class="dnd-form">
                            <input type="hidden" name="event_id" value="<?= (int)$currentEvent['id'] ?>">
                            <input type="hidden" name="prize_id" value="<?= (int)$currentPrize['id'] ?>">

                            <div class="form-actions">
                                <?php if ($left > 0): ?>
                                    <button type="submit" name="action" value="draw" class="btn-primary">
                                        Losovat výherní lístek
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn-primary" disabled>
                                        Všechny kusy této ceny jsou rozlosované
                                    </button>
                                <?php endif; ?>

                                <?php if ($lastDraw && $lastDraw['status'] === 'valid' && $left > 0): ?>
                                    <button type="submit" name="action" value="redraw" class="btn-primary" style="margin-left: .5rem;">
                                        Výherce se neozval – losovat znovu
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>

                        <details class="help-details" style="margin-top: 1.5rem;">
                            <summary>Nápověda k průběhu losování</summary>
                            <div class="help-text help-text-cs">
                                <ul>
                                    <li><strong>Losovat výherní lístek</strong> – vytáhne náhodný lístek z intervalu akce, který ještě nikdy nic nevyhrál.</li>
                                    <li><strong>Výherce se neozval – losovat znovu</strong> – poslední lístek se označí jako <em>nevyzvednutý</em> a vytáhne se nový. Stejný lístek už nikdy nic nevyhraje.</li>
                                    <li>Počet zbývajících kusů ceny se počítá jen podle „platných“ výherců, ne podle počtu losování.</li>
                                </ul>
                            </div>
                        </details>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Pro tuto akci zatím nejsou definované žádné ceny.</p>
                <?php endif; ?>

            <?php else: ?>
                <p>Vyber nebo vytvoř akci vlevo a teprve potom můžeš losovat.</p>
            <?php endif; ?>
        </div>
    </section>
    <section class="tombola-history">
        <h2>Přehled losování vybrané akce</h2>

        <div class="card">
            <?php if ($currentEvent): ?>
                <p>
                    Akce: <strong><?= htmlspecialchars($currentEvent['name']) ?></strong><br>
                    Lístky <?= (int)$currentEvent['ticket_from'] ?>–<?= (int)$currentEvent['ticket_to'] ?>
                </p>

                <?php if (!empty($draws)): ?>
                    <table class="tombola-table">
                        <thead>
                        <tr>
                            <th>Čas</th>
                            <th>Lístek</th>
                            <th>Cena</th>
                            <th>Stav</th>
                            <th>Akce</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($draws as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['created_at']) ?></td>
                                <td><?= (int)$d['ticket_number'] ?></td>
                                <td><?= htmlspecialchars($d['prize_name']) ?></td>
                                <td><?= htmlspecialchars($d['status']) ?></td>
                                <td>
                                    <?php if ($d['status'] === 'valid'): ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="action" value="redraw_draw">
                                            <input type="hidden" name="draw_id" value="<?= (int)$d['id'] ?>">
                                            <button type="submit" class="btn-secondary btn-small">
                                                Přelosovat
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-muted">neplatný / no_show</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Pro tuto akci zatím neproběhlo žádné losování.</p>
                <?php endif; ?>

            <?php else: ?>
                <p>Vyber nejdřív akci nahoře, pak se tady zobrazí přehled losů.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
?>

