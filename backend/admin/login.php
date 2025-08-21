<?php
require_once __DIR__ . '/../core/auth.php';

// Avvia la sessione sicura
start_secure_session();

// Se l'utente è già loggato, reindirizzalo alla dashboard dell'admin
if (is_logged_in()) {
    header('Location: index.php'); // Assumendo che la pagina principale dell'admin sia index.php
    exit();
}

$error_message = '';

// Controlla se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        // Login riuscito, reindirizza alla dashboard
        header('Location: index.php');
        exit();
    } else {
        // Login fallito
        $error_message = 'Username o password non validi.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h1>Login Amministratore</h1>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Accedi</button>
        </form>
    </div>
</body>
</html>
