<?php
namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Session;

/**
 * Middleware pour démarrer la session à chaque requête
 */
class StartSession implements MiddlewareInterface
{
    /**
     * Instance de Session
     * @var Session
     */
    protected $session;

    /**
     * Constructeur
     *
     * @param Session $session Instance de Session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
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
        // Démarrer la session si ce n'est pas déjà fait
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
        
        // Passer au middleware suivant ou au contrôleur
        $result = $next($request, $response);
        
        // Enregistrer la session après le traitement de la requête
        $this->session->save();
        
        return $result;
    }
}