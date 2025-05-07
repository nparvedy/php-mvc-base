<?php
namespace Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurer les options de sécurité pour les cookies de session
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
    }

    /**
     * Définir une valeur dans la session
     *
     * @param string $key Clé de la variable de session
     * @param mixed $value Valeur à stocker
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Récupérer une valeur de la session
     *
     * @param string $key Clé de la variable de session
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vérifier si une clé existe dans la session
     *
     * @param string $key Clé à vérifier
     * @return bool
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Supprimer une variable de la session
     *
     * @param string $key Clé à supprimer
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Détruire complètement la session
     */
    public function destroy()
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Régénérer l'ID de session (utile après connexion/déconnexion)
     *
     * @return bool
     */
    public function regenerateId()
    {
        return session_regenerate_id(true);
    }

    /**
     * Définir ou récupérer un message flash
     * Les messages flash sont affichés une seule fois puis supprimés
     *
     * @param string $key Clé du message flash
     * @param mixed $value Valeur du message (si null, récupère le message)
     * @return mixed|null
     */
    public function flash($key, $value = null)
    {
        if ($value !== null) {
            // Définir le message flash
            if (!isset($_SESSION['flash'])) {
                $_SESSION['flash'] = [];
            }
            $_SESSION['flash'][$key] = $value;
            return null;
        } else {
            // Récupérer et supprimer le message flash
            $value = $_SESSION['flash'][$key] ?? null;
            if (isset($_SESSION['flash'][$key])) {
                unset($_SESSION['flash'][$key]);
                if (empty($_SESSION['flash'])) {
                    unset($_SESSION['flash']);
                }
            }
            return $value;
        }
    }

    /**
     * Vérifier si un message flash existe
     *
     * @param string $key Clé du message flash
     * @return bool
     */
    public function hasFlash($key)
    {
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Récupérer tous les messages flash sans les supprimer
     *
     * @return array
     */
    public function getFlashes()
    {
        return $_SESSION['flash'] ?? [];
    }

    /**
     * Récupérer et supprimer tous les messages flash
     *
     * @return array
     */
    public function getAllFlashes()
    {
        $flashes = $_SESSION['flash'] ?? [];
        if (isset($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }
        return $flashes;
    }
}