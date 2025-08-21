<?php
// File di Configurazione del Database

// In un'applicazione reale, queste credenziali dovrebbero essere
// memorizzate in modo sicuro e non tracciate da Git.

define('DB_HOST', '31.11.39.168');
define('DB_NAME', 'Sql1810635_2');
define('DB_USER', 'Sql1810635');
define('DB_PASS', 'Nulab2025!');
define('DB_CHARSET', 'utf8mb4');

// Configurazione per la sessione di login
define('SESSION_NAME', 'admin_session');

// Chiave segreta per la sicurezza (es. hashing, crittografia)
// In un'app reale, dovrebbe essere una stringa lunga e casuale.
define('SECRET_KEY', 'la_tua_chiave_segreta_qui');

// Configurazione per l'invio di email tramite SMTP
define('SMTP_HOST', 'smtps.aruba.it');
define('SMTP_USERNAME', 'ricondizionati@nu-lab.it');
define('SMTP_PASSWORD', 'Nulab2025!');
define('SMTP_PORT', 465); // o 465 per SSL
define('SMTP_FROM_EMAIL', 'ricondizionati@nu-lab.it');
define('SMTP_FROM_NAME', 'ValutaTel');
define('ADMIN_EMAIL', 'ricondizionati@nu-lab.it'); // Email dell'amministratore per le notifiche
?>
