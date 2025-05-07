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

    public function add($path, $controller, $action, $method = 'GET')
    {
        $this->routes[] = [
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'method' => $method
        ];
    }

    public function dispatch()
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
                
                // Instancier le contrôleur
                $controllerName = "Controllers\\" . $route['controller'];
                $controller = new $controllerName($this->request, $this->response);
                
                // Appeler l'action avec les paramètres extraits
                call_user_func_array([$controller, $route['action']], $matches);
                return;
            }
        }
        
        // Si aucune route ne correspond, retourner une erreur 404
        $this->response->setStatusCode(404);
        echo '404 - Page non trouvée';
    }

    private function convertToRegex($path)
    {
        // Convertir les paramètres {param} en expression régulière
        $pattern = preg_replace('/{([a-z]+)}/', '([^/]+)', $path);
        
        // Ajouter les délimiteurs et les ancres
        return '#^' . $pattern . '$#i';
    }
}