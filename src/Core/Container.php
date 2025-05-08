<?php
namespace Core;

class Container
{
    /**
     * Les services enregistrés
     * @var array
     */
    protected $bindings = [];
    
    /**
     * Les instances partagées (singletons)
     * @var array
     */
    protected $instances = [];
    
    /**
     * Instance unique du conteneur (singleton)
     * @var Container
     */
    protected static $instance;
    
    /**
     * Récupérer l'instance unique du conteneur
     *
     * @return Container
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Enregistrer un service dans le conteneur
     *
     * @param string $abstract Identifiant du service
     * @param mixed $concrete Instance concrète ou callable
     * @param bool $shared Si true, le service est un singleton
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }
    
    /**
     * Enregistrer un singleton dans le conteneur
     *
     * @param string $abstract Identifiant du service
     * @param mixed $concrete Instance concrète ou callable
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Enregistrer une instance partagée
     *
     * @param string $abstract Identifiant du service
     * @param mixed $instance L'instance
     * @return void
     */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }
    
    /**
     * Résoudre un service depuis le conteneur
     *
     * @param string $abstract Identifiant du service
     * @param array $parameters Paramètres supplémentaires
     * @return mixed
     * @throws \Exception
     */
    public function make($abstract, array $parameters = [])
    {
        // Vérifier si une instance existe déjà
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Récupérer la définition du service
        $binding = $this->bindings[$abstract] ?? null;
        
        if (is_null($binding)) {
            // Si aucune définition n'existe, essayer de résoudre directement la classe
            if (class_exists($abstract)) {
                $concrete = $abstract;
                $shared = false;
            } else {
                throw new \Exception("Aucun service lié à '{$abstract}' n'a été trouvé.");
            }
        } else {
            $concrete = $binding['concrete'];
            $shared = $binding['shared'];
        }
        
        // Résoudre l'instance
        if ($concrete === $abstract || is_string($concrete)) {
            // Si concrete est une chaîne, essayer d'instancier la classe
            $object = $this->build($concrete, $parameters);
        } elseif (is_callable($concrete)) {
            // Si concrete est un callable, l'exécuter avec le conteneur comme argument
            $object = $concrete($this, $parameters);
        } else {
            // Sinon, retourner la valeur directement
            $object = $concrete;
        }
        
        // Si le service est partagé, stocker l'instance
        if ($shared) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    /**
     * Construire une instance d'une classe en résolvant ses dépendances
     *
     * @param string $concrete Le nom de la classe à instancier
     * @param array $parameters Paramètres supplémentaires
     * @return object
     * @throws \Exception
     */
    protected function build($concrete, array $parameters = [])
    {
        // Réflexion sur la classe
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new \Exception("Classe '{$concrete}' introuvable: " . $e->getMessage());
        }
        
        // Vérifier si la classe est instanciable
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Classe '{$concrete}' n'est pas instanciable.");
        }
        
        // Récupérer le constructeur
        $constructor = $reflector->getConstructor();
        
        // S'il n'y a pas de constructeur, retourner une nouvelle instance
        if (is_null($constructor)) {
            return new $concrete();
        }
        
        // Récupérer les paramètres du constructeur
        $dependencies = $constructor->getParameters();
        
        // Résoudre chaque dépendance
        $instances = $this->resolveDependencies($dependencies, $parameters);
        
        // Créer une nouvelle instance avec les dépendances résolues
        return $reflector->newInstanceArgs($instances);
    }
    
    /**
     * Résoudre les dépendances à injecter
     *
     * @param array $dependencies Dépendances à résoudre
     * @param array $parameters Paramètres supplémentaires
     * @return array
     * @throws \Exception
     */
    protected function resolveDependencies(array $dependencies, array $parameters = [])
    {
        $results = [];
        
        foreach ($dependencies as $dependency) {
            // Récupérer le nom du paramètre
            $name = $dependency->getName();
            
            // Si le paramètre existe dans les paramètres fournis, l'utiliser
            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }
            
            // Si le paramètre a une valeur par défaut et n'est pas fourni, utiliser la valeur par défaut
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
                continue;
            }
            
            // Si le paramètre a un type, essayer de résoudre la classe
            $type = $dependency->getType();
            
            if ($type && !$type->isBuiltin()) {
                $results[] = $this->make($type->getName());
                continue;
            }
            
            // Si nous arrivons ici, la dépendance n'a pas pu être résolue
            throw new \Exception("Impossible de résoudre la dépendance '{$name}' dans la classe.");
        }
        
        return $results;
    }
    
    /**
     * Déterminer si un service est lié dans le conteneur
     *
     * @param string $abstract
     * @return bool
     */
    public function has($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * Alias pour make() en utilisant l'accès par propriété
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->make($key);
    }
}