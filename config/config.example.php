<?php
// Kopiere diese Datei zu config.php und trage deine cyon-Daten ein.
// config.php ist in .gitignore und wird NICHT ins Repo committet.

define('DB_HOST', 'localhost');
define('DB_NAME', 'dein_datenbankname');   // z.B. sailing_subv
define('DB_USER', 'dein_datenbankuser');
define('DB_PASS', 'dein_passwort');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Subventionssimulator');
define('APP_BASE_URL', 'https://subventionssimulator.zurich-sailing.ch');
define('APP_ENV', 'production'); // 'development' | 'production'
