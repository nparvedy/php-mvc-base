<?php
namespace Core\Database\Relations;

use Core\Model;

/**
 * Relation "appartient à"
 */
class BelongsTo extends Relation
{
    /**
     * Obtenir le résultat de la relation
     *
     * @return object|null
     */
    public function getResults()
    {
        $foreignValue = $this->parent->{$this->foreignKey};
        
        if ($foreignValue === null) {
            return null;
        }
        
        return $this->related->query()
            ->where($this->localKey, $foreignValue)
            ->first();
    }
    
    /**
     * Associer un modèle à la relation
     *
     * @param int|Model $model ID ou instance du modèle
     * @return int
     */
    public function associate($model)
    {
        $id = $model instanceof Model ? $model->{$this->localKey} : $model;
        
        $this->parent->{$this->foreignKey} = $id;
        
        return $this->parent->update(
            $this->parent->{$this->parent->getPrimaryKey()},
            [$this->foreignKey => $id]
        );
    }
    
    /**
     * Dissocier le modèle de la relation
     *
     * @return int
     */
    public function dissociate()
    {
        $this->parent->{$this->foreignKey} = null;
        
        return $this->parent->update(
            $this->parent->{$this->parent->getPrimaryKey()},
            [$this->foreignKey => null]
        );
    }
}