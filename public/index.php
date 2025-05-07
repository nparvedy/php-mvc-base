<?php
// Définir le chemin racine de l'application
define('ROOT_PATH', dirname(__DIR__));

// Charger l'autoloader
require_once ROOT_PATH . '/src/Core/Autoloader.php';

// Initialiser l'autoloader
\Core\Autoloader::register();

// Charger la configuration
$config = require_once ROOT_PATH . '/config/config.php';

// Initialiser l'application
$app = new \Core\Application($config);

// Démarrer l'application
$app->run();