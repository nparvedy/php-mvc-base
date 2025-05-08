<?php
namespace Core\Log;

use Core\Database;

/**
 * Logger qui écrit les messages dans une table de base de données
 */
class DatabaseLogger extends AbstractLogger
{
    /**
     * Instance de Database
     * @var Database
     */
    protected $db;
    
    /**
     * Nom de la table pour les logs
     * @var string
     */
    protected $table = 'logs';
    
    /**
     * Constructeur
     * 
     * @param Database|null $db Instance de Database
     * @param string $table Nom de la table pour les logs
     * @param string $minimumLevel Niveau minimum à logger
     */
    public function __construct(Database $db = null, $table = null, $minimumLevel = self::DEBUG)
    {
        parent::__construct($minimumLevel);
        
        $this->db = $db ?: Database::getInstance();
        
        if ($table) {
            $this->table = $table;
        }
        
        $this->ensureTableExists();
    }
    
    /**
     * S'assurer que la table de logs existe
     */
    protected function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            context TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->execute($sql);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function writeLog($level, $message, array $context = [])
    {
        // Convertir le contexte en JSON si présent
        $contextJson = !empty($context) ? json_encode($context) : null;
        
        $sql = "INSERT INTO {$this->table} (level, message, context) VALUES (:level, :message, :context)";
        
        $this->db->execute($sql, [
            'level' => $level,
            'message' => $message,
            'context' => $contextJson
        ]);
    }
    
    /**
     * Purger les anciens logs
     * 
     * @param int $days Nombre de jours à conserver
     * @return int Nombre d'entrées supprimées
     */
    public function purgeOldLogs($days = 30)
    {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        return $this->db->execute($sql, ['days' => $days]);
    }
}