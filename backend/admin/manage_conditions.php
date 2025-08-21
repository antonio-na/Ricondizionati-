<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$success_message = '';

// Controlla i messaggi di sessione
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Gestione dell'aggiunta di una nuova condizione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_condition'])) {
    $name = trim($_POST['condition_name']);
    $description = trim($_POST['condition_description']);

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO conditions (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $success_message = "Stato '{$name}' aggiunto con successo.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Errore: Lo stato '{$name}' esiste già.";
            } else {
                $error_message = "Errore nel database: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Il nome dello stato non può essere vuoto.";
    }
}

// Lettura di tutte le condizioni
$conditions = $pdo->query("SELECT * FROM conditions ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Stati di Conservazione</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Gestione Stati di Conservazione</h1>
        <p><a href="index.php">Torna alla Dashboard</a></p>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h2>Aggiungi Nuovo Stato</h2>
        <form action="manage_conditions.php" method="post">
            <div class="form-group">
                <label for="condition_name">Nome (es. Come Nuovo):</label>
                <input type="text" id="condition_name" name="condition_name" required>
            </div>
            <div class="form-group">
                <label for="condition_description">Descrizione (opzionale):</label>
                <textarea id="condition_description" name="condition_description" rows="3"></textarea>
            </div>
            <button type="submit" name="add_condition">Aggiungi</button>
        </form>

        <h2>Stati Esistenti</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conditions as $condition): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($condition['id']); ?></td>
                        <td><?php echo htmlspecialchars($condition['name']); ?></td>
                        <td><?php echo htmlspecialchars($condition['description']); ?></td>
                        <td class="actions">
                            <a href="edit_condition.php?id=<?php echo $condition['id']; ?>" class="edit">Modifica</a>
                            <form action="delete_condition.php" method="post" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo stato?');">
                                <input type="hidden" name="id" value="<?php echo $condition['id']; ?>">
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
