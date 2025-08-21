-- Progettazione Database per Piattaforma di Valutazione
-- Data: 20/08/2025
-- Autore: Jules

-- Questo script crea la struttura completa del database.
-- Le tabelle sono progettate per essere flessibili e scalabili.

-- Tabella per gli amministratori del pannello di controllo
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella per le categorie di prodotti (es. Smartphone, Tablet)
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
);

-- Tabella per le marche (es. Apple, Samsung)
CREATE TABLE `brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
);

-- Tabella per i modelli di dispositivi (es. iPhone 14, iPad Pro)
CREATE TABLE `models` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category_id` INT NOT NULL,
    `brand_id` INT NOT NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE CASCADE
);

-- Tabella per le capacità di memoria (es. 128GB, 256GB)
-- Gestibile dall'admin per poterle aggiungere/rimuovere facilmente
CREATE TABLE `capacities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `value` VARCHAR(50) NOT NULL UNIQUE -- es. "128 GB", "256 GB"
);

-- Tabella per gli stati di conservazione (es. Come Nuovo, Ottimo)
-- Gestibile dall'admin
CREATE TABLE `conditions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT
);

-- Tabella per i prezzi
-- Associa un prezzo a una combinazione specifica di modello, capacità e stato
CREATE TABLE `prices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `model_id` INT NOT NULL,
    `capacity_id` INT NOT NULL,
    `condition_id` INT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`model_id`) REFERENCES `models`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`capacity_id`) REFERENCES `capacities`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`condition_id`) REFERENCES `conditions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_price_combination` (`model_id`, `capacity_id`, `condition_id`)
);

-- Tabella per le richieste di ritiro/valutazione inviate dagli utenti
CREATE TABLE `trade_in_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(255) NOT NULL,
    `user_email` VARCHAR(255) NOT NULL,
    `user_address` TEXT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL, -- es. "PayPal", "Bonifico", "Coupon"
    `device_details_json` JSON NOT NULL, -- Un JSON con i dettagli del dispositivo (modello, stato, etc.)
    `valuation_amount` DECIMAL(10, 2) NOT NULL,
    `status` VARCHAR(50) DEFAULT 'Pending', -- es. "Pending", "Processing", "Completed"
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserimento di un amministratore di default per il primo accesso
-- La password è 'admin_password' (dovrà essere hashata in produzione)
INSERT INTO `admins` (`username`, `password_hash`) VALUES ('admin', 'hash_della_password_qui');

-- Esempio di dati iniziali (opzionale, per test)
INSERT INTO `categories` (`name`) VALUES ('Smartphone');
INSERT INTO `brands` (`name`) VALUES ('Apple');
INSERT INTO `models` (`name`, `category_id`, `brand_id`) VALUES ('iPhone 14', 1, 1);
INSERT INTO `capacities` (`value`) VALUES ('128 GB'), ('256 GB');
INSERT INTO `conditions` (`name`, `description`) VALUES ('Come Nuovo', 'Nessun segno di usura, perfettamente funzionante.'), ('Ottimo', 'Leggeri segni di usura, perfettamente funzionante.');
