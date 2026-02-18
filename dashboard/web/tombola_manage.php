<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

require_once __DIR__ . '/includes/tombola_lib.php';
include __DIR__ . '/includes/header.php';

$user = current_user();
$error = null;
$success = null;

/* ===============================
   ZPRACOVÁNÍ AKCÍ
================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* === ARCHIVACE === */
    if ($action === 'archive_event') {
        $eventId = (int)$_POST['event_id'];

        if (is_admin()) {
            $stmt = $pdo->prepare('UPDATE tombola_events SET status="archived" WHERE id=?');
            $stmt->execute([$eventId]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE tombola_events SET status="archived" 
                 WHERE id=? AND user_id=?'
            );
            $stmt->execute([$eventId, $user['id']]);
        }

        $success = "Akce byla archivována.";
    }

    /* === SMAZÁNÍ AKCE (hard delete) === */
    if ($action === 'delete_event') {
        $eventId = (int)$_POST['event_id'];

        if (is_admin()) {
            $stmt = $pdo->prepare('DELETE FROM tombola_events WHERE id=?');
            $stmt->execute([$eventId]);
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM tombola_events WHERE id=? AND user_id=?'
            );
            $stmt->execute([$eventId, $user['id']]);
        }

        $success = "Akce byla smazána.";
    }

    /* === PŘIDÁNÍ CENY === */
    if ($action === 'add_prize') {

        $eventId = (int)$_POST['event_id'];
        $name    = trim($_POST['name']);
        $qty     = max(1, (int)$_POST['quantity']);

        $stmt = $pdo->prepare(
            'INSERT INTO tombola_prizes (event_id,name,quantity_total,sort_order)
             VALUES (?,?,?,0)'
        );
        $stmt->execute([$eventId,$name,$qty]);

        $success = "Cena přidána.";
    }
}

/* ===============================
   NAČTENÍ AKCÍ
================================= */

if (is_admin()) {
    $stmt = $pdo->query(
        'SELECT * FROM tombola_events 
         ORDER BY status="active" DESC, created_at DESC'
    );
} else {
    $stmt = $pdo->prepare(
        'SELECT * FROM tombola_events 
         WHERE user_id=? 
         ORDER BY status="active" DESC, created_at DESC'
    );
    $stmt->execute([$user['id']]);
}

$events = $stmt->fetchAll();
?>

<main class="page page-dnd">
<section class="dnd-layout">

<div class="dnd-panel">
<h2>Správa akcí</h2>

<?php if ($error): ?>
<p style="color:#c00"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
<p style="color:#0a0"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if ($events): ?>
<table class="tombola-table">
<thead>
<tr>
<th>Název</th>
<th>Rozsah</th>
<th>Stav</th>
<th>Akce</th>
</tr>
</thead>
<tbody>

<?php foreach ($events as $ev): ?>
<tr>
<td><?= htmlspecialchars($ev['name']) ?></td>
<td><?= (int)$ev['ticket_from'] ?>–<?= (int)$ev['ticket_to'] ?></td>
<td><?= htmlspecialchars($ev['status']) ?></td>
<td>

<a href="tombola.php?event_id=<?= $ev['id'] ?>" class="btn-secondary btn-small">
Losovat
</a>

<?php if ($ev['status']==='active'): ?>
<form method="post" style="display:inline">
<input type="hidden" name="action" value="archive_event">
<input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
<button class="btn-secondary btn-small">Archivovat</button>
</form>
<?php endif; ?>

<form method="post" style="display:inline"
onsubmit="return confirm('Opravdu smazat akci?');">
<input type="hidden" name="action" value="delete_event">
<input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
<button class="btn-danger btn-small">Smazat</button>
</form>

</td>
</tr>

<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
<p>Žádné akce.</p>
<?php endif; ?>

</div>

</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
