<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$condition_id = $_GET['id'] ?? null;
$condition_data = ['name' => '', 'description' => ''];

if (!$condition_id || !filter_var($condition_id, FILTER_VALIDATE_INT)) {
    header('Location: manage_conditions.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_condition'])) {
    $name = trim($_POST['condition_name']);
    $description = trim($_POST['condition_description']);
    $id_to_update = $_POST['condition_id'];

    if (!empty($name) && $id_to_update == $condition_id) {
        try {
            $stmt = $pdo->prepare("UPDATE conditions SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id_to_update]);
            $_SESSION['success_message'] = "Stato aggiornato con successo.";
            header('Location: manage_conditions.php');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Errore: Esiste già uno stato con questo nome.";
            } else {
                $error_message = "Errore nel database: " . $e->getMessage();
            }
            $condition_data = ['name' => $name, 'description' => $description];
        }
    } else {
        $error_message = "Il nome dello stato non può essere vuoto.";
        $condition_data = ['name' => $name, 'description' => $description];
    }
} else {
    $stmt = $pdo->prepare("SELECT name, description FROM conditions WHERE id = ?");
    $stmt->execute([$condition_id]);
    $condition = $stmt->fetch();

    if ($condition) {
        $condition_data = $condition;
    } else {
        header('Location: manage_conditions.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Stato di Conservazione</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Modifica Stato di Conservazione</h1>
        <p><a href="manage_conditions.php">Torna alla Gestione Stati</a></p>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="edit_condition.php?id=<?php echo htmlspecialchars($condition_id); ?>" method="post">
            <input type="hidden" name="condition_id" value="<?php echo htmlspecialchars($condition_id); ?>">
            <div class="form-group">
                <label for="condition_name">Nome:</label>
                <input type="text" id="condition_name" name="condition_name" value="<?php echo htmlspecialchars($condition_data['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="condition_description">Descrizione:</label>
                <textarea id="condition_description" name="condition_description" rows="3"><?php echo htmlspecialchars($condition_data['description']); ?></textarea>
            </div>
            <button type="submit" name="update_condition">Aggiorna</button>
        </form>
    </div>
</body>
</html>
