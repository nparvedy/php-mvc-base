<?php
namespace Core\Event;

/**
 * Classe qui gère la distribution des événements aux écouteurs
 */
class EventDispatcher
{
    /**
     * Liste des écouteurs par type d'événement
     * @var array
     */
    protected $listeners = [];
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        // Initialisation
    }
    
    /**
     * Ajoute un écouteur pour un événement spécifique
     *
     * @param string $eventName Nom de l'événement
     * @param EventListenerInterface $listener Écouteur à attacher
     * @return self
     */
    public function addListener($eventName, EventListenerInterface $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        
        $this->listeners[$eventName][] = $listener;
        
        return $this;
    }
    
    /**
     * Distribue un événement à tous les écouteurs enregistrés
     *
     * @param object $event L'événement
     * @return void
     */
    public function dispatch($event)
    {
        $eventName = $event->getName();
        
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        
        foreach ($this->listeners[$eventName] as $listener) {
            $listener->handle($event);
        }
    }
    
    /**
     * Supprime tous les écouteurs pour un événement spécifique
     *
     * @param string $eventName Nom de l'événement
     * @return self
     */
    public function removeListeners($eventName)
    {
        if (isset($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }
        
        return $this;
    }
    
    /**
     * Vérifie si un événement a des écouteurs
     *
     * @param string $eventName Nom de l'événement
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]) && count($this->listeners[$eventName]) > 0;
    }
    
    /**
     * Obtient tous les écouteurs pour un événement
     *
     * @param string $eventName Nom de l'événement
     * @return array
     */
    public function getListeners($eventName)
    {
        return $this->listeners[$eventName] ?? [];
    }
}