<?php
/**
 * Script pour exécuter les migrations
 */

// Définir le chemin racine de l'application
define('ROOT_PATH', __DIR__);

// Charger l'autoloader
require_once ROOT_PATH . '/src/Core/Autoloader.php';

// Enregistrer l'autoloader
use Core\Autoloader;
Autoloader::register();

// Charger la configuration
$config = require ROOT_PATH . '/config/config.php';

// Créer une instance de l'application
$app = new Core\Application($config);

// Récupérer le gestionnaire de migrations
$migrationManager = $app->getContainer()->make('migration');

// Afficher un message de début
echo "Début des migrations...\n";

// Exécuter les migrations
try {
    $migrations = $migrationManager->migrate();
    
    if (count($migrations) > 0) {
        echo "Migrations exécutées avec succès :\n";
        foreach ($migrations as $migration) {
            echo "- {$migration}\n";
        }
    } else {
        echo "Aucune nouvelle migration à exécuter.\n";
    }
} catch (Exception $e) {
    echo "Erreur lors des migrations : " . $e->getMessage() . "\n";
    exit(1);
}

echo "Migrations terminées.\n";