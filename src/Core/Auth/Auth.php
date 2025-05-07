<?php
namespace Core\Auth;

use Core\Session;
use Core\Security;

class Auth
{
    /**
     * Instance de Session
     * @var Session
     */
    protected $session;
    
    /**
     * Instance de Security
     * @var Security
     */
    protected $security;
    
    /**
     * Utilisateur actuellement connecté
     * @var object|null
     */
    protected $user;
    
    /**
     * Classe du modèle utilisateur
     * @var string
     */
    protected $userModel;
    
    /**
     * Nom de la clé de session pour l'utilisateur
     * @var string
     */
    protected $sessionKey = 'auth_user';
    
    /**
     * Constructeur
     *
     * @param Session $session Instance de Session
     * @param Security $security Instance de Security
     * @param string $userModel Classe du modèle utilisateur
     */
    public function __construct(Session $session, Security $security, $userModel = '\\Models\\UserModel')
    {
        $this->session = $session;
        $this->security = $security;
        $this->userModel = $userModel;
        
        // Charger l'utilisateur depuis la session s'il existe
        $this->loadUserFromSession();
    }
    
    /**
     * Charger l'utilisateur depuis la session
     */
    protected function loadUserFromSession()
    {
        $userId = $this->session->get($this->sessionKey);
        
        if ($userId) {
            // Instancier le modèle utilisateur
            $userModel = new $this->userModel();
            
            // Charger l'utilisateur par son ID
            $this->user = $userModel->findById($userId);
            
            // Si l'utilisateur n'existe pas, le supprimer de la session
            if (!$this->user) {
                $this->session->remove($this->sessionKey);
            }
        }
    }
    
    /**
     * Tenter de connecter un utilisateur
     *
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe en clair
     * @param bool $remember Si true, définir un cookie de "se souvenir de moi"
     * @return bool True si la connexion est réussie
     */
    public function attempt($email, $password, $remember = false)
    {
        // Instancier le modèle utilisateur
        $userModel = new $this->userModel();
        
        // Trouver l'utilisateur par son email
        $user = $userModel->findByEmail($email);
        
        // Vérifier si l'utilisateur existe et si le mot de passe est correct
        if ($user && $this->security->verifyPassword($password, $user->password)) {
            // Connecter l'utilisateur
            $this->login($user);
            
            // Gérer "Se souvenir de moi"
            if ($remember) {
                $this->rememberUser($user);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Connecter un utilisateur
     *
     * @param object $user Utilisateur à connecter
     * @return void
     */
    public function login($user)
    {
        // Régénérer l'ID de session pour prévenir la fixation de session
        $this->session->regenerateId();
        
        // Stocker l'ID de l'utilisateur dans la session
        $this->session->set($this->sessionKey, $user->id);
        
        // Définir l'utilisateur courant
        $this->user = $user;
    }
    
    /**
     * Déconnecter l'utilisateur
     *
     * @return void
     */
    public function logout()
    {
        // Supprimer l'utilisateur de la session
        $this->session->remove($this->sessionKey);
        
        // Supprimer le cookie "Se souvenir de moi"
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Régénérer l'ID de session
        $this->session->regenerateId();
        
        // Réinitialiser l'utilisateur courant
        $this->user = null;
    }
    
    /**
     * Vérifier si un utilisateur est connecté
     *
     * @return bool
     */
    public function check()
    {
        return $this->user !== null;
    }
    
    /**
     * Vérifier si un utilisateur est un invité (non connecté)
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }
    
    /**
     * Récupérer l'utilisateur connecté
     *
     * @return object|null
     */
    public function user()
    {
        return $this->user;
    }
    
    /**
     * Récupérer l'ID de l'utilisateur connecté
     *
     * @return int|null
     */
    public function id()
    {
        return $this->user ? $this->user->id : null;
    }
    
    /**
     * Se souvenir de l'utilisateur avec un cookie
     *
     * @param object $user L'utilisateur
     */
    protected function rememberUser($user)
    {
        // Générer un token aléatoire
        $token = $this->security->generateRandomString(64);
        
        // Hacher le token pour le stockage en base de données
        $hashedToken = $this->security->hashPassword($token);
        
        // Stocker le token haché et l'utilisateur en base de données (remember_tokens table)
        $userModel = new $this->userModel();
        $userModel->storeRememberToken($user->id, $hashedToken);
        
        // Définir le cookie avec le format "ID|TOKEN"
        $cookieValue = $user->id . '|' . $token;
        
        // Définir le cookie pour 30 jours
        setcookie('remember_token', $cookieValue, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     *
     * @param string|array $roles Rôle(s) à vérifier
     * @return bool
     */
    public function hasRole($roles)
    {
        if (!$this->check()) {
            return false;
        }
        
        // Si aucun rôle n'est spécifié pour l'utilisateur
        if (!isset($this->user->roles)) {
            return false;
        }
        
        // Convertir en tableau si c'est une chaîne
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        // Convertir les rôles de l'utilisateur en tableau si c'est une chaîne
        $userRoles = is_array($this->user->roles) ? $this->user->roles : explode(',', $this->user->roles);
        
        // Vérifier l'intersection des rôles
        return count(array_intersect($userRoles, $roles)) > 0;
    }
    
    /**
     * Vérifier si l'utilisateur a une permission spécifique
     *
     * @param string|array $permissions Permission(s) à vérifier
     * @return bool
     */
    public function can($permissions)
    {
        if (!$this->check()) {
            return false;
        }
        
        // Si aucune permission n'est spécifiée pour l'utilisateur
        if (!isset($this->user->permissions)) {
            return false;
        }
        
        // Convertir en tableau si c'est une chaîne
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }
        
        // Convertir les permissions de l'utilisateur en tableau si c'est une chaîne
        $userPermissions = is_array($this->user->permissions) 
            ? $this->user->permissions 
            : explode(',', $this->user->permissions);
        
        // Vérifier l'intersection des permissions
        return count(array_intersect($userPermissions, $permissions)) > 0;
    }
}