<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestione della richiesta pre-flight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../core/database.php';

// Assicura che la richiesta sia di tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
    exit();
}

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

// --- Validazione Dati ---
if (empty($input['user_name']) || empty($input['user_email']) || empty($input['user_address']) || empty($input['payment_method'])) {
    http_response_code(400);
    $response['message'] = 'Dati utente mancanti.';
    echo json_encode($response);
    exit();
}
if (!filter_var($input['user_email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['message'] = 'Formato email non valido.';
    echo json_encode($response);
    exit();
}
if (empty($input['device']['model_id']) || empty($input['device']['capacity_id']) || empty($input['device']['condition_id'])) {
    http_response_code(400);
    $response['message'] = 'Dati dispositivo mancanti.';
    echo json_encode($response);
    exit();
}

try {
    $pdo = Database::getConnection();

    // --- Sicurezza: Ricalcola il prezzo sul server ---
    $stmt_price = $pdo->prepare("SELECT price FROM prices WHERE model_id = ? AND capacity_id = ? AND condition_id = ?");
    $stmt_price->execute([$input['device']['model_id'], $input['device']['capacity_id'], $input['device']['condition_id']]);
    $price_result = $stmt_price->fetch();

    if (!$price_result) {
        http_response_code(400);
        $response['message'] = 'Prezzo non trovato per il dispositivo selezionato. Impossibile procedere.';
        echo json_encode($response);
        exit();
    }
    $valuation_amount = $price_result['price'];

    // Prepara i dettagli del dispositivo per il salvataggio in JSON
    $stmt_details = $pdo->prepare("
        SELECT m.name as model_name, cap.value as capacity_value, con.name as condition_name
        FROM models m, capacities cap, conditions con
        WHERE m.id = ? AND cap.id = ? AND con.id = ?
    ");
    $stmt_details->execute([$input['device']['model_id'], $input['device']['capacity_id'], $input['device']['condition_id']]);
    $device_details_data = $stmt_details->fetch(PDO::FETCH_ASSOC);
    $device_details_json = json_encode($device_details_data);

    // --- Inserimento nel database ---
    $sql = "INSERT INTO trade_in_requests (user_name, user_email, user_address, payment_method, device_details_json, valuation_amount)
            VALUES (:user_name, :user_email, :user_address, :payment_method, :device_details_json, :valuation_amount)";

    $stmt_insert = $pdo->prepare($sql);
    $stmt_insert->execute([
        ':user_name' => $input['user_name'],
        ':user_email' => $input['user_email'],
        ':user_address' => $input['user_address'],
        ':payment_method' => $input['payment_method'],
        ':device_details_json' => $device_details_json,
        ':valuation_amount' => $valuation_amount
    ]);

    $request_id = $pdo->lastInsertId();

    // --- Invio delle email di notifica ---
    require_once __DIR__ . '/../core/email_service.php';
    $user_email_sent = send_user_confirmation_email($request_id);
    $admin_email_sent = send_admin_notification_email($request_id);

    if (!$user_email_sent || !$admin_email_sent) {
        // Non blocchiamo la risposta di successo, ma logghiamo l'errore
        error_log("Fallimento nell'invio di una o più email per la richiesta ID: $request_id");
    }

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
