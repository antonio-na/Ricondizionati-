<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$error_message = '';
$success_message = '';

$models = $pdo->query("SELECT id, name FROM models ORDER BY name")->fetchAll();
$selected_model_id = $_GET['model_id'] ?? null;

// Gestione salvataggio prezzi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_prices'])) {
    $prices = $_POST['price'];
    $model_id = $_POST['model_id'];

    // [CORREZIONE 1] Usa un nome diverso per il segnaposto nella clausola UPDATE.
    $sql = "INSERT INTO prices (model_id, capacity_id, condition_id, price) VALUES (:model_id, :capacity_id, :condition_id, :price)
            ON DUPLICATE KEY UPDATE price = :update_price";
    $stmt = $pdo->prepare($sql);

    $pdo->beginTransaction();
    try {
        foreach ($prices as $capacity_id => $condition_prices) {
            foreach ($condition_prices as $condition_id => $price) {
                if (is_numeric($price) && $price >= 0) {
                    
                    // [CORREZIONE 2] Aggiungi il nuovo parametro all'array di execute.
                    $stmt->execute([
                        'model_id' => $model_id,
                        'capacity_id' => $capacity_id,
                        'condition_id' => $condition_id,
                        'price' => $price,
                        'update_price' => $price
                    ]);
                }
            }
        }
        $pdo->commit();
        $success_message = "Prezzi aggiornati con successo per il modello selezionato.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Errore durante l'aggiornamento dei prezzi: " . $e->getMessage();
    }
    $selected_model_id = $model_id;
}


// Dati per la visualizzazione
$capacities = [];
$conditions = [];
$price_map = [];

if ($selected_model_id) {
    $capacities = $pdo->query("SELECT id, value FROM capacities ORDER BY id")->fetchAll();
    $conditions = $pdo->query("SELECT id, name FROM conditions ORDER BY id")->fetchAll();

    $stmt = $pdo->prepare("SELECT capacity_id, condition_id, price FROM prices WHERE model_id = ?");
    $stmt->execute([$selected_model_id]);
    $prices_data = $stmt->fetchAll();

    foreach ($prices_data as $p) {
        $price_map[$p['capacity_id']][$p['condition_id']] = $p['price'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Prezzi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Gestione Prezzi</h1>
    <p><a href="index.php">Torna alla Dashboard</a></p>

    <?php if ($success_message): ?><div class="message success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="message error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

    <form action="manage_prices.php" method="get" class="form-group">
        <label for="model_id">Seleziona un modello per gestire i prezzi:</label>
        <select name="model_id" id="model_id" onchange="this.form.submit()">
            <option value="">-- Seleziona Modello --</option>
            <?php foreach ($models as $model): ?>
                <option value="<?php echo $model['id']; ?>" <?php echo ($selected_model_id == $model['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($model['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_model_id): ?>
        <hr style="margin: 30px 0;">
        <h2>Prezzi per: <?php echo htmlspecialchars(array_values(array_filter($models, fn($m) => $m['id'] == $selected_model_id))[0]['name'] ?? ''); ?></h2>
        <form action="manage_prices.php" method="post">
            <input type="hidden" name="model_id" value="<?php echo htmlspecialchars($selected_model_id); ?>">
            <table>
                <thead>
                    <tr>
                        <th>Capacità</th>
                        <?php foreach ($conditions as $condition): ?>
                            <th><?php echo htmlspecialchars($condition['name']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($capacities as $capacity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($capacity['value']); ?></td>
                            <?php foreach ($conditions as $condition): ?>
                                <td>
                                    <input type="number" step="0.01" min="0" name="price[<?php echo $capacity['id']; ?>][<?php echo $condition['id']; ?>]"
                                           value="<?php echo htmlspecialchars($price_map[$capacity['id']][$condition['id']] ?? '0.00'); ?>"
                                           style="width: 100px;">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <button type="submit" name="save_prices">Salva Prezzi</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>