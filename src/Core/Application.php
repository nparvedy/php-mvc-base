<?php
namespace Core;

class Application
{
    private $router;
    private $request;
    private $response;
    private $config;
    private $session;
    private $security;
    private $errorHandler;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->session = new Session();
        $this->security = new Security();
        
        // Initialiser le gestionnaire d'erreurs
        $debug = $config['app']['debug'] ?? true;
        $this->errorHandler = new ErrorHandler($debug);
        $this->errorHandler->register();
        
        // Ajouter les en-têtes de sécurité
        if ($config['app']['secure_headers'] ?? true) {
            $this->security->addSecurityHeaders();
        }
        
        // Forcer HTTPS en production
        if (($config['app']['env'] ?? 'development') === 'production' && 
            ($config['app']['force_https'] ?? false)) {
            $this->security->enforceHttps();
        }
    }

    public function run()
    {
        try {
            // Charger les routes depuis la configuration
            if (isset($this->config['routes'])) {
                foreach ($this->config['routes'] as $route) {
                    $this->router->add(
                        $route['path'], 
                        $route['controller'], 
                        $route['action'], 
                        $route['method'] ?? 'GET'
                    );
                }
            }

            // Dispatcher la requête
            $this->router->dispatch();
        } catch (\Exception $e) {
            // Les exceptions seront interceptées par le gestionnaire d'erreurs
            throw $e;
        }
    }
    
    /**
     * Récupérer un service de l'application
     *
     * @param string $name Nom du service
     * @return mixed Instance du service
     */
    public function get($name)
    {
        switch ($name) {
            case 'request':
                return $this->request;
            case 'response':
                return $this->response;
            case 'router':
                return $this->router;
            case 'session':
                return $this->session;
            case 'security':
                return $this->security;
            case 'config':
                return $this->config;
            default:
                throw new \InvalidArgumentException("Service '{$name}' non trouvé.");
        }
    }
}