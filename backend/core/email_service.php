<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';

// Includo la libreria PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

/**
 * Invia un'email configurata.
 *
 * @param string $to L'indirizzo email del destinatario.
 * @param string $subject L'oggetto dell'email.
 * @param string $body Il corpo dell'email.
 * @return bool True se l'invio ha successo, altrimenti False.
 */
function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Impostazioni del server SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Mittente e Destinatario
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Contenuto
        $mail->isHTML(false); // Inviamo come testo semplice
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // In un'app reale, loggare l'errore
        error_log("Errore PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Recupera i dettagli di una richiesta e li formatta per le email.
 */
function get_formatted_request_details($request_id) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM trade_in_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) return null;

    $device_details = json_decode($request['device_details_json'], true);

    return [
        '{{USER_NAME}}' => $request['user_name'],
        '{{USER_EMAIL}}' => $request['user_email'],
        '{{USER_ADDRESS}}' => $request['user_address'],
        '{{REQUEST_ID}}' => $request['id'],
        '{{REQUEST_DATE}}' => date('d/m/Y H:i', strtotime($request['created_at'])),
        '{{DEVICE_MODEL}}' => $device_details['model_name'] ?? 'N/D',
        '{{DEVICE_CAPACITY}}' => $device_details['capacity_value'] ?? 'N/D',
        '{{DEVICE_CONDITION}}' => $device_details['condition_name'] ?? 'N/D',
        '{{VALUATION_AMOUNT}}' => number_format($request['valuation_amount'], 2, ',', '.'),
        '{{PAYMENT_METHOD}}' => $request['payment_method'],
    ];
}

/**
 * Invia l'email di conferma all'utente.
 */
function send_user_confirmation_email($request_id) {
    $details = get_formatted_request_details($request_id);
    if (!$details) return false;

    $template = file_get_contents(__DIR__ . '/../templates/email_user_confirmation.txt');
    $body = str_replace(array_keys($details), array_values($details), $template);

    $subject = "Conferma Richiesta di Valutazione #" . $details['{{REQUEST_ID}}'];

    return send_email($details['{{USER_EMAIL}}'], $subject, $body);
}

/**
 * Invia l'email di notifica all'amministratore.
 */
function send_admin_notification_email($request_id) {
    $details = get_formatted_request_details($request_id);
    if (!$details) return false;

    $template = file_get_contents(__DIR__ . '/../templates/email_admin_notification.txt');
    $body = str_replace(array_keys($details), array_values($details), $template);

    $subject = "Nuova Richiesta di Valutazione Ricevuta #" . $details['{{REQUEST_ID}}'];

    return send_email(ADMIN_EMAIL, $subject, $body);
}
?>
