<?php
namespace Core\Database\Relations;

use Core\Model;
use Core\Database;

/**
 * Relation "appartient à plusieurs"
 */
class BelongsToMany extends Relation
{
    /**
     * Table pivot
     * @var string
     */
    protected $pivotTable;
    
    /**
     * Clé étrangère dans la table pivot pour le modèle parent
     * @var string
     */
    protected $foreignPivotKey;
    
    /**
     * Clé étrangère dans la table pivot pour le modèle relié
     * @var string
     */
    protected $relatedPivotKey;
    
    /**
     * Colonnes additionnelles à sélectionner depuis la table pivot
     * @var array
     */
    protected $pivotColumns = [];
    
    /**
     * Constructeur
     *
     * @param Model $parent
     * @param Model $related
     * @param string $pivotTable
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     */
    public function __construct(Model $parent, Model $related, $pivotTable, $foreignPivotKey, $relatedPivotKey)
    {
        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        
        parent::__construct($parent, $related, $foreignPivotKey, $parent->getPrimaryKey());
    }
    
    /**
     * Spécifier les colonnes additionnelles à sélectionner depuis la table pivot
     *
     * @param array $columns
     * @return $this
     */
    public function withPivot(array $columns)
    {
        $this->pivotColumns = $columns;
        
        return $this;
    }
    
    /**
     * Obtenir les résultats de la relation
     *
     * @return array
     */
    public function getResults()
    {
        $localValue = $this->parent->{$this->localKey};
        
        // Construire manuellement la requête pour joindre la table pivot
        $relatedTable = $this->related->getTable();
        $relatedPrimaryKey = $this->related->getPrimaryKey();
        
        $selectColumns = ["{$relatedTable}.*"];
        
        // Ajouter les colonnes pivot si nécessaire
        if (!empty($this->pivotColumns)) {
            foreach ($this->pivotColumns as $column) {
                $selectColumns[] = "{$this->pivotTable}.{$column} as pivot_{$column}";
            }
        }
        
        // Construire la requête
        $query = $this->related->query()
            ->select($selectColumns)
            ->join(
                $this->pivotTable,
                "{$relatedTable}.{$relatedPrimaryKey}",
                '=',
                "{$this->pivotTable}.{$this->relatedPivotKey}"
            )
            ->where("{$this->pivotTable}.{$this->foreignPivotKey}", $localValue);
            
        return $query->get();
    }
    
    /**
     * Attacher un ou plusieurs modèles à la relation
     *
     * @param int|array|Model $ids
     * @param array $attributes Attributs supplémentaires pour la table pivot
     * @return void
     */
    public function attach($ids, array $attributes = [])
    {
        $db = Database::getInstance();
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $parentId = $this->parent->{$this->localKey};
        
        foreach ($ids as $id) {
            $relatedId = $id instanceof Model ? $id->{$id->getPrimaryKey()} : $id;
            
            // Préparer les données
            $data = array_merge([
                $this->foreignPivotKey => $parentId,
                $this->relatedPivotKey => $relatedId
            ], $attributes);
            
            // Construire la requête manuellement
            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->pivotTable} ({$columns}) VALUES ({$values})";
            
            // Exécuter la requête
            $db->execute($sql, $data);
        }
    }
    
    /**
     * Détacher un ou plusieurs modèles de la relation
     *
     * @param int|array|null $ids Si null, tous les modèles sont détachés
     * @return int
     */
    public function detach($ids = null)
    {
        $db = Database::getInstance();
        $query = "DELETE FROM {$this->pivotTable} WHERE {$this->foreignPivotKey} = :parentId";
        $params = ['parentId' => $this->parent->{$this->localKey}];
        
        if ($ids !== null) {
            $ids = is_array($ids) ? $ids : [$ids];
            
            // Convertir les objets Model en IDs si nécessaire
            $processedIds = [];
            foreach ($ids as $id) {
                $processedIds[] = $id instanceof Model ? $id->{$id->getPrimaryKey()} : $id;
            }
            
            // Ajouter la condition pour les IDs spécifiés
            $placeholders = [];
            foreach ($processedIds as $i => $id) {
                $key = "relatedId{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $id;
            }
            
            $query .= " AND {$this->relatedPivotKey} IN (" . implode(', ', $placeholders) . ")";
        }
        
        return $db->execute($query, $params);
    }
    
    /**
     * Synchroniser la liste des IDs pour la relation
     *
     * @param array $ids
     * @param array $attributes Attributs supplémentaires pour la table pivot
     * @return array
     */
    public function sync(array $ids, array $attributes = [])
    {
        // Récupérer les IDs actuellement attachés
        $currentIds = [];
        $current = $this->getResults();
        
        $relatedPrimaryKey = $this->related->getPrimaryKey();
        foreach ($current as $related) {
            $currentIds[] = $related->{$relatedPrimaryKey};
        }
        
        // Convertir les objets Model en IDs si nécessaire
        $processedIds = [];
        foreach ($ids as $id) {
            $processedIds[] = $id instanceof Model ? $id->{$id->getPrimaryKey()} : $id;
        }
        
        // Déterminer quels IDs doivent être détachés et attachés
        $detach = array_diff($currentIds, $processedIds);
        $attach = array_diff($processedIds, $currentIds);
        
        // Détacher les IDs qui ne sont plus dans la liste
        if (!empty($detach)) {
            $this->detach($detach);
        }
        
        // Attacher les nouveaux IDs
        if (!empty($attach)) {
            $this->attach($attach, $attributes);
        }
        
        return [
            'attached' => $attach,
            'detached' => $detach,
            'updated' => array_intersect($processedIds, $currentIds)
        ];
    }
    
    /**
     * Mettre à jour les attributs de la table pivot pour une relation
     *
     * @param array|int $id
     * @param array $attributes
     * @return int
     */
    public function updatePivot($id, array $attributes)
    {
        if (empty($attributes)) {
            return 0;
        }
        
        $db = Database::getInstance();
        
        // Préparer les clauses SET
        $sets = [];
        foreach (array_keys($attributes) as $key) {
            $sets[] = "{$key} = :{$key}";
        }
        
        $query = "UPDATE {$this->pivotTable} SET " . implode(', ', $sets)
               . " WHERE {$this->foreignPivotKey} = :parentId"
               . " AND {$this->relatedPivotKey} = :relatedId";
               
        $params = array_merge($attributes, [
            'parentId' => $this->parent->{$this->localKey},
            'relatedId' => $id instanceof Model ? $id->{$id->getPrimaryKey()} : $id
        ]);
        
        return $db->execute($query, $params);
    }
}