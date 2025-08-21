<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_models.php');
    exit();
}

$pdo = Database::getConnection();
$model_id = $_POST['id'] ?? null;

if (!$model_id || !filter_var($model_id, FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "ID non valido.";
    header('Location: manage_models.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM models WHERE id = ?");
    $stmt->execute([$model_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Modello eliminato con successo.";
    } else {
        $_SESSION['error_message'] = "Nessun modello trovato con questo ID.";
    }
} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
         $_SESSION['error_message'] = "Impossibile eliminare il modello perché è attualmente in uso in una o più tabelle di prezzi.";
    } else {
         $_SESSION['error_message'] = "Errore nel database: " . $e->getMessage();
    }
}

header('Location: manage_models.php');
exit();
?>
