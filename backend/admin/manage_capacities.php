<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$success_message = '';

// Controlla se ci sono messaggi passati tramite sessione (dopo un redirect)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Gestione dell'aggiunta di una nuova capacità
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_capacity'])) {
    $new_capacity_value = trim($_POST['capacity_value']);
    if (!empty($new_capacity_value)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO capacities (value) VALUES (?)");
            $stmt->execute([$new_capacity_value]);
            $success_message = "Capacità '{$new_capacity_value}' aggiunta con successo.";
        } catch (PDOException $e) {
            // Codice di errore per duplicato
            if ($e->getCode() == 23000) {
                $error_message = "Errore: La capacità '{$new_capacity_value}' esiste già.";
            } else {
                $error_message = "Errore nel database: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Il campo non può essere vuoto.";
    }
}

// Lettura di tutte le capacità dal database
$capacities = $pdo->query("SELECT * FROM capacities ORDER BY value")->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Capacità</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Gestione Capacità</h1>
        <p><a href="index.php">Torna alla Dashboard</a></p>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h2>Aggiungi Nuova Capacità</h2>
        <form action="manage_capacities.php" method="post">
            <div class="form-group">
                <label for="capacity_value">Valore (es. 128 GB):</label>
                <input type="text" id="capacity_value" name="capacity_value" required>
            </div>
            <button type="submit" name="add_capacity">Aggiungi</button>
        </form>

        <h2>Capacità Esistenti</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Valore</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($capacities) > 0): ?>
                    <?php foreach ($capacities as $capacity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($capacity['id']); ?></td>
                            <td><?php echo htmlspecialchars($capacity['value']); ?></td>
                            <td class="actions">
                                <a href="edit_capacity.php?id=<?php echo $capacity['id']; ?>" class="edit">Modifica</a>
                                <form action="delete_capacity.php" method="post" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questa capacità?');">
                                    <input type="hidden" name="id" value="<?php echo $capacity['id']; ?>">
                                    <button type="submit" class="delete-button">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">Nessuna capacità trovata.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
