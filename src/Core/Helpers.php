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
}