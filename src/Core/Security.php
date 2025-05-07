<?php
namespace Core;

class Security
{
    /**
     * Instance de Session
     * @var Session
     */
    private $session;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Nettoyer les données pour prévenir les attaques XSS
     *
     * @param mixed $data Données à nettoyer
     * @return mixed Données nettoyées
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        
        if (is_string($data)) {
            // Convertir les caractères spéciaux en entités HTML
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $data;
    }

    /**
     * Générer un token CSRF
     *
     * @param string $formName Nom du formulaire (optionnel)
     * @return string Token généré
     */
    public function generateCsrfToken($formName = 'default')
    {
        $token = bin2hex(random_bytes(32));
        
        // Stocker le token dans la session
        $this->session->set('csrf_' . $formName, $token);
        
        return $token;
    }

    /**
     * Valider un token CSRF
     *
     * @param string $token Token à valider
     * @param string $formName Nom du formulaire (optionnel)
     * @return bool True si le token est valide
     */
    public function validateCsrfToken($token, $formName = 'default')
    {
        // Récupérer le token stocké dans la session
        $sessionToken = $this->session->get('csrf_' . $formName);
        
        // Si pas de token dans la session ou token invalide
        if (empty($sessionToken) || $token !== $sessionToken) {
            return false;
        }
        
        // Régénérer un nouveau token pour éviter les attaques par rejeu
        $this->generateCsrfToken($formName);
        
        return true;
    }

    /**
     * Créer un champ CSRF pour un formulaire HTML
     *
     * @param string $formName Nom du formulaire (optionnel)
     * @return string Champ HTML pour le token CSRF
     */
    public function csrfField($formName = 'default')
    {
        $token = $this->generateCsrfToken($formName);
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Hacher un mot de passe
     *
     * @param string $password Mot de passe en clair
     * @return string Mot de passe haché
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => 12
        ]);
    }

    /**
     * Vérifier un mot de passe
     *
     * @param string $password Mot de passe en clair
     * @param string $hash Hash du mot de passe à vérifier
     * @return bool True si le mot de passe est correct
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Générer une chaîne aléatoire
     *
     * @param int $length Longueur de la chaîne
     * @return string Chaîne aléatoire
     */
    public function generateRandomString($length = 32)
    {
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }

    /**
     * Ajouter des en-têtes de sécurité HTTP
     */
    public function addSecurityHeaders()
    {
        // Protection contre le clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Protection contre le MIME-sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Protection XSS pour les navigateurs modernes
        header('X-XSS-Protection: 1; mode=block');
        
        // Politique de sécurité du contenu (CSP)
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:;");
        
        // Référer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Vérifier si une requête utilise HTTPS
     *
     * @return bool
     */
    public function isSecureConnection()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
    }

    /**
     * Forcer une redirection vers HTTPS
     *
     * @return void
     */
    public function enforceHttps()
    {
        if (!$this->isSecureConnection()) {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirect);
            exit();
        }
    }
}