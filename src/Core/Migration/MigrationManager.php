<?php
namespace Core\Migration;

use Core\Database;

class MigrationManager
{
    /**
     * Instance de base de données
     * @var Database
     */
    private $db;
    
    /**
     * Répertoire des migrations
     * @var string
     */
    private $migrationsPath;
    
    /**
     * Constructeur
     */
    public function __construct($migrationsPath = null)
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = $migrationsPath ?: ROOT_PATH . '/src/Migrations';
        
        // Créer la table des migrations si elle n'existe pas
        $this->createMigrationsTable();
    }
    
    /**
     * Créer la table pour suivre les migrations
     */
    private function createMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
    }
    
    /**
     * Exécuter toutes les migrations en attente
     */
    public function migrate()
    {
        // Récupérer les migrations déjà exécutées
        $executedMigrations = $this->getExecutedMigrations();
        
        // Déterminer le prochain numéro de lot
        $batch = $this->getNextBatchNumber();
        
        // Récupérer toutes les migrations disponibles
        $availableMigrations = $this->getAvailableMigrations();
        
        // Trier les migrations par horodatage
        sort($availableMigrations);
        
        $migrationsRun = [];
        
        // Exécuter les migrations non exécutées
        foreach ($availableMigrations as $migrationFile) {
            $migrationName = pathinfo($migrationFile, PATHINFO_FILENAME);
            
            if (!in_array($migrationName, $executedMigrations)) {
                $this->runMigration($migrationName, 'up', $batch);
                $migrationsRun[] = $migrationName;
            }
        }
        
        return $migrationsRun;
    }
    
    /**
     * Annuler la dernière série de migrations
     */
    public function rollback($steps = 1)
    {
        // Récupérer les migrations par lot, du plus récent au plus ancien
        $migrations = $this->getMigrationsByBatch();
        
        $migrationsRolledBack = [];
        
        // Annuler les migrations du nombre de lots demandé
        $batches = array_keys($migrations);
        $batchesToRollback = array_slice($batches, 0, $steps);
        
        foreach ($batchesToRollback as $batch) {
            foreach ($migrations[$batch] as $migration) {
                $this->runMigration($migration, 'down');
                $this->deleteMigrationRecord($migration);
                $migrationsRolledBack[] = $migration;
            }
        }
        
        return $migrationsRolledBack;
    }
    
    /**
     * Rafraîchir toutes les migrations (rollback puis migrate)
     */
    public function refresh()
    {
        // Annuler toutes les migrations
        $this->reset();
        
        // Réexécuter toutes les migrations
        return $this->migrate();
    }
    
    /**
     * Annuler toutes les migrations
     */
    public function reset()
    {
        // Récupérer toutes les migrations exécutées, de la plus récente à la plus ancienne
        $migrations = $this->db->query("SELECT migration FROM migrations ORDER BY id DESC");
        
        $migrationsRolledBack = [];
        
        // Annuler chaque migration
        foreach ($migrations as $migration) {
            $this->runMigration($migration->migration, 'down');
            $this->deleteMigrationRecord($migration->migration);
            $migrationsRolledBack[] = $migration->migration;
        }
        
        return $migrationsRolledBack;
    }
    
    /**
     * Exécuter une migration spécifique
     */
    private function runMigration($migrationName, $direction, $batch = null)
    {
        // Construire le nom de la classe de migration
        $className = $this->getMigrationClassName($migrationName);
        
        // Instancier la migration
        $migration = new $className();
        
        // Exécuter la migration dans la direction spécifiée
        $migration->$direction();
        
        // Si c'est une migration 'up', enregistrer dans la table des migrations
        if ($direction === 'up' && $batch !== null) {
            $this->recordMigration($migrationName, $batch);
        }
    }
    
    /**
     * Enregistrer une migration comme exécutée
     */
    private function recordMigration($migration, $batch)
    {
        $this->db->execute(
            "INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)",
            ['migration' => $migration, 'batch' => $batch]
        );
    }
    
    /**
     * Supprimer l'enregistrement d'une migration
     */
    private function deleteMigrationRecord($migration)
    {
        $this->db->execute(
            "DELETE FROM migrations WHERE migration = :migration",
            ['migration' => $migration]
        );
    }
    
    /**
     * Obtenir les migrations déjà exécutées
     */
    private function getExecutedMigrations()
    {
        $migrations = $this->db->query("SELECT migration FROM migrations");
        
        return array_map(function ($migration) {
            return $migration->migration;
        }, $migrations);
    }
    
    /**
     * Obtenir toutes les migrations disponibles
     */
    private function getAvailableMigrations()
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        $migrations = glob($this->migrationsPath . '/*.php');
        
        return array_map(function ($migration) {
            return pathinfo($migration, PATHINFO_FILENAME);
        }, $migrations);
    }
    
    /**
     * Obtenir le prochain numéro de lot
     */
    private function getNextBatchNumber()
    {
        $max = $this->db->query("SELECT MAX(batch) as max_batch FROM migrations", [], true);
        return ($max && $max->max_batch) ? $max->max_batch + 1 : 1;
    }
    
    /**
     * Obtenir les migrations par lot
     */
    private function getMigrationsByBatch()
    {
        $migrations = $this->db->query("SELECT migration, batch FROM migrations ORDER BY id DESC");
        
        $batchedMigrations = [];
        
        foreach ($migrations as $migration) {
            if (!isset($batchedMigrations[$migration->batch])) {
                $batchedMigrations[$migration->batch] = [];
            }
            
            $batchedMigrations[$migration->batch][] = $migration->migration;
        }
        
        return $batchedMigrations;
    }
    
    /**
     * Obtenir le nom de classe complet d'une migration
     */
    private function getMigrationClassName($migrationName)
    {
        $migrationFile = $this->migrationsPath . '/' . $migrationName . '.php';
        
        // Charger le contenu du fichier de migration
        require_once $migrationFile;
        
        // Si le fichier a un préfixe "Migration_" dans la classe
        $className = 'Migration_' . $migrationName;
        if (class_exists('\\Migrations\\' . $className)) {
            return '\\Migrations\\' . $className;
        }
        
        // Sinon, utiliser le nom original
        return '\\Migrations\\' . $migrationName;
    }
    
    /**
     * Créer une nouvelle migration
     */
    public function create($name, $template = null)
    {
        $timestamp = date('YmdHis');
        $className = $timestamp . '_' . ucfirst($name);
        $filePath = $this->migrationsPath . '/' . $className . '.php';
        
        // Créer le répertoire de migrations s'il n'existe pas
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        // Contenu du template par défaut
        $template = $template ?: $this->getDefaultMigrationTemplate($className);
        
        // Écrire le fichier de migration
        file_put_contents($filePath, $template);
        
        return $className;
    }
    
    /**
     * Obtenir le template par défaut pour une migration
     */
    private function getDefaultMigrationTemplate($className)
    {
        return '<?php
namespace Migrations;

use Core\Migration\Migration;

class ' . $className . ' extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS exemple (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
    }

    /**
     * Annuler la migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS exemple";
        $this->db->execute($sql);
    }
}';
    }
    
    /**
     * Obtenir le statut des migrations
     *
     * @return array Tableau contenant les migrations exécutées et en attente
     */
    public function status()
    {
        // Récupérer les migrations déjà exécutées
        $executedMigrations = $this->getExecutedMigrations();
        
        // Récupérer toutes les migrations disponibles
        $availableMigrations = $this->getAvailableMigrations();
        
        // Déterminer les migrations en attente
        $pendingMigrations = array_diff($availableMigrations, $executedMigrations);
        
        // Trier les migrations
        sort($pendingMigrations);
        sort($executedMigrations);
        
        return [
            'executed' => $executedMigrations,
            'pending' => $pendingMigrations
        ];
    }
}