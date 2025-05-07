<?php
namespace Core;

class Application
{
    private $router;
    private $request;
    private $response;
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
    }

    public function run()
    {
        try {
            // Charger les routes depuis la configuration
            if (isset($this->config['routes'])) {
                foreach ($this->config['routes'] as $route) {
                    $this->router->add($route['path'], $route['controller'], $route['action'], $route['method'] ?? 'GET');
                }
            }

            // Dispatcher la requête
            $this->router->dispatch();
        } catch (\Exception $e) {
            // Gérer les erreurs
            $this->response->setStatusCode(500);
            echo 'Erreur: ' . $e->getMessage();
        }
    }
}