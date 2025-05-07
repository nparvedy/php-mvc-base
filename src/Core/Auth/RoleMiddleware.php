<?php
namespace Core\Auth;

use Core\Request;
use Core\Response;
use Core\Middleware\MiddlewareInterface;
use Core\Session;

class RoleMiddleware implements MiddlewareInterface
{
    /**
     * Instance de Auth
     * @var Auth
     */
    protected $auth;
    
    /**
     * Instance de Session
     * @var Session
     */
    protected $session;
    
    /**
     * Rôles autorisés
     * @var array
     */
    protected $roles;
    
    /**
     * URL de redirection en cas d'accès refusé
     * @var string
     */
    protected $redirectUrl;

    /**
     * Constructeur
     *
     * @param Auth $auth Instance de Auth
     * @param Session $session Instance de Session
     * @param string|array $roles Rôles autorisés
     * @param string $redirectUrl URL de redirection en cas d'accès refusé
     */
    public function __construct(Auth $auth, Session $session, $roles, $redirectUrl = '/unauthorised')
    {
        $this->auth = $auth;
        $this->session = $session;
        $this->roles = is_array($roles) ? $roles : [$roles];
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Traiter la requête entrante
     *
     * @param Request $request La requête HTTP
     * @param Response $response La réponse HTTP
     * @param callable $next Fonction pour passer au middleware suivant
     * @return mixed
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        // Vérifier si l'utilisateur est connecté et a un des rôles requis
        if ($this->auth->check() && $this->auth->hasRole($this->roles)) {
            // L'utilisateur est autorisé, passer au middleware suivant
            return $next($request, $response);
        } else {
            // Définir un message flash pour informer l'utilisateur
            $this->session->flash('error', 'Vous n\'êtes pas autorisé à accéder à cette page');
            
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            if (!$this->auth->check()) {
                return $response->redirect('/login');
            }
            
            // Rediriger vers la page d'accès refusé
            return $response->redirect($this->redirectUrl);
        }
    }
}