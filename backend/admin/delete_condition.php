<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_conditions.php');
    exit();
}

$pdo = Database::getConnection();
$condition_id = $_POST['id'] ?? null;

if (!$condition_id || !filter_var($condition_id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "ID non valido.";
    header('Location: manage_conditions.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM conditions WHERE id = ?");
    $stmt->execute([$condition_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Stato di conservazione eliminato con successo.";
    } else {
        $_SESSION['error_message'] = "Nessuno stato trovato con questo ID.";
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
         $_SESSION['error_message'] = "Impossibile eliminare lo stato perché è attualmente in uso in una o più tabelle di prezzi.";
    } else {
         $_SESSION['error_message'] = "Errore nel database: " . $e->getMessage();
    }
}

header('Location: manage_conditions.php');
exit();
?>
