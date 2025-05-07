<?php
namespace Core\Auth;

use Core\Request;
use Core\Response;
use Core\Middleware\MiddlewareInterface;
use Core\Session;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Instance de Auth
     * @var Auth
     */
    protected $auth;
    
    /**
     * URL de redirection en cas d'échec d'authentification
     * @var string
     */
    protected $redirectUrl;
    
    /**
     * Instance de Session
     * @var Session
     */
    protected $session;

    /**
     * Constructeur
     *
     * @param Auth $auth Instance de Auth
     * @param Session $session Instance de Session
     * @param string $redirectUrl URL de redirection en cas d'échec
     */
    public function __construct(Auth $auth, Session $session, $redirectUrl = '/login')
    {
        $this->auth = $auth;
        $this->session = $session;
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
        // Vérifier si l'utilisateur est connecté
        if ($this->auth->check()) {
            // L'utilisateur est connecté, passer au middleware suivant
            return $next($request, $response);
        } else {
            // Stocker l'URL actuelle pour rediriger après connexion
            $this->session->set('redirect_after_login', $request->getUri());
            
            // Définir un message flash pour informer l'utilisateur
            $this->session->flash('error', 'Veuillez vous connecter pour accéder à cette page');
            
            // Rediriger vers la page de connexion
            return $response->redirect($this->redirectUrl);
        }
    }
}