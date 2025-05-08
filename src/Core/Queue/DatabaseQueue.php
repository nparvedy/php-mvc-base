<?php
namespace Core\Queue;

use Core\Database;

/**
 * Implémentation de file d'attente utilisant une base de données
 */
class DatabaseQueue implements QueueInterface
{
    /**
     * Instance de la base de données
     * @var Database
     */
    protected $db;
    
    /**
     * Nom de la table pour les tâches
     * @var string
     */
    protected $table = 'jobs';
    
    /**
     * File d'attente par défaut
     * @var string
     */
    protected $default = 'default';
    
    /**
     * Constructeur
     * 
     * @param Database $db Instance de la base de données
     * @param string $table Nom de la table pour les tâches
     */
    public function __construct(Database $db = null, $table = null)
    {
        $this->db = $db ?: Database::getInstance();
        
        if ($table) {
            $this->table = $table;
        }
        
        $this->ensureTableExists();
    }
    
    /**
     * S'assurer que la table des tâches existe
     */
    protected function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL DEFAULT 'default',
            payload TEXT NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            reserved_at INT NULL,
            available_at INT NOT NULL,
            created_at INT NOT NULL
        )";
        
        $this->db->execute($sql);
    }
    
    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($queue ?: $this->default, $this->createPayload($job, $data));
    }
    
    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $availableAt = time() + $delay;
        
        return $this->pushToDatabase($queue ?: $this->default, $this->createPayload($job, $data), $availableAt);
    }
    
    /**
     * Ajouter une tâche à la base de données
     * 
     * @param string $queue File d'attente
     * @param string $payload Données encodées de la tâche
     * @param int|null $availableAt Timestamp de disponibilité
     * @return int ID de la tâche insérée
     */
    protected function pushToDatabase($queue, $payload, $availableAt = null)
    {
        $now = time();
        $availableAt = $availableAt ?: $now;
        
        $sql = "INSERT INTO {$this->table} (queue, payload, attempts, available_at, created_at) VALUES (:queue, :payload, 0, :available_at, :created_at)";
        
        $this->db->execute($sql, [
            'queue' => $queue,
            'payload' => $payload,
            'available_at' => $availableAt,
            'created_at' => $now
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        $queue = $queue ?: $this->default;
        $now = time();
        
        // Chercher une tâche disponible
        $sql = "SELECT * FROM {$this->table} 
                WHERE queue = :queue 
                AND (reserved_at IS NULL OR reserved_at < :expired) 
                AND available_at <= :now
                ORDER BY id ASC
                LIMIT 1";
                
        $job = $this->db->query($sql, [
            'queue' => $queue,
            'expired' => $now - 60, // Tâches réservées depuis plus de 60 secondes sont considérées abandonnées
            'now' => $now
        ], true);
        
        if (!$job) {
            return null;
        }
        
        // Marquer la tâche comme réservée
        $sql = "UPDATE {$this->table} 
                SET reserved_at = :reserved_at, attempts = attempts + 1 
                WHERE id = :id";
                
        $this->db->execute($sql, [
            'reserved_at' => $now,
            'id' => $job->id
        ]);
        
        $payload = json_decode($job->payload, true);
        
        // Créer un objet QueuedJob avec toutes les informations nécessaires
        return new QueuedJob(
            $this->db,
            $this->table,
            $job->id,
            $queue,
            $payload['job'],
            $payload['data']
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function size($queue = null)
    {
        $queue = $queue ?: $this->default;
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE queue = :queue 
                AND (reserved_at IS NULL OR reserved_at < :expired) 
                AND available_at <= :now";
                
        $result = $this->db->query($sql, [
            'queue' => $queue,
            'expired' => time() - 60,
            'now' => time()
        ], true);
        
        return (int) $result->count;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear($queue = null)
    {
        $queue = $queue ?: $this->default;
        
        $sql = "DELETE FROM {$this->table} WHERE queue = :queue";
        
        return $this->db->execute($sql, ['queue' => $queue]) > 0;
    }
    
    /**
     * Créer le payload à stocker
     * 
     * @param string|object $job La tâche
     * @param mixed $data Données supplémentaires
     * @return string JSON encodé
     */
    protected function createPayload($job, $data)
    {
        $payload = [
            'job' => is_object($job) ? get_class($job) : $job,
            'data' => $data
        ];
        
        return json_encode($payload);
    }
}