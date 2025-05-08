<?php
namespace Core\Database\Relations;

use Core\Model;

/**
 * Classe abstraite pour toutes les relations entre modèles
 */
abstract class Relation
{
    /**
     * Le modèle parent
     * @var Model
     */
    protected $parent;
    
    /**
     * Le modèle lié
     * @var Model
     */
    protected $related;
    
    /**
     * La clé étrangère
     * @var string
     */
    protected $foreignKey;
    
    /**
     * La clé locale
     * @var string
     */
    protected $localKey;
    
    /**
     * Constructeur
     * 
     * @param Model $parent
     * @param Model $related
     * @param string $foreignKey
     * @param string $localKey
     */
    public function __construct(Model $parent, Model $related, $foreignKey, $localKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }
    
    /**
     * Exécute la requête pour la relation
     * 
     * @return mixed
     */
    abstract public function getResults();
}