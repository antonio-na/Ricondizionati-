<?php
// Includi i file necessari una sola volta
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';

/**
 * Avvia una sessione in modo sicuro.
 */
function start_secure_session() {
    $session_name = SESSION_NAME;
    $secure = false; // In produzione, impostare a true se si usa HTTPS
    $httponly = true; // Impedisce a JavaScript di accedere all'ID di sessione

    // Forza la sessione a usare solo cookie
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }

    // Ottiene i parametri del cookie
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params(
        $cookieParams["lifetime"],
        $cookieParams["path"],
        $cookieParams["domain"],
        $secure,
        $httponly
    );

    session_name($session_name);
    session_start();
    session_regenerate_id(true); // Rigenera l'ID di sessione per prevenire session fixation
}

/**
 * Verifica le credenziali dell'utente e imposta la sessione.
 *
 * @param string $username
 * @param string $password
 * @return bool True se il login ha successo, altrimenti False.
 */
function login($username, $password) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Verifica la password
        // NOTA: 'password_hash' è il nome della colonna nel DB.
        // La password nel DB deve essere stata creata con password_hash().
        if (password_verify($password, $user['password_hash'])) {
            // Password corretta, imposta le variabili di sessione
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['login_string'] = hash('sha512', $user['password_hash'] . $_SERVER['HTTP_USER_AGENT']);
            return true;
        }
    }
    // Login fallito
    return false;
}

/**
 * Controlla se l'amministratore è loggato.
 *
 * @return bool
 */
function is_logged_in() {
    if (isset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['login_string'])) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            $login_check = hash('sha512', $user['password_hash'] . $_SERVER['HTTP_USER_AGENT']);
            if (hash_equals($login_check, $_SESSION['login_string'])) {
                return true; // Utente loggato
            }
        }
    }
    return false; // Utente non loggato
}

/**
 * Esegue il logout distruggendo la sessione.
 */
function logout() {
    $_SESSION = array(); // Svuota l'array di sessione
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    session_destroy();
    header('Location: login.php'); // Reindirizza alla pagina di login
    exit();
}

/**
 * Da includere all'inizio delle pagine protette.
 * Se l'utente non è loggato, lo reindirizza alla pagina di login.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}
?>
