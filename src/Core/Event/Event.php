<?php
namespace Core\Event;

/**
 * Classe de base pour tous les événements du système
 */
class Event
{
    /**
     * Horodatage de création de l'événement
     * 
     * @var float
     */
    protected $timestamp;
    
    /**
     * Nom de l'événement (déduit de la classe)
     * 
     * @var string
     */
    protected $name;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->timestamp = microtime(true);
        $this->name = get_class($this);
    }
    
    /**
     * Obtenir l'horodatage de création de l'événement
     * 
     * @return float
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
    
    /**
     * Obtenir le nom de l'événement
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Définir le nom de l'événement
     * 
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}