<?php
return [
    'app' => [
        'name' => 'Mon Application MVC PHP',
        'env' => 'development', // development, testing, production
        'debug' => true,        // true pour afficher les erreurs détaillées, false pour la production
        'secure_headers' => true,
        'force_https' => false, // true pour forcer HTTPS en production
        'timezone' => 'Europe/Paris',
        'locale' => 'fr_FR',
        'charset' => 'UTF-8'
    ],
    
    'routes' => [
        [
            'path' => '/',
            'controller' => 'HomeController',
            'action' => 'index',
            'method' => 'GET'
        ],
        [
            'path' => '/about',
            'controller' => 'HomeController',
            'action' => 'about',
            'method' => 'GET'
        ],
        [
            'path' => '/users',
            'controller' => 'UserController',
            'action' => 'index',
            'method' => 'GET'
        ],
        [
            'path' => '/users/{id}',
            'controller' => 'UserController',
            'action' => 'show',
            'method' => 'GET'
        ],
        [
            'path' => '/users/create',
            'controller' => 'UserController',
            'action' => 'create',
            'method' => 'GET'
        ],
        [
            'path' => '/users',
            'controller' => 'UserController',
            'action' => 'store',
            'method' => 'POST'
        ]
    ],
    
    'session' => [
        'name' => 'mvc_session',
        'lifetime' => 7200, // Durée en secondes
        'secure' => false,  // true pour HTTPS uniquement
        'httponly' => true,
        'samesite' => 'Lax' // None, Lax ou Strict
    ]
];