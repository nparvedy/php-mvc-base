<?php
namespace Core\Cache;

/**
 * Classe abstraite de base pour les implémentations de cache
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * Préfixe pour toutes les clés de cache
     * @var string
     */
    protected $prefix = '';
    
    /**
     * Constructeur
     * 
     * @param string $prefix Préfixe pour toutes les clés de cache
     */
    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
    }
    
    /**
     * Normaliser une clé de cache avec le préfixe
     * 
     * @param string $key Clé à normaliser
     * @return string
     */
    protected function key($key)
    {
        return $this->prefix . $key;
    }
    
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return $this->get($key, $this) !== $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function remember($key, $ttl, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}