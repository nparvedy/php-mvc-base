<?php
namespace Core\Middleware;

use Core\Request;
use Core\Response;

interface MiddlewareInterface
{
    /**
     * Traiter la requête entrante
     *
     * @param Request $request La requête HTTP
     * @param Response $response La réponse HTTP
     * @param callable $next Fonction pour passer au middleware suivant
     * @return mixed
     */
    public function handle(Request $request, Response $response, callable $next);
}