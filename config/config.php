<?php
return [
    'app' => [
        'name' => 'Mon Application MVC PHP',
        'env' => 'development',  // development, testing, production
        'debug' => true,         // true pour afficher les erreurs détaillées
        'secure_headers' => true,
        'force_https' => false,  // true pour forcer HTTPS en production
        'timezone' => 'Europe/Paris',
        'locale' => 'fr_FR',
        'charset' => 'UTF-8'
    ],
    
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'mvc_framework',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => ''
    ],
    
    'session' => [
        'name' => 'mvc_session',
        'lifetime' => 7200,        // Durée en secondes (2 heures)
        'secure' => false,         // true pour HTTPS uniquement
        'httponly' => true,
        'samesite' => 'Lax'        // None, Lax ou Strict
    ],
    
    'auth' => [
        'user_model' => '\\Models\\UserModel',
        'login_url' => '/login',
        'logout_url' => '/logout',
        'redirect_after_login' => '/',
        'redirect_after_logout' => '/',
        'unauthorized_url' => '/unauthorised'
    ],
    
    'middleware' => [
        'global' => [
            // Middleware appliqué à toutes les routes
        ],
        'groups' => [
            'web' => [
                // Middleware appliqué au groupe 'web'
                '\\Core\\Middleware\\StartSession'
            ],
            'api' => [
                // Middleware appliqué au groupe 'api'
            ],
            'auth' => [
                // Middleware d'authentification
                '\\Core\\Auth\\AuthMiddleware'
            ],
            'guest' => [
                // Middleware pour les invités (non connectés)
            ]
        ],
        'route' => [
            'auth' => '\\Core\\Auth\\AuthMiddleware',
            'role' => '\\Core\\Auth\\RoleMiddleware',
            'permission' => '\\Core\\Auth\\PermissionMiddleware',
        ]
    ],
    
    'view' => [
        'path' => ROOT_PATH . '/src/Views',
        'cache' => ROOT_PATH . '/var/cache/views',
        'extension' => '.php'
    ],
    
    'routes' => [
        [
            'path' => '/',
            'controller' => 'HomeController',
            'action' => 'index',
            'method' => 'GET',
            'middleware' => ['web']
        ],
        [
            'path' => '/about',
            'controller' => 'HomeController',
            'action' => 'about',
            'method' => 'GET',
            'middleware' => ['web']
        ],
        [
            'path' => '/login',
            'controller' => 'AuthController',
            'action' => 'showLogin',
            'method' => 'GET',
            'middleware' => ['web', 'guest']
        ],
        [
            'path' => '/login',
            'controller' => 'AuthController',
            'action' => 'login',
            'method' => 'POST',
            'middleware' => ['web', 'guest']
        ],
        [
            'path' => '/logout',
            'controller' => 'AuthController',
            'action' => 'logout',
            'method' => 'GET',
            'middleware' => ['web', 'auth']
        ],
        [
            'path' => '/dashboard',
            'controller' => 'DashboardController',
            'action' => 'index',
            'method' => 'GET',
            'middleware' => ['web', 'auth']
        ],
        [
            'path' => '/admin',
            'controller' => 'AdminController',
            'action' => 'index',
            'method' => 'GET',
            'middleware' => ['web', 'auth', 'role:admin']
        ],
        [
            'path' => '/users',
            'controller' => 'UserController',
            'action' => 'index',
            'method' => 'GET',
            'middleware' => ['web', 'auth', 'permission:view_users']
        ],
        [
            'path' => '/users/create',
            'controller' => 'UserController',
            'action' => 'create',
            'method' => 'GET',
            'middleware' => ['web', 'auth', 'permission:create_users']
        ],
        [
            'path' => '/users',
            'controller' => 'UserController',
            'action' => 'store',
            'method' => 'POST',
            'middleware' => ['web', 'auth', 'permission:create_users']
        ],
        [
            'path' => '/users/{id}',
            'controller' => 'UserController',
            'action' => 'show',
            'method' => 'GET',
            'middleware' => ['web', 'auth', 'permission:view_users']
        ]
    ]
];