<?php
namespace Core\Database;

use Core\Database;

/**
 * Constructeur de requêtes SQL
 */
class QueryBuilder
{
    /**
     * Instance de Database
     * @var Database
     */
    protected $db;
    
    /**
     * Nom de la table
     * @var string
     */
    protected $table;
    
    /**
     * Les colonnes à sélectionner
     * @var array
     */
    protected $columns = ['*'];
    
    /**
     * Les clauses WHERE
     * @var array
     */
    protected $wheres = [];
    
    /**
     * Les clauses JOIN
     * @var array
     */
    protected $joins = [];
    
    /**
     * Les clauses ORDER BY
     * @var array
     */
    protected $orders = [];
    
    /**
     * La clause LIMIT
     * @var int|null
     */
    protected $limit = null;
    
    /**
     * La clause OFFSET
     * @var int|null
     */
    protected $offset = null;
    
    /**
     * Constructeur
     * 
     * @param Database $db Instance de Database
     * @param string $table Nom de la table
     */
    public function __construct(Database $db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }
    
    /**
     * Spécifier les colonnes à récupérer
     * 
     * @param array|string $columns Colonnes à récupérer
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        
        return $this;
    }
    
    /**
     * Ajouter une clause WHERE
     * 
     * @param string $column Colonne
     * @param string $operator Opérateur
     * @param mixed $value Valeur
     * @param string $boolean Opérateur logique (AND/OR)
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        // Si seulement deux paramètres sont fournis, utiliser = comme opérateur
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        
        return $this;
    }
    
    /**
     * Ajouter une clause WHERE avec OR
     * 
     * @param string $column Colonne
     * @param string $operator Opérateur
     * @param mixed $value Valeur
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }
    
    /**
     * Ajouter une clause WHERE IN
     * 
     * @param string $column Colonne
     * @param array $values Valeurs
     * @param string $boolean Opérateur logique (AND/OR)
     * @return $this
     */
    public function whereIn($column, array $values, $boolean = 'AND')
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        
        return $this;
    }
    
    /**
     * Ajouter une clause JOIN
     * 
     * @param string $table Table à joindre
     * @param string $first Première colonne
     * @param string $operator Opérateur
     * @param string $second Seconde colonne
     * @param string $type Type de jointure
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER')
    {
        // Si seulement trois paramètres sont fournis, utiliser = comme opérateur
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }
        
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        
        return $this;
    }
    
    /**
     * Ajouter une clause LEFT JOIN
     * 
     * @param string $table Table à joindre
     * @param string $first Première colonne
     * @param string $operator Opérateur
     * @param string $second Seconde colonne
     * @return $this
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }
    
    /**
     * Ajouter une clause ORDER BY
     * 
     * @param string $column Colonne
     * @param string $direction Direction (ASC/DESC)
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orders[] = compact('column', 'direction');
        
        return $this;
    }
    
    /**
     * Ajouter une clause LIMIT
     * 
     * @param int $limit Limite
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        
        return $this;
    }
    
    /**
     * Ajouter une clause OFFSET
     * 
     * @param int $offset Décalage
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        
        return $this;
    }
    
    /**
     * Raccourci pour limit et offset (pagination)
     * 
     * @param int $page Numéro de page
     * @param int $perPage Éléments par page
     * @return $this
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }
    
    /**
     * Récupérer le premier résultat
     * 
     * @return mixed
     */
    public function first()
    {
        return $this->limit(1)->get(true);
    }
    
    /**
     * Récupérer tous les résultats
     * 
     * @param bool $single Retourner un seul résultat
     * @return array|object
     */
    public function get($single = false)
    {
        list($sql, $params) = $this->buildSelectQuery();
        
        return $this->db->query($sql, $params, $single);
    }
    
    /**
     * Compter le nombre de résultats
     * 
     * @return int
     */
    public function count()
    {
        $result = $this->select('COUNT(*) as count')->first();
        
        return (int) $result->count;
    }
    
    /**
     * Insérer des données
     * 
     * @param array $data Données à insérer
     * @return int|bool
     */
    public function insert(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->db->execute($sql, $data);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Mettre à jour des données
     * 
     * @param array $data Données à mettre à jour
     * @return int
     */
    public function update(array $data)
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = :{$column}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        list($whereSql, $whereParams) = $this->buildWhereClause();
        
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }
        
        $params = array_merge($data, $whereParams);
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Supprimer des données
     * 
     * @return int
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        
        list($whereSql, $whereParams) = $this->buildWhereClause();
        
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }
        
        return $this->db->execute($sql, $whereParams);
    }
    
    /**
     * Construire la requête SELECT complète
     * 
     * @return array [sql, params]
     */
    protected function buildSelectQuery()
    {
        $params = [];
        $columns = implode(', ', $this->columns);
        
        $sql = "SELECT {$columns} FROM {$this->table}";
        
        // Ajouter les JOIN
        if (!empty($this->joins)) {
            $sql .= $this->buildJoinClause();
        }
        
        // Ajouter les WHERE
        if (!empty($this->wheres)) {
            list($whereSql, $whereParams) = $this->buildWhereClause();
            $sql .= " WHERE {$whereSql}";
            $params = array_merge($params, $whereParams);
        }
        
        // Ajouter les ORDER BY
        if (!empty($this->orders)) {
            $orders = [];
            foreach ($this->orders as $order) {
                $orders[] = "{$order['column']} {$order['direction']}";
            }
            
            $sql .= " ORDER BY " . implode(', ', $orders);
        }
        
        // Ajouter LIMIT et OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        
        return [$sql, $params];
    }
    
    /**
     * Construire la clause JOIN
     * 
     * @return string
     */
    protected function buildJoinClause()
    {
        $sql = '';
        
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        return $sql;
    }
    
    /**
     * Construire la clause WHERE
     * 
     * @return array [sql, params]
     */
    protected function buildWhereClause()
    {
        $sql = '';
        $params = [];
        
        foreach ($this->wheres as $i => $where) {
            $boolean = ($i === 0) ? '' : $where['boolean'] . ' ';
            
            if (isset($where['type']) && $where['type'] === 'in') {
                $placeholders = [];
                
                foreach ($where['values'] as $j => $value) {
                    $param = "in_{$i}_{$j}";
                    $placeholders[] = ":{$param}";
                    $params[$param] = $value;
                }
                
                $sql .= $boolean . $where['column'] . ' IN (' . implode(', ', $placeholders) . ')';
            } else {
                $param = "where_{$i}";
                $sql .= $boolean . $where['column'] . ' ' . $where['operator'] . ' :' . $param;
                $params[$param] = $where['value'];
            }
        }
        
        return [$sql, $params];
    }
}