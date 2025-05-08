<?php
namespace CLI\Commands;

use CLI\AbstractCommand;

/**
 * Commande pour générer des composants du framework
 */
class MakeCommand extends AbstractCommand
{
    /**
     * Types de composants pouvant être générés
     * @var array
     */
    protected $types = [
        'controller' => [
            'path' => 'src/Controllers',
            'namespace' => 'Controllers',
            'suffix' => 'Controller',
            'template' => 'controller.tpl.php',
        ],
        'model' => [
            'path' => 'src/Models',
            'namespace' => 'Models',
            'suffix' => 'Model',
            'template' => 'model.tpl.php',
        ],
        'middleware' => [
            'path' => 'src/Core/Middleware',
            'namespace' => 'Core\Middleware',
            'suffix' => '',
            'template' => 'middleware.tpl.php',
        ],
        'migration' => [
            'path' => 'src/Migrations',
            'namespace' => 'Migrations',
            'suffix' => '',
            'template' => 'migration.tpl.php',
            'prefix' => true,
        ],
        'command' => [
            'path' => 'src/CLI/Commands',
            'namespace' => 'CLI\Commands',
            'suffix' => 'Command',
            'template' => 'command.tpl.php',
        ],
        'job' => [
            'path' => 'src/Jobs',
            'namespace' => 'Jobs',
            'suffix' => 'Job',
            'template' => 'job.tpl.php',
        ]
    ];
    
    /**
     * Options supportées
     */
    protected $supportedOptions = [
        'force' => [
            'alias' => 'f',
            'description' => 'Remplacer le fichier s\'il existe déjà',
            'value' => false,
        ],
        'table' => [
            'alias' => 't',
            'description' => 'Nom de la table (pour les modèles et migrations)',
            'value' => true,
        ],
        'parent' => [
            'alias' => 'p',
            'description' => 'Classe parente (pour les contrôleurs et modèles)',
            'value' => true,
        ],
    ];
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'make';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Générer un composant du framework';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        $help = $this->getDescription() . "\n\n";
        $help .= "Usage: php mvc make [type] [nom] [options]\n\n";
        $help .= "Types disponibles:\n";
        
        foreach ($this->types as $type => $config) {
            $help .= "  - {$type}\n";
        }
        
        $help .= "\nOptions:\n";
        
        foreach ($this->supportedOptions as $name => $option) {
            $optionStr = "  --{$name}";
            
            if (isset($option['alias'])) {
                $optionStr .= ", -{$option['alias']}";
            }
            
            if (!empty($option['value'])) {
                $optionStr .= " <value>";
            }
            
            $help .= str_pad($optionStr, 25) . $option['description'] . "\n";
        }
        
        $help .= "\nExemples:\n";
        $help .= "  php mvc make controller UserController\n";
        $help .= "  php mvc make model User --table=users\n";
        $help .= "  php mvc make migration CreateUsersTable\n";
        
        return $help;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (count($this->args) < 2) {
            $this->error("Arguments manquants.");
            echo $this->getHelp();
            return 1;
        }
        
        $type = strtolower($this->args[0]);
        $name = $this->args[1];
        
        if (!isset($this->types[$type])) {
            $this->error("Type non valide : {$type}");
            echo "Types disponibles : " . implode(', ', array_keys($this->types)) . "\n";
            return 1;
        }
        
        return $this->generateComponent($type, $name);
    }
    
    /**
     * Générer un composant
     * 
     * @param string $type Type de composant
     * @param string $name Nom du composant
     * @return int
     */
    protected function generateComponent($type, $name)
    {
        $config = $this->types[$type];
        
        // Appliquer le suffixe si non présent
        if (!empty($config['suffix']) && !str_ends_with($name, $config['suffix'])) {
            $name .= $config['suffix'];
        }
        
        // Préparer le chemin et le namespace
        $path = ROOT_PATH . '/' . $config['path'];
        $namespace = $config['namespace'];
        
        // Créer le répertoire si nécessaire
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $this->error("Impossible de créer le répertoire : {$path}");
                return 1;
            }
        }
        
        // Détecter le nom de classe depuis le nom complet
        $className = basename(str_replace('\\', '/', $name));
        
        // Appliquer le préfixe (pour les migrations)
        $fileName = $className;
        if (!empty($config['prefix'])) {
            $fileName = date('YmdHis') . '_' . $fileName;
        }
        
        // Construire le chemin complet du fichier
        $filePath = $path . '/' . $fileName . '.php';
        
        // Vérifier si le fichier existe déjà
        if (file_exists($filePath) && !$this->hasOption('force')) {
            $this->error("Le fichier existe déjà : {$filePath}");
            $this->info("Utilisez --force pour remplacer le fichier existant.");
            return 1;
        }
        
        // Récupérer le template et générer le contenu
        $content = $this->generateContent($type, [
            'namespace' => $namespace,
            'className' => $className,
            'tableName' => $this->getOption('table', $this->guessTableName($className)),
            'parentClass' => $this->getOption('parent'),
        ]);
        
        // Écrire le fichier
        if (file_put_contents($filePath, $content)) {
            $this->success("Composant {$type} créé : {$filePath}");
            return 0;
        }
        
        $this->error("Impossible de créer le fichier : {$filePath}");
        return 1;
    }
    
    /**
     * Générer le contenu du fichier
     * 
     * @param string $type Type de composant
     * @param array $data Données de substitution
     * @return string
     */
    protected function generateContent($type, array $data)
    {
        $config = $this->types[$type];
        $templateName = $config['template'];
        $templatePath = ROOT_PATH . '/src/CLI/Templates/' . $templateName;
        
        // Si le fichier template n'existe pas, utiliser un template par défaut
        if (!file_exists($templatePath)) {
            return $this->getDefaultTemplate($type, $data);
        }
        
        $content = file_get_contents($templatePath);
        
        // Remplacer les variables dans le template
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Obtenir un template par défaut pour un type de composant
     * 
     * @param string $type Type de composant
     * @param array $data Données de substitution
     * @return string
     */
    protected function getDefaultTemplate($type, array $data)
    {
        switch ($type) {
            case 'controller':
                return $this->getControllerTemplate($data);
            case 'model':
                return $this->getModelTemplate($data);
            case 'middleware':
                return $this->getMiddlewareTemplate($data);
            case 'migration':
                return $this->getMigrationTemplate($data);
            case 'command':
                return $this->getCommandTemplate($data);
            case 'job':
                return $this->getJobTemplate($data);
            default:
                return $this->getGenericTemplate($data);
        }
    }
    
    /**
     * Template par défaut pour les contrôleurs
     */
    protected function getControllerTemplate($data)
    {
        $parentClass = !empty($data['parentClass']) ? $data['parentClass'] : 'Core\Controller';
        
        return <<<PHP
<?php
namespace {$data['namespace']};

use {$parentClass};

class {$data['className']} extends Controller
{
    /**
     * Action par défaut
     */
    public function index()
    {
        // TODO: Implémenter cette méthode
        return \$this->render('index');
    }
    
    /**
     * Action pour afficher un élément
     */
    public function show(\$id)
    {
        // TODO: Implémenter cette méthode
        return \$this->render('show', [
            'id' => \$id
        ]);
    }
    
    /**
     * Action pour afficher le formulaire de création
     */
    public function create()
    {
        // TODO: Implémenter cette méthode
        return \$this->render('create');
    }
    
    /**
     * Action pour traiter le formulaire de création
     */
    public function store()
    {
        // TODO: Implémenter cette méthode
        return \$this->redirect('/');
    }
    
    /**
     * Action pour afficher le formulaire d'édition
     */
    public function edit(\$id)
    {
        // TODO: Implémenter cette méthode
        return \$this->render('edit', [
            'id' => \$id
        ]);
    }
    
    /**
     * Action pour traiter le formulaire d'édition
     */
    public function update(\$id)
    {
        // TODO: Implémenter cette méthode
        return \$this->redirect('/');
    }
    
    /**
     * Action pour supprimer un élément
     */
    public function delete(\$id)
    {
        // TODO: Implémenter cette méthode
        return \$this->redirect('/');
    }
}
PHP;
    }
    
    /**
     * Template par défaut pour les modèles
     */
    protected function getModelTemplate($data)
    {
        $parentClass = !empty($data['parentClass']) ? $data['parentClass'] : 'Core\Model';
        
        return <<<PHP
<?php
namespace {$data['namespace']};

use {$parentClass};

class {$data['className']} extends Model
{
    /**
     * Table associée au modèle
     * @var string
     */
    protected \$table = '{$data['tableName']}';
    
    /**
     * Champs remplissables
     * @var array
     */
    protected \$fillable = [
        // TODO: Définir les champs remplissables
    ];
    
    /**
     * Utiliser les timestamps created_at et updated_at
     * 
     * @return bool
     */
    public function useTimestamps()
    {
        return true;
    }
    
    // TODO: Ajouter les relations et méthodes spécifiques au modèle
}
PHP;
    }
    
    /**
     * Template par défaut pour les middlewares
     */
    protected function getMiddlewareTemplate($data)
    {
        return <<<PHP
<?php
namespace {$data['namespace']};

use Core\Middleware\MiddlewareInterface;
use Core\Request;

class {$data['className']} implements MiddlewareInterface
{
    /**
     * Traiter la requête
     * 
     * @param Request \$request Requête HTTP
     * @param callable \$next Middleware suivant
     * @return mixed
     */
    public function handle(Request \$request, callable \$next)
    {
        // Traitement avant l'action du contrôleur
        
        // Appel du middleware suivant
        \$response = \$next(\$request);
        
        // Traitement après l'action du contrôleur
        
        return \$response;
    }
}
PHP;
    }
    
    /**
     * Template par défaut pour les migrations
     */
    protected function getMigrationTemplate($data)
    {
        return <<<PHP
<?php
namespace {$data['namespace']};

use Core\Migration\Migration;

class {$data['className']} extends Migration
{
    /**
     * Exécuter la migration
     * 
     * @return void
     */
    public function up()
    {
        \$this->schema->create('{$data['tableName']}', function(\$table) {
            \$table->id();
            // TODO: Ajouter les colonnes
            \$table->timestamps();
        });
    }
    
    /**
     * Annuler la migration
     * 
     * @return void
     */
    public function down()
    {
        \$this->schema->drop('{$data['tableName']}');
    }
}
PHP;
    }
    
    /**
     * Template par défaut pour les commandes CLI
     */
    protected function getCommandTemplate($data)
    {
        return <<<PHP
<?php
namespace {$data['namespace']};

use CLI\AbstractCommand;

class {$data['className']} extends AbstractCommand
{
    /**
     * Options supportées
     */
    protected \$supportedOptions = [
        // TODO: Définir les options supportées
        // 'nom' => [
        //     'alias' => 'n',
        //     'description' => 'Description de l\'option',
        //     'value' => true,
        // ],
    ];
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        // Convertir CamelCase en kebab-case
        \$name = preg_replace('/([a-z])([A-Z])/', '\$1-\$2', \$this->getClassShortName());
        \$name = strtolower(str_replace('Command', '', \$name));
        
        return \$name;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Description de la commande';
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        \$this->info('Exécution de la commande ' . \$this->getName());
        
        // TODO: Implémenter la logique de la commande
        
        \$this->success('Commande exécutée avec succès');
        return 0;
    }
    
    /**
     * Obtenir le nom court de la classe (sans namespace)
     * 
     * @return string
     */
    protected function getClassShortName()
    {
        \$classname = get_class(\$this);
        
        if (\$pos = strrpos(\$classname, '\\')) {
            return substr(\$classname, \$pos + 1);
        }
        
        return \$classname;
    }
}
PHP;
    }
    
    /**
     * Template par défaut pour les jobs
     */
    protected function getJobTemplate($data)
    {
        return <<<PHP
<?php
namespace {$data['namespace']};

use Core\Queue\Job;

class {$data['className']} implements Job
{
    /**
     * Exécuter la tâche
     * 
     * @param mixed \$data Données associées à la tâche
     * @return void
     */
    public function handle(\$data)
    {
        // TODO: Implémenter la logique de traitement de la tâche
    }
}
PHP;
    }
    
    /**
     * Template générique pour les autres types
     */
    protected function getGenericTemplate($data)
    {
        return <<<PHP
<?php
namespace {$data['namespace']};

class {$data['className']}
{
    // TODO: Implémenter cette classe
}
PHP;
    }
    
    /**
     * Deviner le nom de la table à partir du nom de classe
     * 
     * @param string \$className Nom de la classe
     * @return string
     */
    protected function guessTableName($className)
    {
        // Retirer le suffixe "Model" ou "Controller"
        $name = str_replace(['Model', 'Controller'], '', $className);
        
        // Convertir CamelCase en snake_case
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        
        // Pluraliser (simpliste)
        if (!str_ends_with($name, 's')) {
            $name .= 's';
        }
        
        return $name;
    }
    
    /**
     * Vérifie si une chaîne se termine par un suffixe
     * 
     * @param string $haystack La chaîne à vérifier
     * @param string $needle Le suffixe à rechercher
     * @return bool
     */
    private function str_ends_with($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        
        return (substr($haystack, -$length) === $needle);
    }
}