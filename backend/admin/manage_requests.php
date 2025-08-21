<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();

// Lettura di tutte le richieste
$stmt = $pdo->query("SELECT * FROM trade_in_requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizzazione Richieste</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 1200px;">
        <h1>Visualizzazione Richieste</h1>
        <p><a href="index.php">Torna alla Dashboard</a></p>

        <h2>Richieste Ricevute</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Utente</th>
                    <th>Dispositivo</th>
                    <th>Importo</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $request): ?>
                        <?php $device_details = json_decode($request['device_details_json'], true); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['id']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($request['user_name']); ?><br><small><?php echo htmlspecialchars($request['user_email']); ?></small></td>
                            <td>
                                <?php
                                    if ($device_details) {
                                        echo htmlspecialchars($device_details['model_name'] . ' ' . $device_details['capacity_value'] . ' - ' . $device_details['condition_name']);
                                    }
                                ?>
                            </td>
                            <td>€ <?php echo htmlspecialchars(number_format($request['valuation_amount'], 2, ',', '.')); ?></td>
                            <td><?php echo htmlspecialchars($request['status']); ?></td>
                            <td class="actions">
                                <a href="view_request.php?id=<?php echo $request['id']; ?>" class="edit">Visualizza</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Nessuna richiesta trovata.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
