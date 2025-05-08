<?php
namespace Core;

class Helpers
{
    /**
     * Génère une URL avec le chemin de base de l'application
     * 
     * @param string $path Le chemin relatif 
     * @return string L'URL complète avec le chemin de base
     */
    public static function url($path = '')
    {
        // Retirer le slash initial si présent
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
        
        // Définir le chemin de base de l'application
        $basePath = '/test/mvc-php/public';
        
        // Construire l'URL complète
        return $basePath . ($path ? '/' . $path : '');
    }
    
    /**
     * Traduire une chaîne de caractères
     * 
     * @param string $key Clé de traduction
     * @param array $params Paramètres de remplacement
     * @param string|null $locale Locale à utiliser
     * @return string
     */
    public static function translate($key, array $params = [], $locale = null)
    {
        static $translator = null;
        
        if ($translator === null) {
            // Récupérer le traducteur depuis le conteneur si disponible
            if (class_exists('Core\\Container')) {
                $container = Container::getInstance();
                if ($container->has('translator')) {
                    $translator = $container->make('translator');
                } else {
                    // Créer un nouveau traducteur si non disponible dans le conteneur
                    $translator = new I18n\Translator();
                }
            } else {
                $translator = new I18n\Translator();
            }
        }
        
        return $translator->translate($key, $params, $locale);
    }
    
    /**
     * Raccourci pour translate()
     * 
     * @param string $key Clé de traduction
     * @param array $params Paramètres de remplacement
     * @param string|null $locale Locale à utiliser
     * @return string
     */
    public static function __($key, array $params = [], $locale = null)
    {
        return static::translate($key, $params, $locale);
    }
    
    /**
     * Traduire une chaîne de caractères avec gestion du pluriel
     * 
     * @param string $key Clé de traduction
     * @param int $count Nombre pour déterminer la forme plurielle
     * @param array $params Paramètres de remplacement
     * @param string|null $locale Locale à utiliser
     * @return string
     */
    public static function translateChoice($key, $count, array $params = [], $locale = null)
    {
        static $translator = null;
        
        if ($translator === null) {
            // Récupérer le traducteur depuis le conteneur si disponible
            if (class_exists('Core\\Container')) {
                $container = Container::getInstance();
                if ($container->has('translator')) {
                    $translator = $container->make('translator');
                } else {
                    // Créer un nouveau traducteur si non disponible dans le conteneur
                    $translator = new I18n\Translator();
                }
            } else {
                $translator = new I18n\Translator();
            }
        }
        
        return $translator->translateChoice($key, $count, $params, $locale);
    }
    
    /**
     * Échapper les données pour une sortie HTML sécurisée
     * 
     * @param mixed $data Les données à échapper
     * @return mixed Les données échappées
     */
    public static function e($data)
    {
        if (is_array($data)) {
            $escaped = [];
            foreach ($data as $key => $value) {
                $escaped[$key] = static::e($value);
            }
            return $escaped;
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8', false);
        }
        
        return $data;
    }
    
    /**
     * Formater une date selon la locale actuelle
     * 
     * @param mixed $date Date à formater (timestamp, string ou DateTime)
     * @param string $format Format de la date
     * @param string|null $locale Locale à utiliser
     * @return string Date formatée
     */
    public static function formatDate($date, $format = 'd/m/Y H:i', $locale = null)
    {
        if (is_string($date) && !is_numeric($date)) {
            $date = strtotime($date);
        }
        
        if ($date instanceof \DateTime) {
            $date = $date->getTimestamp();
        }
        
        if ($locale !== null) {
            $oldLocale = setlocale(LC_TIME, '0');
            setlocale(LC_TIME, $locale);
            $formattedDate = strftime($format, $date);
            setlocale(LC_TIME, $oldLocale);
            return $formattedDate;
        }
        
        return date($format, $date);
    }
}