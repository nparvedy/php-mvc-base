<?php
namespace Core;

use Core\Middleware\MiddlewareManager;
use Core\Auth\Auth;
use Core\Migration\MigrationManager;
use Core\Event\EventDispatcher;

class Application
{
    /**
     * Instance du conteneur d'injection de dépendances
     * @var Container
     */
    protected $container;

    /**
     * Constructeur
     *
     * @param array $config Configuration de l'application
     */
    public function __construct(array $config)
    {
        // Initialiser le conteneur d'injection de dépendances
        $this->container = new Container();
        
        // Enregistrer la configuration dans le conteneur
        $this->container->instance('config', $config);
        
        // Définir les services de base en tant que singletons
        $this->registerBaseServices();
        
        // Configurer les services en fonction de la configuration
        $this->configureServices();
    }

    /**
     * Exécuter l'application
     * 
     * @return mixed
     */
    public function run()
    {
        try {
            // Récupérer le router et charger les routes depuis la configuration
            $router = $this->container->make('router');
            $config = $this->container->make('config');
            
            // Charger les routes depuis la configuration
            if (isset($config['routes'])) {
                foreach ($config['routes'] as $route) {
                    $router->add(
                        $route['path'], 
                        $route['controller'], 
                        $route['action'], 
                        $route['method'] ?? 'GET',
                        $route['middleware'] ?? []
                    );
                }
            }

            // Dispatcher la requête à travers le gestionnaire de middleware
            $middlewareManager = $this->container->make('middleware');
            $request = $this->container->make('request');
            $response = $this->container->make('response');
            
            // Obtenir le contrôleur et l'action du router
            list($controller, $action, $params, $middleware) = $router->resolve();
            
            // Middleware spécifique à la route
            $routeMiddleware = $middlewareManager->resolveMiddleware($middleware);
            
            // Créer la fonction finale (exécution du contrôleur)
            $target = function ($request, $response) use ($controller, $action, $params) {
                // Créer une instance du contrôleur avec le conteneur
                $controllerInstance = $this->container->make($controller);
                
                // Exécuter l'action du contrôleur avec les paramètres
                return call_user_func_array([$controllerInstance, $action], $params);
            };
            
            // Exécuter les middleware avec la cible finale
            return $middlewareManager->run($request, $response, $routeMiddleware, $target);
        } catch (\Exception $e) {
            // Les erreurs seront interceptées par le gestionnaire d'erreurs
            throw $e;
        }
    }
    
    /**
     * Enregistrer les services de base
     */
    protected function registerBaseServices()
    {
        // Services de base
        $this->container->singleton('request', Request::class);
        $this->container->singleton('response', Response::class);
        $this->container->singleton('session', Session::class);
        $this->container->singleton('security', Security::class);
        
        // Router
        $this->container->singleton('router', function ($container) {
            return new Router(
                $container->make('request'),
                $container->make('response')
            );
        });
        
        // Gestionnaire de middleware
        $this->container->singleton('middleware', function ($container) {
            return new MiddlewareManager($container);
        });
        
        // Moteur de templates
        $this->container->singleton('view', function ($container) {
            $config = $container->make('config');
            $debug = ($config['app']['env'] ?? 'production') === 'development';
            
            return new TemplateEngine(
                ROOT_PATH . '/src/Views',
                ROOT_PATH . '/var/cache/views',
                $debug
            );
        });
        
        // Base de données
        $this->container->singleton('db', function ($container) {
            $config = $container->make('config');
            return Database::getInstance($config['database'] ?? []);
        });
        
        // Gestionnaire d'événements
        $this->container->singleton('events', function ($container) {
            return new EventDispatcher($container);
        });
        
        // Gestionnaire d'erreurs
        $this->container->singleton('errorHandler', function ($container) {
            $config = $container->make('config');
            $debug = $config['app']['debug'] ?? true;
            
            $errorHandler = new ErrorHandler($debug);
            $errorHandler->register();
            
            return $errorHandler;
        });
        
        // Authentification
        $this->container->singleton('auth', function ($container) {
            return new Auth(
                $container->make('session'),
                $container->make('security'),
                '\Models\UserModel'
            );
        });
        
        // Gestionnaire de migrations
        $this->container->singleton('migration', function ($container) {
            return new MigrationManager(ROOT_PATH . '/src/Migrations');
        });
    }
    
    /**
     * Configurer les services en fonction de la configuration
     */
    protected function configureServices()
    {
        $config = $this->container->make('config');
        
        // Configurer le gestionnaire de middleware
        $middlewareManager = $this->container->make('middleware');
        
        // Ajouter les middleware globaux
        if (isset($config['middleware']['global']) && is_array($config['middleware']['global'])) {
            foreach ($config['middleware']['global'] as $middleware) {
                $middlewareManager->add($middleware);
            }
        }
        
        // Ajouter les groupes de middleware
        if (isset($config['middleware']['groups']) && is_array($config['middleware']['groups'])) {
            foreach ($config['middleware']['groups'] as $name => $middleware) {
                $middlewareManager->addGroup($name, $middleware);
            }
        }
        
        // Ajouter les middleware de routes
        if (isset($config['middleware']['route']) && is_array($config['middleware']['route'])) {
            foreach ($config['middleware']['route'] as $name => $middleware) {
                $middlewareManager->addRoute($name, $middleware);
            }
        }
        
        // Configurer la sécurité
        $security = $this->container->make('security');
        
        // Ajouter les en-têtes de sécurité si configuré
        if ($config['app']['secure_headers'] ?? true) {
            $security->addSecurityHeaders();
        }
        
        // Forcer HTTPS en production si configuré
        if (($config['app']['env'] ?? 'development') === 'production' && 
            ($config['app']['force_https'] ?? false)) {
            $security->enforceHttps();
        }
    }
    
    /**
     * Récupérer un service du conteneur
     *
     * @param string $name Nom du service
     * @return mixed Instance du service
     */
    public function get($name)
    {
        return $this->container->make($name);
    }
    
    /**
     * Récupérer le conteneur d'injection de dépendances
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}