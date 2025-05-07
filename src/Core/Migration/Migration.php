<?php
namespace Core\Migration;

use Core\Database;

abstract class Migration
{
    /**
     * Instance de base de données
     * @var Database
     */
    protected $db;
    
    /**
     * Nom de la migration
     * @var string
     */
    protected $name;
    
    /**
     * Horodatage de la migration
     * @var string
     */
    protected $timestamp;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $className = get_class($this);
        $parts = explode('\\', $className);
        $this->name = end($parts);
        
        // Extraire l'horodatage du nom de la migration (si format YYYYMMDDHHMMSS_NomMigration)
        if (preg_match('/^(\d{14})_/', $this->name, $matches)) {
            $this->timestamp = $matches[1];
        } else {
            $this->timestamp = date('YmdHis');
        }
    }

    /**
     * Méthode pour migrer vers le haut (créer)
     */
    abstract public function up();

    /**
     * Méthode pour migrer vers le bas (annuler)
     */
    abstract public function down();

    /**
     * Récupérer le nom de la migration
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Récupérer l'horodatage de la migration
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}