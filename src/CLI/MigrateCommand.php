<?php
namespace CLI;

use Core\Migration\MigrationManager;

/**
 * Commande pour gérer les migrations de base de données
 */
class MigrateCommand
{
    /**
     * Gestionnaire de migrations
     * @var MigrationManager
     */
    protected $migrationManager;
    
    /**
     * Arguments de la commande
     * @var array
     */
    protected $args = [];
    
    /**
     * Constructeur
     *
     * @param MigrationManager $migrationManager
     */
    public function __construct(MigrationManager $migrationManager)
    {
        $this->migrationManager = $migrationManager;
    }
    
    /**
     * Définir les arguments de la commande
     *
     * @param array $args
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
        return $this;
    }
    
    /**
     * Exécuter la commande
     */
    public function execute()
    {
        // Déterminer quelle action exécuter
        $action = isset($this->args[0]) ? strtolower($this->args[0]) : 'migrate';
        
        switch ($action) {
            case 'migrate':
                $this->runMigrations();
                break;
            case 'rollback':
                $this->rollbackMigrations();
                break;
            case 'reset':
                $this->resetMigrations();
                break;
            case 'refresh':
                $this->refreshMigrations();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->showHelp();
                break;
        }
    }
    
    /**
     * Exécuter les migrations
     */
    protected function runMigrations()
    {
        echo "Exécution des migrations...\n";
        
        try {
            $migrations = $this->migrationManager->migrate();
            
            if (count($migrations) > 0) {
                echo "Migrations exécutées avec succès :\n";
                foreach ($migrations as $migration) {
                    echo "- {$migration}\n";
                }
            } else {
                echo "Aucune nouvelle migration à exécuter.\n";
            }
        } catch (\Exception $e) {
            echo "Erreur lors de l'exécution des migrations : " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Annuler la dernière migration
     */
    protected function rollbackMigrations()
    {
        echo "Annulation de la dernière migration...\n";
        
        try {
            $migrations = $this->migrationManager->rollback();
            
            if (count($migrations) > 0) {
                echo "Migrations annulées avec succès :\n";
                foreach ($migrations as $migration) {
                    echo "- {$migration}\n";
                }
            } else {
                echo "Aucune migration à annuler.\n";
            }
        } catch (\Exception $e) {
            echo "Erreur lors de l'annulation des migrations : " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Réinitialiser toutes les migrations
     */
    protected function resetMigrations()
    {
        echo "Réinitialisation de toutes les migrations...\n";
        
        try {
            $migrations = $this->migrationManager->reset();
            
            if (count($migrations) > 0) {
                echo "Toutes les migrations ont été réinitialisées avec succès.\n";
            } else {
                echo "Aucune migration à réinitialiser.\n";
            }
        } catch (\Exception $e) {
            echo "Erreur lors de la réinitialisation des migrations : " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Réinitialiser et réexécuter toutes les migrations
     */
    protected function refreshMigrations()
    {
        echo "Rafraîchissement de toutes les migrations...\n";
        
        try {
            // Réinitialiser d'abord
            $this->migrationManager->reset();
            
            // Puis réexécuter les migrations
            $migrations = $this->migrationManager->migrate();
            
            if (count($migrations) > 0) {
                echo "Migrations rafraîchies avec succès :\n";
                foreach ($migrations as $migration) {
                    echo "- {$migration}\n";
                }
            } else {
                echo "Aucune migration à exécuter.\n";
            }
        } catch (\Exception $e) {
            echo "Erreur lors du rafraîchissement des migrations : " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Afficher le statut des migrations
     */
    protected function showStatus()
    {
        echo "Statut des migrations :\n";
        
        try {
            $status = $this->migrationManager->status();
            
            if (empty($status['pending']) && empty($status['executed'])) {
                echo "Aucune migration trouvée.\n";
            } else {
                if (!empty($status['executed'])) {
                    echo "\nMigrations exécutées :\n";
                    foreach ($status['executed'] as $migration) {
                        echo "- {$migration}\n";
                    }
                }
                
                if (!empty($status['pending'])) {
                    echo "\nMigrations en attente :\n";
                    foreach ($status['pending'] as $migration) {
                        echo "- {$migration}\n";
                    }
                }
            }
        } catch (\Exception $e) {
            echo "Erreur lors de la récupération du statut des migrations : " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Afficher l'aide
     */
    protected function showHelp()
    {
        echo "Usage: php mvc migrate [command]\n\n";
        echo "Commandes disponibles :\n";
        echo "  migrate   : Exécuter les nouvelles migrations\n";
        echo "  rollback  : Annuler la dernière migration\n";
        echo "  reset     : Annuler toutes les migrations\n";
        echo "  refresh   : Réinitialiser et réexécuter toutes les migrations\n";
        echo "  status    : Afficher le statut des migrations\n";
        echo "  help      : Afficher cette aide\n";
    }
}