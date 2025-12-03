<?php
// dashboard/web/tombola_tazene.php

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/tombola_lib.php';

// nastavíme časové pásmo na Evropu/Prahu
date_default_timezone_set('Europe/Prague');

$refreshSeconds = 30; // interval automatického obnovení v sekundách

$code  = trim($_GET['code'] ?? '');
$event = null;
$draws = [];

if ($code !== '') {
    $stmt = $pdo->prepare('SELECT * FROM tombola_events WHERE public_code = ?');
    $stmt->execute([$code]);
    $event = $stmt->fetch();

    if ($event) {
        $stmt = $pdo->prepare(
            'SELECT 
                 td.ticket_number,
                 td.created_at,
                 p.name AS prize_name,
                 td.status
             FROM tombola_draws td
             JOIN tombola_prizes p ON p.id = td.prize_id
             WHERE td.event_id = ?
               AND td.status = "valid"
             ORDER BY td.created_at ASC, p.sort_order ASC, p.id ASC'
        );
        $stmt->execute([$event['id']]);
        $draws = $stmt->fetchAll();
    }
}

// čas načtení stránky
$loadedAt = date('d.m.Y H:i:s');
?>

<main class="page page-dnd">
    <section class="tombola-history">
        <h1>Výsledky tomboly</h1>
        <p style="margin-top:0.25rem; opacity:0.9;">
            Aktualizace: <?= htmlspecialchars($loadedAt) ?>
            (auto refresh každých <?= (int)$refreshSeconds ?> s)
        </p>

        <div class="card">
            <?php if ($code === ''): ?>
                <p>Chybí kód akce v URL. Ověř si odkaz.</p>

            <?php elseif (!$event): ?>
                <p>Akce s tímto kódem nebyla nalezena.</p>

            <?php else: ?>
                <p>
                    Akce:
                    <strong><?= htmlspecialchars($event['name']) ?></strong><br>
                    Lístky <?= (int)$event['ticket_from'] ?>–<?= (int)$event['ticket_to'] ?><br>
                </p>

                <?php if ($draws): ?>
                    <table class="tombola-table">
                        <thead>
                        <tr>
                            <th>Čas losování</th>
                            <th>Lístek</th>
                            <th>Cena</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($draws as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['created_at']) ?></td>
                                <td><?= (int)$d['ticket_number'] ?></td>
                                <td><?= htmlspecialchars($d['prize_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Zatím nebyly vylosovány žádné platné výhry.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
// automatické obnovení výsledkovky každých N sekund
setInterval(function () {
    window.location.reload();
}, <?= (int)$refreshSeconds * 1000 ?>); // ms
</script>

<?php
include __DIR__ . '/includes/footer.php';
