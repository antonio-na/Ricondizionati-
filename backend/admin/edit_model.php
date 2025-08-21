<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$model_id = $_GET['id'] ?? null;
$model_name = '';

if (!$model_id || !filter_var($model_id, FILTER_VALIDATE_INT)) {
    header('Location: manage_models.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_model'])) {
    $name = trim($_POST['model_name']);
    $id_to_update = $_POST['model_id'];

    if (!empty($name) && $id_to_update == $model_id) {
        try {
            // Per ora modifichiamo solo il nome. Categoria e marca restano invariate.
            $stmt = $pdo->prepare("UPDATE models SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id_to_update]);
            $_SESSION['success_message'] = "Modello aggiornato con successo.";
            header('Location: manage_models.php');
            exit();
        } catch (PDOException $e) {
            $error_message = "Errore nel database: " . $e->getMessage();
            $model_name = $name;
        }
    } else {
        $error_message = "Il nome del modello non può essere vuoto.";
        $model_name = $name;
    }
} else {
    $stmt = $pdo->prepare("SELECT name FROM models WHERE id = ?");
    $stmt->execute([$model_id]);
    $model = $stmt->fetch();

    if ($model) {
        $model_name = $model['name'];
    } else {
        header('Location: manage_models.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Modello</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Modifica Modello</h1>
        <p><a href="manage_models.php">Torna alla Gestione Modelli</a></p>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="edit_model.php?id=<?php echo htmlspecialchars($model_id); ?>" method="post">
            <input type="hidden" name="model_id" value="<?php echo htmlspecialchars($model_id); ?>">
            <div class="form-group">
                <label for="model_name">Nome Modello:</label>
                <input type="text" id="model_name" name="model_name" value="<?php echo htmlspecialchars($model_name); ?>" required>
            </div>
            <button type="submit" name="update_model">Aggiorna</button>
        </form>
    </div>
</body>
</html>
