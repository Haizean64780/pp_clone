<?php
/**
 * Database Configuration
 * Update these values to match your phpMyAdmin/MySQL settings
 */

 //LOCAL
/*
define('DB_HOST', 'localhost');
define('DB_NAME', 'peertutoring');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
*/

 //FILEZILLA
 
define('DB_HOST', 'localhost');
define('DB_NAME', 'tai_damattit_geerebaert_v2');
define('DB_USER', 'tai_damattit_geerebaert_v2');
define('DB_PASS', '1012EEAKBI_v2');
define('DB_CHARSET', 'utf8mb4');


// Site configuration
define('SITE_NAME', 'PeerTutoringMatchmaker');
define('SITE_URL', 'https://devweb.estia.fr/tai/tai_damattit_geerebaert_v2/project/');
//define('SITE_URL', 'http://localhost/peertutoring');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('America/New_York');

/**
 * Database connection using PDO
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Start session if not already started
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Initialize session on include
initSession();
