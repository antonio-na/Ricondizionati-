<?php
// Includi il file di configurazione una sola volta
require_once __DIR__ . '/../config.php';

class Database {
    private static $pdo = null;

    /**
     * Stabilisce e restituisce una connessione PDO al database.
     * Utilizza il pattern Singleton per garantire che ci sia una sola istanza della connessione.
     *
     * @return PDO|null L'oggetto PDO della connessione o null in caso di errore.
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lancia eccezioni in caso di errore
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Restituisce array associativi
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Disabilita l'emulazione dei prepared statements
            ];

            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // In un'applicazione di produzione, non mostrare l'errore dettagliato all'utente.
                // Invece, logga l'errore in un file di log protetto.
                error_log('Errore di connessione al database: ' . $e->getMessage());
                // Potresti voler terminare lo script o mostrare una pagina di errore generica.
                die('Errore: Impossibile connettersi al database.');
            }
        }

        return self::$pdo;
    }
}

// Esempio di come ottenere la connessione:
// $pdo = Database::getConnection();
?>
