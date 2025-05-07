<?php
namespace Core\Auth;

use Core\Request;
use Core\Response;
use Core\Middleware\MiddlewareInterface;
use Core\Session;

class PermissionMiddleware implements MiddlewareInterface
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
     * Permissions requises
     * @var array
     */
    protected $permissions;
    
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
     * @param string|array $permissions Permissions requises
     * @param string $redirectUrl URL de redirection en cas d'accès refusé
     */
    public function __construct(Auth $auth, Session $session, $permissions, $redirectUrl = '/unauthorised')
    {
        $this->auth = $auth;
        $this->session = $session;
        $this->permissions = is_array($permissions) ? $permissions : [$permissions];
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
        // Vérifier si l'utilisateur est connecté et a les permissions requises
        if ($this->auth->check() && $this->auth->can($this->permissions)) {
            // L'utilisateur est autorisé, passer au middleware suivant
            return $next($request, $response);
        } else {
            // Définir un message flash pour informer l'utilisateur
            $this->session->flash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page');
            
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            if (!$this->auth->check()) {
                return $response->redirect('/login');
            }
            
            // Rediriger vers la page d'accès refusé
            return $response->redirect($this->redirectUrl);
        }
    }
}