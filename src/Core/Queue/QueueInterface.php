<?php
namespace Core\Queue;

/**
 * Interface pour tous les drivers de file d'attente
 */
interface QueueInterface
{
    /**
     * Ajouter une tâche à la file d'attente
     * 
     * @param string|object $job La tâche à exécuter (nom de classe ou objet)
     * @param mixed $data Données supplémentaires pour la tâche
     * @param string|null $queue Nom de la file d'attente
     * @return mixed ID de la tâche ajoutée
     */
    public function push($job, $data = '', $queue = null);
    
    /**
     * Ajouter une tâche à la file d'attente avec un délai
     * 
     * @param int $delay Délai en secondes
     * @param string|object $job La tâche à exécuter (nom de classe ou objet)
     * @param mixed $data Données supplémentaires pour la tâche
     * @param string|null $queue Nom de la file d'attente
     * @return mixed ID de la tâche ajoutée
     */
    public function later($delay, $job, $data = '', $queue = null);
    
    /**
     * Récupérer et retirer la prochaine tâche de la file d'attente
     * 
     * @param string|null $queue Nom de la file d'attente
     * @return object|null La tâche récupérée ou null si la file est vide
     */
    public function pop($queue = null);
    
    /**
     * Obtenir le nombre de tâches dans la file d'attente
     * 
     * @param string|null $queue Nom de la file d'attente
     * @return int
     */
    public function size($queue = null);
    
    /**
     * Vider une file d'attente
     * 
     * @param string|null $queue Nom de la file d'attente
     * @return bool
     */
    public function clear($queue = null);
}