<?php
namespace Core\Cache;

/**
 * Implémentation du cache utilisant des fichiers
 */
class FileCache extends AbstractCache
{
    /**
     * Répertoire de stockage des fichiers de cache
     * @var string
     */
    protected $directory;

    /**
     * Constructeur
     * 
     * @param string $directory Répertoire de stockage des fichiers de cache
     * @param string $prefix Préfixe pour toutes les clés de cache
     */
    public function __construct($directory = null, $prefix = '')
    {
        parent::__construct($prefix);
        
        $this->directory = $directory ?: ROOT_PATH . '/var/cache/data';
        
        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = file_get_contents($file);
        $data = unserialize($content);
        
        // Vérifier l'expiration
        if (isset($data['expiration']) && $data['expiration'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expiration' => $ttl === null ? null : time() + $ttl
        ];
        
        $content = serialize($data);
        
        return file_put_contents($file, $content) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $files = glob($this->directory . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Obtenir le chemin complet du fichier de cache pour une clé
     * 
     * @param string $key Clé de cache
     * @return string
     */
    protected function getFilePath($key)
    {
        $key = $this->key($key);
        $hash = md5($key);
        
        return $this->directory . '/' . $hash . '.cache';
    }
}