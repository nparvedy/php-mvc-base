<?php
namespace Core\Cache;

/**
 * Interface pour tous les drivers de cache
 */
interface CacheInterface
{
    /**
     * Récupérer une valeur du cache
     * 
     * @param string $key Clé de l'élément
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public function get($key, $default = null);
    
    /**
     * Stocker une valeur dans le cache
     * 
     * @param string $key Clé de l'élément
     * @param mixed $value Valeur à stocker
     * @param int|null $ttl Durée de vie en secondes (null = sans expiration)
     * @return bool
     */
    public function set($key, $value, $ttl = null);
    
    /**
     * Vérifier si une clé existe dans le cache
     * 
     * @param string $key Clé à vérifier
     * @return bool
     */
    public function has($key);
    
    /**
     * Supprimer un élément du cache
     * 
     * @param string $key Clé à supprimer
     * @return bool
     */
    public function delete($key);
    
    /**
     * Vider tout le cache
     * 
     * @return bool
     */
    public function clear();
    
    /**
     * Récupérer ou calculer une valeur
     * 
     * @param string $key Clé de l'élément
     * @param int|null $ttl Durée de vie en secondes
     * @param callable $callback Fonction qui génère la valeur si elle n'existe pas
     * @return mixed
     */
    public function remember($key, $ttl, callable $callback);
}