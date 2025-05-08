<?php
namespace Migrations;

use Core\Migration\Migration;

class Migration_20250507000000_CreateUsersTable extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up()
    {
        // Première étape : Créer la table sans l'index UNIQUE
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(191) NOT NULL,
            password VARCHAR(255) NOT NULL,
            roles VARCHAR(255) NULL,
            permissions VARCHAR(255) NULL,
            remember_token VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
        
        // Deuxième étape : Ajouter l'index UNIQUE sur email avec une longueur limitée
        $this->db->execute("ALTER TABLE users ADD UNIQUE INDEX idx_users_email (email)");
    }

    /**
     * Annuler la migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS users";
        $this->db->execute($sql);
    }
}