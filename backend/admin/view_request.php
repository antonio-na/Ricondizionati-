<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/database.php';

start_secure_session();
require_login();

$pdo = Database::getConnection();
$request_id = $_GET['id'] ?? null;

if (!$request_id || !filter_var($request_id, FILTER_VALIDATE_INT)) {
    header('Location: manage_requests.php');
    exit();
}

// Lista degli stati possibili
$possible_statuses = ['In attesa', 'In lavorazione', 'Completata', 'Annullata'];
$success_message = '';
$error_message = '';

// Gestione aggiornamento stato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    if (in_array($new_status, $possible_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE trade_in_requests SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $request_id]);
            $success_message = "Stato della richiesta aggiornato con successo.";
        } catch (PDOException $e) {
            $error_message = "Errore durante l'aggiornamento: " . $e->getMessage();
        }
    } else {
        $error_message = "Stato non valido.";
    }
}

// Caricamento dettagli richiesta
$stmt = $pdo->prepare("SELECT * FROM trade_in_requests WHERE id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: manage_requests.php');
    exit();
}

$device_details = json_decode($request['device_details_json'], true);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Richiesta #<?php echo htmlspecialchars($request['id']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .detail-box { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .detail-box h3 { margin-top: 0; color: var(--primary-color); }
    </style>
</head>
<body>
<div class="container">
    <h1>Dettaglio Richiesta #<?php echo htmlspecialchars($request['id']); ?></h1>
    <p><a href="manage_requests.php">Torna a Tutte le Richieste</a></p>

    <?php if ($success_message): ?><div class="message success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="message error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

    <div class="details-grid">
        <div class="detail-box">
            <h3>Dati Utente</h3>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($request['user_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($request['user_email']); ?></p>
            <p><strong>Indirizzo per il ritiro:</strong><br><?php echo nl2br(htmlspecialchars($request['user_address'])); ?></p>
        </div>
        <div class="detail-box">
            <h3>Dati Dispositivo</h3>
            <?php if ($device_details): ?>
                <p><strong>Modello:</strong> <?php echo htmlspecialchars($device_details['model_name']); ?></p>
                <p><strong>Capacità:</strong> <?php echo htmlspecialchars($device_details['capacity_value']); ?></p>
                <p><strong>Stato:</strong> <?php echo htmlspecialchars($device_details['condition_name']); ?></p>
            <?php endif; ?>
        </div>
        <div class="detail-box">
            <h3>Dati Valutazione</h3>
            <p><strong>Importo Valutazione:</strong> € <?php echo htmlspecialchars(number_format($request['valuation_amount'], 2, ',', '.')); ?></p>
            <p><strong>Metodo di Pagamento:</strong> <?php echo htmlspecialchars($request['payment_method']); ?></p>
            <p><strong>Data Richiesta:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at']))); ?></p>
        </div>
        <div class="detail-box">
            <h3>Stato Richiesta</h3>
            <p><strong>Stato Attuale:</strong> <?php echo htmlspecialchars($request['status']); ?></p>
            <form action="view_request.php?id=<?php echo $request_id; ?>" method="post">
                <div class="form-group">
                    <label for="status"><strong>Aggiorna Stato:</strong></label>
                    <select name="status" id="status">
                        <?php foreach ($possible_statuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo ($status == $request['status']) ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="update_status">Aggiorna</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
