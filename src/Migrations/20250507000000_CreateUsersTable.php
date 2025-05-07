<?php
namespace Migrations;

use Core\Migration\Migration;

class 20250507000000_CreateUsersTable extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            roles VARCHAR(255) NULL,
            permissions VARCHAR(255) NULL,
            remember_token VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
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