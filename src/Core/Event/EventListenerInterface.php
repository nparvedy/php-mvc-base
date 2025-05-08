<?php
namespace Core\Event;

/**
 * Interface que tous les écouteurs d'événements doivent implémenter
 */
interface EventListenerInterface
{
    /**
     * Méthode appelée lorsqu'un événement est déclenché
     * 
     * @param Event $event L'événement déclenché
     * @return void
     */
    public function handle(Event $event);
    
    /**
     * Retourne les événements auxquels cet écouteur souhaite s'abonner
     * 
     * @return array Tableau associatif [nom de l'événement => méthode à appeler]
     */
    public static function getSubscribedEvents();
}