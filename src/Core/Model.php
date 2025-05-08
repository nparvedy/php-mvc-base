<?php
namespace Core;

use Core\Database\QueryBuilder;
use Core\Database\Relations\HasMany;
use Core\Database\Relations\HasOne;
use Core\Database\Relations\BelongsTo;
use Core\Database\Relations\BelongsToMany;

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $with = [];
    protected $queryBuilder;
    protected $relations = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->queryBuilder = new QueryBuilder($this->db, $this->table);
    }

    /**
     * Obtenir un nouveau Query Builder
     *
     * @return QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder($this->db, $this->table);
    }

    /**
     * Trouve tous les enregistrements
     *
     * @return array
     */
    public function findAll()
    {
        return $this->query()->get();
    }

    /**
     * Trouve un enregistrement par son identifiant
     *
     * @param mixed $id
     * @return object|null
     */
    public function findById($id)
    {
        return $this->query()->where($this->primaryKey, $id)->first();
    }

    /**
     * Crée un nouvel enregistrement
     *
     * @param array $data
     * @return int|bool ID de l'enregistrement créé
     */
    public function create(array $data)
    {
        // Filtrer les données selon les champs remplissables
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        // Ajouter les timestamps si nécessaire
        if (method_exists($this, 'useTimestamps') && $this->useTimestamps()) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }
        
        return $this->query()->insert($data);
    }

    /**
     * Met à jour un enregistrement
     *
     * @param mixed $id
     * @param array $data
     * @return int
     */
    public function update($id, array $data)
    {
        // Filtrer les données selon les champs remplissables
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        // Mettre à jour le timestamp si nécessaire
        if (method_exists($this, 'useTimestamps') && $this->useTimestamps()) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->query()->where($this->primaryKey, $id)->update($data);
    }

    /**
     * Supprime un enregistrement
     *
     * @param mixed $id
     * @return int
     */
    public function delete($id)
    {
        return $this->query()->where($this->primaryKey, $id)->delete();
    }

    /**
     * Définir une relation "a plusieurs"
     *
     * @param string $related Classe du modèle relié
     * @param string|null $foreignKey Clé étrangère
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower(class_basename($this)) . '_id';
        return new HasMany($this, new $related(), $foreignKey, $this->primaryKey);
    }

    /**
     * Définir une relation "a un"
     *
     * @param string $related Classe du modèle relié
     * @param string|null $foreignKey Clé étrangère
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower(class_basename($this)) . '_id';
        return new HasOne($this, new $related(), $foreignKey, $this->primaryKey);
    }

    /**
     * Définir une relation "appartient à"
     *
     * @param string $related Classe du modèle relié
     * @param string|null $foreignKey Clé étrangère
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower(class_basename(new $related())) . '_id';
        return new BelongsTo($this, new $related(), $foreignKey, 'id');
    }

    /**
     * Définir une relation "appartient à plusieurs"
     *
     * @param string $related Classe du modèle relié
     * @param string|null $pivotTable Table pivot
     * @param string|null $foreignPivotKey Clé étrangère de la table pivot pour ce modèle
     * @param string|null $relatedPivotKey Clé étrangère de la table pivot pour le modèle relié
     * @return BelongsToMany
     */
    public function belongsToMany($related, $pivotTable = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $related = new $related();
        
        $pivotTable = $pivotTable ?: $this->createPivotTableName($this, $related);
        $foreignPivotKey = $foreignPivotKey ?: strtolower(class_basename($this)) . '_id';
        $relatedPivotKey = $relatedPivotKey ?: strtolower(class_basename($related)) . '_id';
        
        return new BelongsToMany($this, $related, $pivotTable, $foreignPivotKey, $relatedPivotKey);
    }

    /**
     * Créer un nom de table pivot à partir de deux modèles
     *
     * @param Model $model1
     * @param Model $model2
     * @return string
     */
    protected function createPivotTableName(Model $model1, Model $model2)
    {
        $models = [
            strtolower(class_basename($model1)),
            strtolower(class_basename($model2))
        ];
        
        sort($models);
        
        return implode('_', $models);
    }

    /**
     * Obtenir le nom de la classe sans namespace
     *
     * @param object|string $class
     * @return string
     */
    protected function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Obtenir l'attribut de clé primaire
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Obtenir le nom de la table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Appel dynamique des méthodes du query builder
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->query(), $method)) {
            return call_user_func_array([$this->query(), $method], $arguments);
        }
        
        throw new \BadMethodCallException("Méthode {$method} non trouvée dans le modèle " . get_class($this));
    }

    /**
     * Appel statique des méthodes du modèle
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        return (new static)->$method(...$arguments);
    }
}