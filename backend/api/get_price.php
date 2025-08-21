<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../core/database.php';

$response = [
    'success' => false,
    'price' => null,
    'message' => ''
];

$model_id = filter_input(INPUT_GET, 'model_id', FILTER_VALIDATE_INT);
$capacity_id = filter_input(INPUT_GET, 'capacity_id', FILTER_VALIDATE_INT);
$condition_id = filter_input(INPUT_GET, 'condition_id', FILTER_VALIDATE_INT);

if (!$model_id || !$capacity_id || !$condition_id) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Parametri mancanti o non validi.';
    echo json_encode($response);
    exit();
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("
        SELECT price FROM prices
        WHERE model_id = :model_id
        AND capacity_id = :capacity_id
        AND condition_id = :condition_id
        LIMIT 1
    ");

    $stmt->execute([
        'model_id' => $model_id,
        'capacity_id' => $capacity_id,
        'condition_id' => $condition_id
    ]);

    $result = $stmt->fetch();

    if ($result) {
        $response['success'] = true;
        $response['price'] = (float)$result['price'];
    } else {
        $response['success'] = true; // La richiesta ha successo, ma non c'è un prezzo
        $response['price'] = null;
        $response['message'] = 'Nessun prezzo definito per questa combinazione.';
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Errore del server durante il recupero del prezzo.';
    error_log('API Error in get_price.php: ' . $e->getMessage());
}

echo json_encode($response);
?>
