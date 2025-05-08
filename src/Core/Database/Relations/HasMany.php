<?php
namespace Core\Database\Relations;

use Core\Model;

/**
 * Relation "a plusieurs"
 */
class HasMany extends Relation
{
    /**
     * Obtenir les résultats de la relation
     *
     * @return array
     */
    public function getResults()
    {
        $localValue = $this->parent->{$this->localKey};
        
        return $this->related->query()
            ->where($this->foreignKey, $localValue)
            ->get();
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
     * @param int|null $id Si null, tous les modèles liés sont dissociés
     * @return int
     */
    public function dissociate($id = null)
    {
        $query = $this->related->query()
            ->where($this->foreignKey, $this->parent->{$this->localKey});
            
        if ($id !== null) {
            $query->where($this->related->getPrimaryKey(), $id);
        }
        
        return $query->update([
            $this->foreignKey => null
        ]);
    }
}