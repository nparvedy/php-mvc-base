<?php
return [
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
    ]
];