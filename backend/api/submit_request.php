<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../core/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
    exit();
}

$response = ['success' => false, 'message' => 'Errore sconosciuto.'];
$input = json_decode(file_get_contents('php://input'), true);

// --- [MODIFICATO] Validazione Dati sulla nuova struttura ---
$user_data = $input['user_data'] ?? [];
$payment_data = $input['payment_data'] ?? [];
$device_data = $input['device_data'] ?? [];

if (empty($user_data['name']) || empty($user_data['email']) || empty($user_data['phone']) || empty($user_data['address']) || empty($user_data['city']) || empty($user_data['province'])) {
    http_response_code(400);
    $response['message'] = 'Dati utente mancanti.';
    echo json_encode($response);
    exit();
}
if (!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['message'] = 'Formato email non valido.';
    echo json_encode($response);
    exit();
}
if (empty($device_data['model_id']) || empty($device_data['capacity_id']) || empty($device_data['condition_id'])) {
    http_response_code(400);
    $response['message'] = 'Dati dispositivo mancanti.';
    echo json_encode($response);
    exit();
}
// Potresti aggiungere qui una validazione più dettagliata per i dati di pagamento

try {
    $pdo = Database::getConnection();

    // Ricalcola il prezzo sul server per sicurezza
    $stmt_price = $pdo->prepare("SELECT price FROM prices WHERE model_id = ? AND capacity_id = ? AND condition_id = ?");
    $stmt_price->execute([$device_data['model_id'], $device_data['capacity_id'], $device_data['condition_id']]);
    $price_result = $stmt_price->fetch();

    if (!$price_result) {
        http_response_code(400);
        $response['message'] = 'Prezzo non trovato per il dispositivo selezionato.';
        echo json_encode($response);
        exit();
    }
    $valuation_amount = $price_result['price'];

    // Recupera i dettagli del dispositivo per salvarli
    $stmt_details = $pdo->prepare("
        SELECT m.name as model_name, cap.value as capacity_value, con.name as condition_name
        FROM models m, capacities cap, conditions con
        WHERE m.id = ? AND cap.id = ? AND con.id = ?
    ");
    $stmt_details->execute([$device_data['model_id'], $device_data['capacity_id'], $device_data['condition_id']]);
    $device_details_json = json_encode($stmt_details->fetch(PDO::FETCH_ASSOC));

    // Concatena l'indirizzo completo
    $full_address = $user_data['address'] . ', ' . $user_data['city'] . ' (' . $user_data['province'] . ')';

    // Salva i dettagli di pagamento come JSON
    $payment_details_json = json_encode($payment_data);

    // --- [MODIFICATO] Inserimento nel database con i nuovi campi ---
    $sql = "INSERT INTO trade_in_requests 
                (user_name, user_email, user_phone, user_address, payment_details_json, device_details_json, valuation_amount)
            VALUES 
                (:user_name, :user_email, :user_phone, :user_address, :payment_details_json, :device_details_json, :valuation_amount)";

    $stmt_insert = $pdo->prepare($sql);
    $stmt_insert->execute([
        ':user_name' => $user_data['name'],
        ':user_email' => $user_data['email'],
        ':user_phone' => $user_data['phone'],
        ':user_address' => $full_address,
        ':payment_details_json' => $payment_details_json,
        ':device_details_json' => $device_details_json,
        ':valuation_amount' => $valuation_amount
    ]);

    $request_id = $pdo->lastInsertId();

    // Le email sono ancora disabilitate per debug
    /*
    require_once __DIR__ . '/../core/email_service.php';
    send_user_confirmation_email($request_id);
    send_admin_notification_email($request_id);
    */

    $response['success'] = true;
    $response['message'] = 'Richiesta inviata con successo!';
    $response['request_id'] = $request_id;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Errore del server durante il salvataggio della richiesta.';
    error_log('API Error in submit_request.php: ' . $e->getMessage());
}

echo json_encode($response);
?>