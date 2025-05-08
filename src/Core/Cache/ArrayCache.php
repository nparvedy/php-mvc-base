<?php
namespace Core\Cache;

/**
 * Implémentation du cache utilisant un tableau en mémoire (pour les tests ou données temporaires)
 */
class ArrayCache extends AbstractCache
{
    /**
     * Les données stockées en mémoire
     * @var array
     */
    protected $data = [];
    
    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $key = $this->key($key);
        
        if (!array_key_exists($key, $this->data)) {
            return $default;
        }
        
        $item = $this->data[$key];
        
        // Vérifier l'expiration
        if ($item['expiration'] !== null && $item['expiration'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $item['value'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->key($key);
        
        $this->data[$key] = [
            'value' => $value,
            'expiration' => $ttl === null ? null : time() + $ttl
        ];
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->key($key);
        
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->data = [];
        
        return true;
    }
}