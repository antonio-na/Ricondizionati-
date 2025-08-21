<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

// Controlla che la richiesta sia di tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_capacities.php');
    exit();
}

$pdo = Database::getConnection();
$capacity_id = $_POST['id'] ?? null;

// Se l'ID non è valido, reindirizza
if (!$capacity_id || !filter_var($capacity_id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "ID non valido.";
    header('Location: manage_capacities.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM capacities WHERE id = ?");
    $stmt->execute([$capacity_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Capacità eliminata con successo.";
    } else {
        $_SESSION['error_message'] = "Nessuna capacità trovata con questo ID.";
    }
} catch (PDOException $e) {
    // Gestisce errori di vincoli di integrità referenziale
    // Se una capacità è usata nella tabella `prices`, non può essere eliminata.
    if ($e->getCode() == '23000') {
         $_SESSION['error_message'] = "Impossibile eliminare la capacità perché è attualmente in uso in una o più tabelle di prezzi.";
    } else {
         $_SESSION['error_message'] = "Errore nel database: " . $e->getMessage();
    }
}

header('Location: manage_capacities.php');
exit();
?>
