<?php
namespace Core\Cache;

/**
 * Gestionnaire de cache qui permet de choisir et de configurer différents drivers de cache
 */
class CacheManager
{
    /**
     * Les drivers de cache disponibles
     * @var array
     */
    protected $stores = [];
    
    /**
     * Driver de cache par défaut
     * @var string
     */
    protected $defaultStore = 'file';
    
    /**
     * Configuration du cache
     * @var array
     */
    protected $config = [];
    
    /**
     * Constructeur
     * 
     * @param array $config Configuration du cache
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        
        if (isset($config['default'])) {
            $this->defaultStore = $config['default'];
        }
    }
    
    /**
     * Obtenir un driver de cache spécifique
     * 
     * @param string|null $store Nom du driver de cache
     * @return CacheInterface
     * @throws \Exception
     */
    public function store($store = null)
    {
        $storeName = $store ?: $this->defaultStore;
        
        // Si le driver n'est pas déjà instancié, le créer
        if (!isset($this->stores[$storeName])) {
            $this->stores[$storeName] = $this->createStore($storeName);
        }
        
        return $this->stores[$storeName];
    }
    
    /**
     * Créer une nouvelle instance d'un driver de cache
     * 
     * @param string $store Nom du driver de cache
     * @return CacheInterface
     * @throws \Exception
     */
    protected function createStore($store)
    {
        $config = $this->getStoreConfig($store);
        $driver = $config['driver'] ?? $store;
        
        switch ($driver) {
            case 'file':
                return new FileCache($config['path'] ?? null, $config['prefix'] ?? '');
            case 'array':
                return new ArrayCache($config['prefix'] ?? '');
            // Vous pouvez ajouter d'autres drivers ici (Redis, Memcached, etc.)
            default:
                throw new \Exception("Driver de cache non supporté: {$driver}");
        }
    }
    
    /**
     * Obtenir la configuration pour un driver de cache spécifique
     * 
     * @param string $store Nom du driver de cache
     * @return array
     */
    protected function getStoreConfig($store)
    {
        return $this->config['stores'][$store] ?? [];
    }
    
    /**
     * Appels dynamiques aux méthodes du driver de cache par défaut
     * 
     * @param string $method Nom de la méthode
     * @param array $parameters Paramètres de la méthode
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}