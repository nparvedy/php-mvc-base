<?php
namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Container;

class MiddlewareManager
{
    /**
     * Liste des middleware globaux
     * @var array
     */
    private $middleware = [];
    
    /**
     * Groupes de middleware
     * @var array
     */
    private $middlewareGroups = [];
    
    /**
     * Middleware associés à des routes
     * @var array
     */
    private $routeMiddleware = [];
    
    /**
     * Conteneur d'injection de dépendances
     * @var Container
     */
    private $container;
    
    /**
     * Constructeur
     *
     * @param Container $container Conteneur d'injection de dépendances
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Ajouter un middleware global
     *
     * @param string|callable $middleware Nom de classe du middleware ou callable
     * @return $this
     */
    public function add($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Ajouter un groupe de middleware
     *
     * @param string $name Nom du groupe
     * @param array $middleware Liste des middleware
     * @return $this
     */
    public function addGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;
        return $this;
    }
    
    /**
     * Ajouter un middleware pour une route spécifique
     *
     * @param string $name Nom du middleware
     * @param string|callable $middleware Nom de classe du middleware ou callable
     * @return $this
     */
    public function addRoute($name, $middleware)
    {
        $this->routeMiddleware[$name] = $middleware;
        return $this;
    }
    
    /**
     * Résoudre les noms de middleware en instances
     *
     * @param string|array|callable $middleware Middleware à résoudre
     * @return array Liste des middleware résolus
     */
    public function resolveMiddleware($middleware)
    {
        $resolvedMiddleware = [];
        
        if (is_string($middleware)) {
            // Si c'est un groupe de middleware
            if (isset($this->middlewareGroups[$middleware])) {
                foreach ($this->middlewareGroups[$middleware] as $item) {
                    $resolvedMiddleware = array_merge($resolvedMiddleware, $this->resolveMiddleware($item));
                }
            } 
            // Si c'est un middleware nommé pour des routes
            else if (isset($this->routeMiddleware[$middleware])) {
                $resolvedMiddleware[] = $this->routeMiddleware[$middleware];
            } 
            // Si c'est un nom de classe
            else {
                $resolvedMiddleware[] = $middleware;
            }
        } 
        // Si c'est un tableau de middleware
        else if (is_array($middleware)) {
            foreach ($middleware as $item) {
                $resolvedMiddleware = array_merge($resolvedMiddleware, $this->resolveMiddleware($item));
            }
        } 
        // Si c'est une fonction callable
        else if (is_callable($middleware)) {
            $resolvedMiddleware[] = $middleware;
        }
        
        return $resolvedMiddleware;
    }
    
    /**
     * Exécuter une pile de middleware
     *
     * @param Request $request Requête HTTP
     * @param Response $response Réponse HTTP
     * @param array $middleware Liste des middleware à exécuter
     * @param callable $target Fonction finale à exécuter après les middleware
     * @return mixed
     */
    public function run(Request $request, Response $response, array $middleware = [], callable $target = null)
    {
        // Ajouter les middleware globaux au début
        $middleware = array_merge($this->resolveMiddleware($this->middleware), $middleware);
        
        // Transformer la pile de middleware en une fonction imbriquée
        $runner = $this->createRunner($middleware, $target);
        
        // Exécuter la pile de middleware
        return $runner($request, $response);
    }
    
    /**
     * Créer une fonction qui exécute la pile de middleware
     *
     * @param array $middleware Liste des middleware à exécuter
     * @param callable $target Fonction finale à exécuter
     * @return callable
     */
    private function createRunner(array $middleware, callable $target = null)
    {
        // Fonction par défaut en cas d'absence de cible
        $target = $target ?: function (Request $request, Response $response) {
            return $response;
        };
        
        // Parcourir la pile de middleware en commençant par la fin
        $runner = array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) {
                return function (Request $request, Response $response) use ($middleware, $next) {
                    // Résoudre le middleware si c'est une chaîne de caractères (nom de classe)
                    if (is_string($middleware)) {
                        // Utiliser le conteneur pour créer l'instance du middleware
                        $middleware = $this->container->make($middleware);
                    }
                    
                    // Si c'est un objet implémentant l'interface MiddlewareInterface
                    if ($middleware instanceof MiddlewareInterface) {
                        return $middleware->handle($request, $response, $next);
                    }
                    
                    // Si c'est une fonction callable
                    if (is_callable($middleware)) {
                        return $middleware($request, $response, $next);
                    }
                    
                    // Si le middleware n'est pas reconnu, passer au suivant
                    return $next($request, $response);
                };
            },
            $target
        );
        
        return $runner;
    }
}