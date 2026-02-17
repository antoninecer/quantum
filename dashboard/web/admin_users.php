<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

if (!is_admin()) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/tombola_lib.php';
include __DIR__ . '/includes/header.php';

$error   = null;
$success = null;

$currentUser = current_user(); // přihlášený admin

/* ==============================
   ZPRACOVÁNÍ FORMULÁŘŮ
   ============================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* === 1) VYTVOŘENÍ UŽIVATELE === */
    if (isset($_POST['action']) && $_POST['action'] === 'create') {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'user';

        if ($username === '' || $password === '') {
            $error = 'Uživatel i heslo jsou povinné.';
        } elseif (!in_array($role, ['user', 'admin'], true)) {
            $error = 'Neplatná role.';
        } elseif (strlen($password) < 6) {
            $error = 'Heslo musí mít alespoň 6 znaků.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)'
                );
                $stmt->execute([$username, $hash, $role]);

                $success = 'Uživatel "' . htmlspecialchars($username) . '" byl vytvořen.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'Uživatel se stejným jménem už existuje.';
                } else {
                    $error = 'Chyba při ukládání uživatele.';
                }
            }
        }
    }

    /* === 2) ZMĚNA HESLA === */
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {

        $userId   = (int)($_POST['user_id'] ?? 0);
        $password = $_POST['new_password'] ?? '';

        if ($password === '' || strlen($password) < 6) {
            $error = 'Nové heslo musí mít alespoň 6 znaků.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $userId]);

            $success = 'Heslo bylo změněno.';
        }
    }

    /* === 3) SMAZÁNÍ UŽIVATELE === */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {

        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId === (int)$currentUser['id']) {
            $error = 'Nemůžeš smazat sám sebe.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);

            $success = 'Uživatel byl smazán.';
        }
    }
}

/* ==============================
   NAČTENÍ UŽIVATELŮ
   ============================== */

$stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();
?>

<main class="page page-dnd">
<section class="dnd-layout admin-users">

<div class="dnd-column">
    <h1>Správa uživatelů</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post" class="card">
        <h2>Nový uživatel</h2>

        <input type="hidden" name="action" value="create">

        <div class="form-group">
            <label>Uživatelské jméno</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Heslo</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="user">Uživatel</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <button type="submit" class="btn-primary">Vytvořit</button>
    </form>
</div>

<div class="dnd-column">
    <h2>Existující uživatelé</h2>

    <div class="card">
        <table class="tombola-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Uživatel</th>
                <th>Role</th>
                <th>Vytvořen</th>
                <th>Akce</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                    <td>

                        <!-- Změna hesla -->
                        <form method="post" style="margin-bottom:6px;">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="password" name="new_password" placeholder="Nové heslo" required>
                            <button type="submit" class="btn btn-secondary">Změnit</button>
                        </form>

                        <!-- Smazání -->
                        <?php if ($u['id'] != $currentUser['id']): ?>
                            <form method="post" onsubmit="return confirm('Opravdu smazat uživatele?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger">Smazat</button>
                            </form>
                        <?php else: ?>
                            <small>(nelze smazat sebe)</small>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
