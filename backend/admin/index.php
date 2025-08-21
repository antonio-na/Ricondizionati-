<?php
// Includi la logica di autenticazione all'inizio di ogni pagina protetta
require_once __DIR__ . '/../core/auth.php';

// Avvia la sessione e verifica che l'utente sia loggato
start_secure_session();
require_login();

// Se si arriva qui, l'utente è autenticato.
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
</head>
<body>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Dashboard Amministratore</h1>
        <p>Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>!</p>
        <p>Da qui puoi gestire i contenuti del sito di valutazione.</p>

        <nav class="admin-nav">
            <h2>Gestione Contenuti</h2>
            <ul>
                <li><a href="manage_capacities.php">Gestisci Capacità</a></li>
                <li><a href="manage_conditions.php">Gestisci Stati di Conservazione</a></li>
                <li><a href="manage_models.php">Gestisci Modelli</a></li>
            </ul>
            <h2>Gestione Valutazioni</h2>
            <ul>
                <li><a href="manage_prices.php">Gestisci Prezzi</a></li>
                <li><a href="manage_requests.php">Visualizza Richieste</a></li>
            </ul>
        </nav>

        <p style="margin-top: 20px;"><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
