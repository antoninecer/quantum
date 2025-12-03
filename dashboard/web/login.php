<?php
include __DIR__ . '/includes/tombola_lib.php'; // kvůli $pdo
include __DIR__ . '/includes/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id'       => (int)$user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];

        // po přihlášení kam chceš – třeba na dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Neplatné jméno nebo heslo.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="page page-dnd">
    <section class="dnd-layout">
        <div class="dnd-column">
            <h1>Přihlášení</h1>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="card">
                <div class="form-group">
                    <label>Uživatel</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Heslo</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Přihlásit</button>
                </div>
            </form>
        </div>
    </section>
</main>

<?php
include __DIR__ . '/includes/footer.php';
