<?php
// Configuration de l'application
define('SITE_NAME', 'Système de Pointage');
define('TIMEZONE', 'Indian/Antananarivo');
define('DATE_FORMAT', 'd/m/Y H:i:s');
define('SESSION_TIMEOUT', 3600);

date_default_timezone_set(TIMEZONE);

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'pointage_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Démarrage de la session si non démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>