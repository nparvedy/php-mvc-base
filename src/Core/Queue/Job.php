<?php
namespace Core\Queue;

/**
 * Interface pour toutes les tâches exécutables
 */
interface Job
{
    /**
     * Exécuter la tâche
     * 
     * @param mixed $data Données associées à la tâche
     * @return void
     */
    public function handle($data);
}