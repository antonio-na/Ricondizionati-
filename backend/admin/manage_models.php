<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$success_message = '';

// Gestione messaggi di sessione
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Gestione aggiunta nuovo modello
// Per ora, associamo sempre a Categoria 1 (Smartphone) e Marca 1 (Apple)
const DEFAULT_CATEGORY_ID = 1;
const DEFAULT_BRAND_ID = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_model'])) {
    $name = trim($_POST['model_name']);

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO models (name, category_id, brand_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, DEFAULT_CATEGORY_ID, DEFAULT_BRAND_ID]);
            $success_message = "Modello '{$name}' aggiunto con successo.";
        } catch (PDOException $e) {
            $error_message = "Errore nel database: " . $e->getMessage();
        }
    } else {
        $error_message = "Il nome del modello non può essere vuoto.";
    }
}

// Lettura di tutti i modelli (con join per mostrare categoria e marca)
$stmt = $pdo->query("
    SELECT m.id, m.name, c.name as category_name, b.name as brand_name
    FROM models m
    JOIN categories c ON m.category_id = c.id
    JOIN brands b ON m.brand_id = b.id
    ORDER BY m.id
");
$models = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Modelli</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Gestione Modelli</h1>
        <p><a href="index.php">Torna alla Dashboard</a></p>

        <?php if ($success_message): ?><div class="message success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="message error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

        <h2>Aggiungi Nuovo Modello</h2>
        <form action="manage_models.php" method="post">
            <div class="form-group">
                <label for="model_name">Nome Modello (es. iPhone 15 Pro):</label>
                <input type="text" id="model_name" name="model_name" required>
            </div>
            <button type="submit" name="add_model">Aggiungi</button>
        </form>

        <h2>Modelli Esistenti</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Modello</th>
                    <th>Marca</th>
                    <th>Categoria</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($models as $model): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($model['id']); ?></td>
                        <td><?php echo htmlspecialchars($model['name']); ?></td>
                        <td><?php echo htmlspecialchars($model['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($model['category_name']); ?></td>
                        <td class="actions">
                            <a href="edit_model.php?id=<?php echo $model['id']; ?>" class="edit">Modifica</a>
                            <form action="delete_model.php" method="post" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo modello?');">
                                <input type="hidden" name="id" value="<?php echo $model['id']; ?>">
                                <button type="submit" class="delete-button">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
