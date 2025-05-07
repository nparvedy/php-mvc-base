# Tutoriel de création d'une application MVC PHP

Ce guide détaille la création d'une application MVC PHP personnalisée, sans utiliser de frameworks ou plugins externes.

## Table des matières
1. [Structure du projet](#structure-du-projet)
2. [Configuration initiale](#configuration-initiale)
3. [Création du noyau de l'application](#création-du-noyau-de-lapplication)
4. [Définition des contrôleurs](#définition-des-contrôleurs)
5. [Création des modèles](#création-des-modèles)
6. [Mise en place des vues](#mise-en-place-des-vues)
7. [Gestion des routes](#gestion-des-routes)
8. [Configuration de la base de données](#configuration-de-la-base-de-données)
9. [Sécurité et validation](#sécurité-et-validation)
10. [Fonctionnalités additionnelles](#fonctionnalités-additionnelles)

## Structure du projet

```
mvc-php/
│
├── config/                  # Fichiers de configuration
│   ├── config.php           # Configuration principale
│   └── database.php         # Configuration de la base de données
│
├── public/                  # Point d'entrée public
│   ├── index.php            # Fichier d'entrée principal
│   ├── .htaccess            # Configuration Apache
│   ├── css/                 # Fichiers CSS
│   ├── js/                  # Fichiers JavaScript
│   └── img/                 # Images
│
├── src/                     # Code source de l'application
│   ├── Controllers/         # Contrôleurs de l'application
│   ├── Models/              # Modèles de données
│   ├── Views/               # Vues et templates
│   ├── Core/                # Noyau du framework
│   │   ├── Application.php  # Classe principale de l'application
│   │   ├── Controller.php   # Classe de base des contrôleurs
│   │   ├── Database.php     # Classe de connexion à la base de données
│   │   ├── Model.php        # Classe de base des modèles
│   │   ├── Router.php       # Gestionnaire de routes
│   │   ├── Request.php      # Gestion des requêtes
│   │   ├── Response.php     # Gestion des réponses
│   │   └── View.php         # Gestionnaire de vues
│   │
│   └── Services/            # Services de l'application
│
└── var/                     # Fichiers variables
    ├── cache/               # Cache de l'application
    └── logs/                # Logs de l'application
```

## Configuration initiale

### Étape 1 : Créer la structure de dossiers

Créez tous les dossiers selon la structure indiquée ci-dessus.

### Étape 2 : Configuration du serveur web (Apache)

Dans `public/.htaccess` :

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

### Étape 3 : Créer le point d'entrée

Dans `public/index.php` :

```php
<?php
// Définir le chemin racine de l'application
define('ROOT_PATH', dirname(__DIR__));

// Charger l'autoloader
require_once ROOT_PATH . '/src/Core/Autoloader.php';

// Initialiser l'autoloader
\Core\Autoloader::register();

// Charger la configuration
$config = require_once ROOT_PATH . '/config/config.php';

// Initialiser l'application
$app = new \Core\Application($config);

// Démarrer l'application
$app->run();
```

## Création du noyau de l'application

### Étape 4 : Créer l'autoloader

Dans `src/Core/Autoloader.php` :

```php
<?php
namespace Core;

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            $file = ROOT_PATH . '/src/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            return false;
        });
    }
}
```

### Étape 5 : Créer la classe Application

Dans `src/Core/Application.php` :

```php
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
```

### Étape 6 : Créer les classes de base

Créez les classes suivantes :

- `src/Core/Request.php` - Gestion des requêtes HTTP
- `src/Core/Response.php` - Gestion des réponses HTTP
- `src/Core/Router.php` - Routage des requêtes
- `src/Core/Controller.php` - Classe de base pour les contrôleurs
- `src/Core/Model.php` - Classe de base pour les modèles
- `src/Core/View.php` - Gestion des vues et templates
- `src/Core/Database.php` - Connexion et requêtes à la base de données

## Définition des contrôleurs

### Étape 7 : Créer un contrôleur de base

Dans `src/Core/Controller.php` :

```php
<?php
namespace Core;

abstract class Controller
{
    protected $view;
    protected $model;
    protected $request;
    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->view = new View();
    }

    protected function render(string $template, array $data = [])
    {
        return $this->view->render($template, $data);
    }

    protected function redirect(string $url)
    {
        $this->response->redirect($url);
    }

    protected function json(array $data)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
    }
}
```

### Étape 8 : Créer un exemple de contrôleur

Dans `src/Controllers/HomeController.php` :

```php
<?php
namespace Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return $this->render('home/index', [
            'title' => 'Accueil',
            'content' => 'Bienvenue sur notre application MVC PHP'
        ]);
    }

    public function about()
    {
        return $this->render('home/about', [
            'title' => 'À propos',
            'content' => 'Informations sur notre application'
        ]);
    }
}
```

## Création des modèles

### Étape 9 : Créer un modèle de base

Dans `src/Core/Model.php` :

```php
<?php
namespace Core;

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll()
    {
        $query = "SELECT * FROM {$this->table}";
        return $this->db->query($query);
    }

    public function findById($id)
    {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->query($query, ['id' => $id], true);
    }

    public function create(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        return $this->db->execute($query, $data);
    }

    public function update($id, array $data)
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $query = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        
        $data['id'] = $id;
        
        return $this->db->execute($query, $data);
    }

    public function delete($id)
    {
        $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        return $this->db->execute($query, ['id' => $id]);
    }
}
```

### Étape 10 : Exemple de modèle spécifique

Dans `src/Models/UserModel.php` :

```php
<?php
namespace Models;

use Core\Model;

class UserModel extends Model
{
    protected $table = 'users';

    public function findByEmail($email)
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email";
        return $this->db->query($query, ['email' => $email], true);
    }

    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user->password) ? $user : false;
    }

    public function registerUser($name, $email, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($userData);
    }
}
```

## Mise en place des vues

### Étape 11 : Créer le gestionnaire de vues

Dans `src/Core/View.php` :

```php
<?php
namespace Core;

class View
{
    private $layoutDir;
    private $viewsDir;
    private $layout = 'default';

    public function __construct()
    {
        $this->layoutDir = ROOT_PATH . '/src/Views/layouts/';
        $this->viewsDir = ROOT_PATH . '/src/Views/';
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function render($template, array $data = [])
    {
        // Extraire les données pour les rendre accessibles dans la vue
        extract($data);
        
        // Commencer la mise en mémoire tampon
        ob_start();
        
        // Inclure le template
        $templatePath = $this->viewsDir . $template . '.php';
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new \Exception("Vue introuvable : {$templatePath}");
        }
        
        // Récupérer le contenu
        $content = ob_get_clean();
        
        // Charger le layout
        $layoutPath = $this->layoutDir . $this->layout . '.php';
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            throw new \Exception("Layout introuvable : {$layoutPath}");
        }
    }
}
```

### Étape 12 : Créer un layout de base

Dans `src/Views/layouts/default.php` :

```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mon Application MVC' ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="/">Accueil</a></li>
                <li><a href="/about">À propos</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <?= $content ?>
    </main>
    
    <footer>
        <p>&copy; <?= date('Y') ?> - Mon Application MVC</p>
    </footer>
    
    <script src="/js/app.js"></script>
</body>
</html>
```

### Étape 13 : Créer des exemples de vues

Dans `src/Views/home/index.php` :

```php
<div class="container">
    <h1><?= $title ?></h1>
    <p><?= $content ?></p>
</div>
```

Dans `src/Views/home/about.php` :

```php
<div class="container">
    <h1><?= $title ?></h1>
    <p><?= $content ?></p>
</div>
```

## Gestion des routes

### Étape 14 : Créer le système de routage

Dans `src/Core/Router.php` :

```php
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
```

### Étape 15 : Configuration des routes

Dans `config/config.php` :

```php
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
```

## Configuration de la base de données

### Étape 16 : Créer la configuration de la base de données

Dans `config/database.php` :

```php
<?php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'mvc_app',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];
```

### Étape 17 : Créer la classe Database

Dans `src/Core/Database.php` :

```php
<?php
namespace Core;

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct()
    {
        $this->config = require ROOT_PATH . '/config/database.php';
        
        $dsn = "{$this->config['driver']}:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
        
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], $options);
        } catch (\PDOException $e) {
            throw new \Exception("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    public function query($sql, $params = [], $single = false)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $single ? $stmt->fetch() : $stmt->fetchAll();
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}
```

## Sécurité et validation

### Étape 18 : Créer une classe pour la validation des données

Dans `src/Core/Validator.php` :

```php
<?php
namespace Core;

class Validator
{
    private $errors = [];
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required($field, $message = null)
    {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message ?? "Le champ {$field} est requis";
        }
        
        return $this;
    }

    public function email($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "L'adresse e-mail n'est pas valide";
        }
        
        return $this;
    }

    public function min($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit contenir au moins {$length} caractères";
        }
        
        return $this;
    }

    public function max($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit contenir au maximum {$length} caractères";
        }
        
        return $this;
    }

    public function matches($field, $matchField, $message = null)
    {
        if (isset($this->data[$field], $this->data[$matchField]) && $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? "Les champs ne correspondent pas";
        }
        
        return $this;
    }

    public function isValid()
    {
        return empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
```

### Étape 19 : Créer une classe pour la sécurité

Dans `src/Core/Security.php` :

```php
<?php
namespace Core;

class Security
{
    private $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    public function sanitize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
        } else {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }

    public function generateCsrfToken()
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        
        return $token;
    }

    public function validateCsrfToken($token)
    {
        $storedToken = $this->session->get('csrf_token');
        
        if (!$storedToken || $token !== $storedToken) {
            return false;
        }
        
        // Générer un nouveau token pour éviter les attaques de replay
        $this->generateCsrfToken();
        
        return true;
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
```

## Fonctionnalités additionnelles

### Étape 20 : Créer une classe Session

Dans `src/Core/Session.php` :

```php
<?php
namespace Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function destroy()
    {
        session_destroy();
    }

    public function flash($key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
        } else {
            $value = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $value;
        }
    }

    public function hasFlash($key)
    {
        return isset($_SESSION['flash'][$key]);
    }
}
```

### Étape 21 : Créer les classes Request et Response

Dans `src/Core/Request.php` :

```php
<?php
namespace Core;

class Request
{
    private $get;
    private $post;
    private $server;
    private $files;
    private $cookie;
    private $uri;
    private $method;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookie = $_COOKIE;
        $this->uri = $this->parseUri();
        $this->method = $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    private function parseUri()
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        
        return $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        
        return $this->get[$key] ?? $default;
    }

    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }

    public function file($key)
    {
        return $this->files[$key] ?? null;
    }

    public function cookie($key, $default = null)
    {
        return $this->cookie[$key] ?? $default;
    }

    public function isGet()
    {
        return $this->method === 'GET';
    }

    public function isPost()
    {
        return $this->method === 'POST';
    }

    public function isPut()
    {
        return $this->method === 'PUT';
    }

    public function isDelete()
    {
        return $this->method === 'DELETE';
    }

    public function isAjax()
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
```

Dans `src/Core/Response.php` :

```php
<?php
namespace Core;

class Response
{
    private $statusCode = 200;
    private $headers = [];

    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        http_response_code($code);
        
        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        header("{$name}: {$value}");
        
        return $this;
    }

    public function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    public function json($data, $statusCode = null)
    {
        if ($statusCode !== null) {
            $this->setStatusCode($statusCode);
        }
        
        $this->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
        exit;
    }

    public function output($content, $statusCode = null)
    {
        if ($statusCode !== null) {
            $this->setStatusCode($statusCode);
        }
        
        echo $content;
        exit;
    }
}
```

## Comment utiliser ce framework

Pour utiliser ce framework :

1. Clonez ou téléchargez ce code source
2. Configurez votre serveur web pour pointer vers le dossier `public`
3. Personnalisez les fichiers de configuration dans le dossier `config`
4. Créez vos contrôleurs, modèles et vues en suivant les exemples fournis
5. Définissez vos routes dans le fichier `config/config.php`

Ceci représente une base solide pour un framework MVC PHP personnalisé, inspiré de Symfony mais sans dépendances externes.