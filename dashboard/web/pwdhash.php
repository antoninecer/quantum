<?php
// password_hasher.php

declare(strict_types=1);

$hash  = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === '') {
        $error = 'Zadejte heslo.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            $error = 'Došlo k chybě při generování hashe.';
        }
    }
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>Generátor hashů hesel (password_hash)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f3f4f6;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            padding: 1.5rem 2rem 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }
        h1 {
            margin-top: 0;
            font-size: 1.4rem;
        }
        label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        input[type="password"],
        input[type="text"] {
            width: 100%;
            box-sizing: border-box;
            padding: 0.5rem 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            font-family: monospace;
            font-size: 0.95rem;
        }
        input[readonly] {
            background: #f9fafb;
        }
        button {
            padding: 0.45rem 0.9rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }
        .btn-secondary {
            background: #e5e7eb;
        }
        .row {
            margin-bottom: 1rem;
        }
        .error {
            color: #b91c1c;
            margin-bottom: 0.75rem;
        }
        .copy-status {
            margin-left: 0.5rem;
            font-size: 0.85rem;
        }
        .hint {
            font-size: 0.85rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Generátor hashů hesel (password_hash)</h1>

    <form method="post">
        <div class="row">
            <label for="password">Heslo:</label>
            <input type="password" name="password" id="password" required>
            <div class="hint">
                Heslo se nikam neukládá, používá se jen lokálně k vygenerování hashe.
            </div>
        </div>

        <div class="row">
            <button type="submit" class="btn-primary">Vygenerovat hash</button>
        </div>
    </form>

    <?php if ($error !== null): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($hash !== null && $error === null): ?>
        <div class="row">
            <label for="hash">Vygenerovaný hash:</label>
            <input type="text"
                   id="hash"
                   readonly
                   value="<?php echo htmlspecialchars($hash, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
        </div>

        <div class="row">
            <button type="button" class="btn-secondary" onclick="copyHash()">Kopírovat do schránky</button>
            <span id="copy-status" class="copy-status" style="display:none;">Zkopírováno ✅</span>
        </div>

        <div class="row hint">
            V PHP se použije například:<br>
            <code>
                password_verify('TajneHeslo1', '&lt;sem vlož tento hash&gt;');
            </code>
        </div>
    <?php endif; ?>
</div>

<script>
function copyHash() {
    const input = document.getElementById('hash');
    const status = document.getElementById('copy-status');
    if (!input) return;

    const text = input.value;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
            status.style.display = 'inline';
        }).catch(function () {
            fallbackCopy(input, status);
        });
    } else {
        fallbackCopy(input, status);
    }
}

function fallbackCopy(input, status) {
    input.focus();
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        document.execCommand('copy');
        status.style.display = 'inline';
    } catch (e) {
        alert('Nepodařilo se zkopírovat do schránky, zkopírujte prosím ručně.');
    }
}
</script>
</body>
</html>
