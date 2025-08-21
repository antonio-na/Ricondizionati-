<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$success_message = '';
$capacity_id = $_GET['id'] ?? null;
$capacity_value = '';

// Se l'ID non è presente o non è un numero, reindirizza
if (!$capacity_id || !filter_var($capacity_id, FILTER_VALIDATE_INT)) {
    header('Location: manage_capacities.php');
    exit();
}

// Gestione dell'aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_capacity'])) {
    $new_value = trim($_POST['capacity_value']);
    $id_to_update = $_POST['capacity_id'];

    if (!empty($new_value) && $id_to_update == $capacity_id) {
        try {
            $stmt = $pdo->prepare("UPDATE capacities SET value = ? WHERE id = ?");
            $stmt->execute([$new_value, $id_to_update]);
            // Usiamo la sessione per passare il messaggio di successo dopo il redirect
            $_SESSION['success_message'] = "Capacità aggiornata con successo.";
            header('Location: manage_capacities.php');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Errore: Esiste già una capacità con questo valore.";
            } else {
                $error_message = "Errore nel database: " . $e->getMessage();
            }
            $capacity_value = $new_value; // Mantieni il valore inserito nel form
        }
    } else {
        $error_message = "Il campo non può essere vuoto.";
        $capacity_value = $new_value;
    }
} else {
    // Caricamento del valore esistente
    $stmt = $pdo->prepare("SELECT value FROM capacities WHERE id = ?");
    $stmt->execute([$capacity_id]);
    $capacity = $stmt->fetch();

    if ($capacity) {
        $capacity_value = $capacity['value'];
    } else {
        // Se non trova la capacità, reindirizza
        header('Location: manage_capacities.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Capacità</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Modifica Capacità</h1>
        <p><a href="manage_capacities.php">Torna alla Gestione Capacità</a></p>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="edit_capacity.php?id=<?php echo htmlspecialchars($capacity_id); ?>" method="post">
            <input type="hidden" name="capacity_id" value="<?php echo htmlspecialchars($capacity_id); ?>">
            <div class="form-group">
                <label for="capacity_value">Valore:</label>
                <input type="text" id="capacity_value" name="capacity_value" value="<?php echo htmlspecialchars($capacity_value); ?>" required>
            </div>
            <button type="submit" name="update_capacity">Aggiorna</button>
        </form>
    </div>
</body>
</html>
