<?php
namespace Core;

class Validator
{
    /**
     * Données à valider
     * @var array
     */
    private $data;
    
    /**
     * Erreurs de validation
     * @var array
     */
    private $errors = [];
    
    /**
     * Règles personnalisées
     * @var array
     */
    private $customRules = [];

    /**
     * Constructeur
     *
     * @param array $data Données à valider
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Vérifier si un champ est présent et non vide
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function required($field, $message = null)
    {
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "Le champ {$field} est requis.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur est une adresse email valide
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function email($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit être une adresse email valide.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur a une longueur minimale
     *
     * @param string $field Nom du champ
     * @param int $length Longueur minimale
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function min($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && mb_strlen((string)$this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit contenir au moins {$length} caractères.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur a une longueur maximale
     *
     * @param string $field Nom du champ
     * @param int $length Longueur maximale
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function max($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && mb_strlen((string)$this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "Le champ {$field} ne doit pas dépasser {$length} caractères.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur est numérique
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function numeric($field, $message = null)
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit être une valeur numérique.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur correspond à une expression régulière
     *
     * @param string $field Nom du champ
     * @param string $pattern Expression régulière
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function pattern($field, $pattern, $message = null)
    {
        if (isset($this->data[$field]) && !preg_match($pattern, (string)$this->data[$field])) {
            $this->errors[$field] = $message ?? "Le format du champ {$field} est invalide.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur correspond à une autre valeur
     *
     * @param string $field Nom du champ
     * @param string $otherField Nom du champ à comparer
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function matches($field, $otherField, $message = null)
    {
        if (isset($this->data[$field], $this->data[$otherField]) 
            && $this->data[$field] !== $this->data[$otherField]) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit correspondre au champ {$otherField}.";
        }
        
        return $this;
    }

    /**
     * Vérifier si une valeur est dans une liste d'options
     *
     * @param string $field Nom du champ
     * @param array $options Liste des options valides
     * @param string $message Message d'erreur personnalisé
     * @return $this
     */
    public function inList($field, array $options, $message = null)
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $options, true)) {
            $this->errors[$field] = $message ?? "Le champ {$field} contient une valeur invalide.";
        }
        
        return $this;
    }

    /**
     * Ajouter une règle de validation personnalisée
     *
     * @param string $name Nom de la règle
     * @param callable $callback Fonction de validation (doit retourner un booléen)
     * @return $this
     */
    public function addRule($name, callable $callback)
    {
        $this->customRules[$name] = $callback;
        return $this;
    }

    /**
     * Appliquer une règle personnalisée
     *
     * @param string $field Nom du champ
     * @param string $ruleName Nom de la règle à appliquer
     * @param mixed $param Paramètre optionnel pour la règle
     * @param string $message Message d'erreur personnalisé
     * @return $this
     * @throws \InvalidArgumentException Si la règle n'existe pas
     */
    public function apply($field, $ruleName, $param = null, $message = null)
    {
        if (!isset($this->customRules[$ruleName])) {
            throw new \InvalidArgumentException("La règle de validation '{$ruleName}' n'existe pas.");
        }

        $callback = $this->customRules[$ruleName];
        $fieldValue = $this->data[$field] ?? null;

        if (isset($this->data[$field]) && !$callback($fieldValue, $param)) {
            $this->errors[$field] = $message ?? "Le champ {$field} est invalide.";
        }

        return $this;
    }
    
    /**
     * Vérifier si la validation a réussi
     *
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors);
    }

    /**
     * Récupérer toutes les erreurs de validation
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Récupérer l'erreur d'un champ spécifique
     *
     * @param string $field Nom du champ
     * @return string|null Message d'erreur ou null si aucune erreur
     */
    public function getError($field)
    {
        return $this->errors[$field] ?? null;
    }
}