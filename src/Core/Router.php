<?php
namespace Core;

class Router
{
    private $routes = [];
    private $request;
    private $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function add($path, $controller, $action, $method = 'GET', $middleware = [])
    {
        $this->routes[] = [
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'method' => $method,
            'middleware' => $middleware
        ];
    }

    /**
     * Résoudre la route correspondante et retourner les informations nécessaires
     * 
     * @return array [controller, action, params, middleware]
     * @throws \Exception si aucune route ne correspond
     */
    public function resolve()
    {
        $uri = $this->request->getUri();
        $method = $this->request->getMethod();
        
        foreach ($this->routes as $route) {
            // Vérifier si la méthode correspond
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Convertir le chemin en expression régulière
            $pattern = $this->convertToRegex($route['path']);
            
            // Vérifier si l'URI correspond au modèle
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Supprimer la première correspondance (l'URI complète)
                
                // Préparer les informations de la route
                $controller = "Controllers\\" . $route['controller'];
                $action = $route['action'];
                $middleware = $route['middleware'] ?? [];
                
                // Retourner les informations nécessaires pour traiter la route
                return [$controller, $action, $matches, $middleware];
            }
        }
        
        // Si aucune route ne correspond, lancer une exception
        throw new \Exception('Route non trouvée: ' . $uri, 404);
    }

    public function dispatch()
    {
        try {
            list($controller, $action, $params, $middleware) = $this->resolve();
            
            // Instancier le contrôleur
            $controllerInstance = new $controller($this->request, $this->response);
            
            // Appeler l'action avec les paramètres extraits
            call_user_func_array([$controllerInstance, $action], $params);
        } catch (\Exception $e) {
            // Si l'erreur est 404, afficher une page 404
            if ($e->getCode() === 404) {
                $this->response->setStatusCode(404);
                echo '404 - Page non trouvée';
            } else {
                // Sinon, propager l'exception
                throw $e;
            }
        }
    }

    private function convertToRegex($path)
    {
        // Convertir les paramètres {param} en expression régulière
        $pattern = preg_replace('/{([a-z]+)}/', '([^/]+)', $path);
        
        // Ajouter les délimiteurs et les ancres
        return '#^' . $pattern . '$#i';
    }
}