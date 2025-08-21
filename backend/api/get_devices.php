<?php
// Imposta l'header per indicare che la risposta è in formato JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permette l'accesso da qualsiasi origine (da restringere in produzione)

require_once __DIR__ . '/../core/database.php';

$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    $pdo = Database::getConnection();

    // Recupera tutti i dati necessari con singole query
    $models = $pdo->query("SELECT id, name FROM models ORDER BY name")->fetchAll();
    $capacities = $pdo->query("SELECT id, value FROM capacities ORDER BY id")->fetchAll();
    $conditions = $pdo->query("SELECT id, name, description FROM conditions ORDER BY id")->fetchAll();

    // Combina i dati in un unico oggetto
    $data = [
        'models' => $models,
        'capacities' => $capacities,
        'conditions' => $conditions
    ];

    $response['success'] = true;
    $response['data'] = $data;

} catch (PDOException $e) {
    // In caso di errore del database
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Errore del server durante il recupero dei dati.';
    error_log('API Error in get_devices.php: ' . $e->getMessage());
}

// Stampa la risposta JSON
echo json_encode($response);
?>
