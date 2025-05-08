<?php
namespace Core\I18n;

use Core\Cache\CacheInterface;

/**
 * Classe de gestion des traductions
 */
class Translator
{
    /**
     * Locale actuelle
     * @var string
     */
    protected $locale;
    
    /**
     * Locale par défaut
     * @var string
     */
    protected $fallbackLocale;
    
    /**
     * Chemin vers les fichiers de traduction
     * @var string
     */
    protected $path;
    
    /**
     * Traductions chargées
     * @var array
     */
    protected $loaded = [];
    
    /**
     * Instance de cache
     * @var CacheInterface|null
     */
    protected $cache;
    
    /**
     * Constructeur
     * 
     * @param string $locale Locale actuelle
     * @param string $fallbackLocale Locale de secours
     * @param string|null $path Chemin vers les fichiers de traduction
     * @param CacheInterface|null $cache Instance de cache
     */
    public function __construct($locale = 'fr_FR', $fallbackLocale = 'en_US', $path = null, CacheInterface $cache = null)
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
        $this->path = $path ?: ROOT_PATH . '/resources/lang';
        $this->cache = $cache;
        
        // Créer le répertoire des langues s'il n'existe pas
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }
    
    /**
     * Traduire une clé dans la langue actuelle
     * 
     * @param string $key Clé à traduire (format: 'fichier.clé')
     * @param array $params Paramètres à remplacer dans la traduction
     * @param string|null $locale Langue spécifique à utiliser
     * @return string Traduction
     */
    public function translate($key, array $params = [], $locale = null)
    {
        $locale = $locale ?: $this->locale;
        
        // Séparer le nom de fichier et la clé
        list($file, $item) = $this->parseKey($key);
        
        // Charger les traductions du fichier
        $translations = $this->load($file, $locale);
        
        // Obtenir la traduction ou utiliser la locale de secours si non trouvée
        $translation = $this->get($translations, $item);
        
        if ($translation === null && $locale !== $this->fallbackLocale) {
            $fallbackTranslations = $this->load($file, $this->fallbackLocale);
            $translation = $this->get($fallbackTranslations, $item);
        }
        
        // Si toujours pas trouvée, utiliser la clé comme traduction
        if ($translation === null) {
            return $item;
        }
        
        // Remplacer les paramètres dans la traduction
        return $this->replaceParams($translation, $params);
    }
    
    /**
     * Raccourci pour translate()
     * 
     * @param string $key
     * @param array $params
     * @param string|null $locale
     * @return string
     */
    public function trans($key, array $params = [], $locale = null)
    {
        return $this->translate($key, $params, $locale);
    }
    
    /**
     * Traduire au pluriel
     * 
     * @param string $key Clé à traduire
     * @param int $count Quantité pour déterminer la forme
     * @param array $params Paramètres à remplacer
     * @param string|null $locale Langue spécifique
     * @return string
     */
    public function translateChoice($key, $count, array $params = [], $locale = null)
    {
        $params['count'] = $count;
        
        $locale = $locale ?: $this->locale;
        
        // Séparer le nom de fichier et la clé
        list($file, $item) = $this->parseKey($key);
        
        // Charger les traductions du fichier
        $translations = $this->load($file, $locale);
        
        // Obtenir la traduction
        $translation = $this->get($translations, $item);
        
        // Utiliser la locale de secours si non trouvée
        if ($translation === null && $locale !== $this->fallbackLocale) {
            $fallbackTranslations = $this->load($file, $this->fallbackLocale);
            $translation = $this->get($fallbackTranslations, $item);
        }
        
        // Si toujours pas trouvée, utiliser la clé comme traduction
        if ($translation === null) {
            return $item;
        }
        
        // Sélectionner la forme plurielle appropriée
        $variant = $this->getPluralForm($translation, $count, $locale);
        
        // Remplacer les paramètres
        return $this->replaceParams($variant, $params);
    }
    
    /**
     * Raccourci pour translateChoice()
     */
    public function transChoice($key, $count, array $params = [], $locale = null)
    {
        return $this->translateChoice($key, $count, $params, $locale);
    }
    
    /**
     * Définir la locale actuelle
     * 
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        
        return $this;
    }
    
    /**
     * Obtenir la locale actuelle
     * 
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
    
    /**
     * Définir la locale de secours
     * 
     * @param string $locale
     * @return $this
     */
    public function setFallbackLocale($locale)
    {
        $this->fallbackLocale = $locale;
        
        return $this;
    }
    
    /**
     * Obtenir la locale de secours
     * 
     * @return string
     */
    public function getFallbackLocale()
    {
        return $this->fallbackLocale;
    }
    
    /**
     * Charger les traductions d'un fichier
     * 
     * @param string $file Nom du fichier
     * @param string $locale Langue
     * @return array
     */
    protected function load($file, $locale)
    {
        $cacheKey = "translations.{$locale}.{$file}";
        
        // Si déjà chargé en mémoire, utiliser cette version
        if (isset($this->loaded[$cacheKey])) {
            return $this->loaded[$cacheKey];
        }
        
        // Essayer de charger depuis le cache si disponible
        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== null) {
                $this->loaded[$cacheKey] = $cached;
                return $cached;
            }
        }
        
        // Charger depuis le fichier
        $path = "{$this->path}/{$locale}/{$file}.php";
        
        if (file_exists($path)) {
            $translations = require $path;
            
            // Stocker dans le cache si disponible
            if ($this->cache !== null) {
                $this->cache->set($cacheKey, $translations, 3600); // Cache pendant 1 heure
            }
            
            $this->loaded[$cacheKey] = $translations;
            return $translations;
        }
        
        return [];
    }
    
    /**
     * Séparer la clé en nom de fichier et clé interne
     * 
     * @param string $key
     * @return array [file, item]
     */
    protected function parseKey($key)
    {
        if (strpos($key, '.') !== false) {
            return explode('.', $key, 2);
        }
        
        return ['messages', $key];
    }
    
    /**
     * Obtenir une valeur depuis un tableau multidimensionnel en utilisant une notation pointée
     * 
     * @param array $array
     * @param string $key
     * @return mixed|null
     */
    protected function get($array, $key)
    {
        $segments = explode('.', $key);
        
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }
            
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    /**
     * Remplacer les paramètres dans une chaîne
     * 
     * @param string $string
     * @param array $params
     * @return string
     */
    protected function replaceParams($string, array $params)
    {
        if (empty($params)) {
            return $string;
        }
        
        $replace = [];
        
        foreach ($params as $key => $value) {
            $replace[':' . $key] = $value;
            $replace['{' . $key . '}'] = $value;
        }
        
        return strtr($string, $replace);
    }
    
    /**
     * Obtenir la forme plurielle appropriée
     * 
     * @param string $translation La chaîne contenant les différentes formes
     * @param int $count Le nombre pour déterminer la forme
     * @param string $locale La locale à utiliser
     * @return string
     */
    protected function getPluralForm($translation, $count, $locale)
    {
        // Format: "zero|singular|plural" ou "singular|plural"
        $parts = explode('|', $translation);
        
        // Gestion simplifiée du pluriel pour les principales langues européennes
        switch (count($parts)) {
            case 1:
                return $parts[0];
            case 2:
                return $count == 1 ? $parts[0] : $parts[1];
            case 3:
                if ($count == 0) {
                    return $parts[0];
                }
                return $count == 1 ? $parts[1] : $parts[2];
            default:
                // Pour les langues avec des règles complexes, utiliser seulement la première forme
                return $parts[0];
        }
    }
}