<?php
namespace Core\Database\Relations;

use Core\Model;

/**
 * Relation "a un"
 */
class HasOne extends Relation
{
    /**
     * Obtenir le résultat de la relation
     *
     * @return object|null
     */
    public function getResults()
    {
        $localValue = $this->parent->{$this->localKey};
        
        return $this->related->query()
            ->where($this->foreignKey, $localValue)
            ->first();
    }
    
    /**
     * Créer un nouveau modèle associé
     *
     * @param array $attributes
     * @return int|bool
     */
    public function create(array $attributes)
    {
        $attributes[$this->foreignKey] = $this->parent->{$this->localKey};
        
        return $this->related->create($attributes);
    }
    
    /**
     * Associer un modèle à la relation
     *
     * @param int $id
     * @return int
     */
    public function associate($id)
    {
        return $this->related->update($id, [
            $this->foreignKey => $this->parent->{$this->localKey}
        ]);
    }
    
    /**
     * Dissocier un modèle de la relation
     *
     * @return int
     */
    public function dissociate()
    {
        $result = $this->getResults();
        
        if (!$result) {
            return 0;
        }
        
        return $this->related->update($result->{$this->related->getPrimaryKey()}, [
            $this->foreignKey => null
        ]);
    }
}