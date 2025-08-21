<?php
// File di Configurazione del Database

// In un'applicazione reale, queste credenziali dovrebbero essere
// memorizzate in modo sicuro e non tracciate da Git.

define('DB_HOST', 'localhost');
define('DB_NAME', 'valutazione_db');
define('DB_USER', 'root');
define('DB_PASS', 'password_segnaposto');
define('DB_CHARSET', 'utf8mb4');

// Configurazione per la sessione di login
define('SESSION_NAME', 'admin_session');

// Chiave segreta per la sicurezza (es. hashing, crittografia)
// In un'app reale, dovrebbe essere una stringa lunga e casuale.
define('SECRET_KEY', 'la_tua_chiave_segreta_qui');

// Configurazione per l'invio di email tramite SMTP
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USERNAME', 'user@example.com');
define('SMTP_PASSWORD', 'smtp_password');
define('SMTP_PORT', 587); // o 465 per SSL
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', 'ValutaTel');
define('ADMIN_EMAIL', 'admin@example.com'); // Email dell'amministratore per le notifiche
?>
