<?php
// admin_users.php

require_once __DIR__ . '/includes/auth.php';
require_login();

if (!is_admin()) {
    // obyčejné uživatele sem nepustíme
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/tombola_lib.php';
include __DIR__ . '/includes/header.php';

$error   = null;
$success = null;

// zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    // základní validace
    if ($username === '' || $password === '') {
        $error = 'Uživatel i heslo jsou povinné.';
    } elseif (!in_array($role, ['user', 'admin'], true)) {
        $error = 'Neplatná role.';
    } elseif (strlen($password) < 6) {
        $error = 'Heslo by mělo mít alespoň 6 znaků.';
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
                // duplicate key (username už existuje)
                $error = 'Uživatel se stejným jménem už existuje.';
            } else {
                $error = 'Chyba při ukládání uživatele: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// načtení uživatelů pro přehled
$stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll();
?>

<main class="page page-dnd">
    <section class="dnd-layout admin-users">
        <div class="dnd-column">
            <h1>Správa uživatelů</h1>
            <p>Jen pro adminy – zde můžeš přidávat nové loginy pro tombolu a další části dashboardu.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="post" class="card">
                <h2>Nový uživatel</h2>

                <div class="form-group">
                    <label for="username">Uživatelské jméno</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Heslo</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="user">Uživatel (jen své tomboly)</option>
                        <option value="admin">Admin (vše)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Vytvořit uživatele</button>
                </div>
            </form>
        </div>

        <div class="dnd-column">
            <h2>Existující uživatelé</h2>

            <div class="card">
                <?php if ($users): ?>
                    <table class="tombola-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Uživatel</th>
                            <th>Role</th>
                            <th>Vytvořen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['role']) ?></td>
                                <td><?= htmlspecialchars($u['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Zatím nejsou v systému žádní uživatelé.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
