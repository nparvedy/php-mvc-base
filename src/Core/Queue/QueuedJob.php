<?php
namespace Core\Queue;

use Core\Database;
use Core\Container;

/**
 * Représente une tâche extraite de la file d'attente
 */
class QueuedJob
{
    /**
     * Instance de base de données
     * @var Database
     */
    protected $db;
    
    /**
     * Nom de la table des tâches
     * @var string
     */
    protected $table;
    
    /**
     * ID de la tâche
     * @var int
     */
    protected $id;
    
    /**
     * Nom de la file d'attente
     * @var string
     */
    protected $queue;
    
    /**
     * Nom de classe de la tâche
     * @var string
     */
    protected $job;
    
    /**
     * Données associées à la tâche
     * @var mixed
     */
    protected $data;
    
    /**
     * Constructeur
     * 
     * @param Database $db
     * @param string $table
     * @param int $id
     * @param string $queue
     * @param string $job
     * @param mixed $data
     */
    public function __construct(Database $db, $table, $id, $queue, $job, $data)
    {
        $this->db = $db;
        $this->table = $table;
        $this->id = $id;
        $this->queue = $queue;
        $this->job = $job;
        $this->data = $data;
    }
    
    /**
     * Exécuter la tâche
     * 
     * @return mixed
     */
    public function fire()
    {
        $instance = $this->resolve($this->job);
        
        try {
            $result = $instance->handle($this->data);
            $this->delete();
            return $result;
        } catch (\Exception $e) {
            $this->release(60); // Remettre en file d'attente pour réessayer après 60 secondes
            throw $e;
        }
    }
    
    /**
     * Résoudre l'instance de la tâche à exécuter
     * 
     * @param string $job
     * @return object
     */
    protected function resolve($job)
    {
        // Essayer d'utiliser le conteneur d'injection de dépendances s'il existe
        if (class_exists('Core\\Container') && method_exists('Core\\Container', 'getInstance')) {
            $container = Container::getInstance();
            
            if ($container && method_exists($container, 'make')) {
                try {
                    return $container->make($job);
                } catch (\Exception $e) {
                    // Fallback sur l'instanciation directe
                }
            }
        }
        
        return new $job();
    }
    
    /**
     * Supprimer cette tâche de la file d'attente
     * 
     * @return bool
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        
        return $this->db->execute($sql, ['id' => $this->id]) > 0;
    }
    
    /**
     * Remettre cette tâche en file d'attente pour une exécution ultérieure
     * 
     * @param int $delay Délai en secondes
     * @return bool
     */
    public function release($delay = 0)
    {
        $availableAt = time() + $delay;
        
        $sql = "UPDATE {$this->table} 
                SET reserved_at = NULL, available_at = :available_at 
                WHERE id = :id";
                
        return $this->db->execute($sql, [
            'available_at' => $availableAt,
            'id' => $this->id
        ]) > 0;
    }
    
    /**
     * Obtenir l'ID de la tâche
     * 
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Obtenir le nom de la tâche
     * 
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }
    
    /**
     * Obtenir les données de la tâche
     * 
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Obtenir le nom de la file d'attente
     * 
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }
}