<?php
namespace CLI;

use Core\Container;

/**
 * Gestionnaire de commandes CLI
 */
class CommandManager
{
    /**
     * Container de dépendances
     * @var Container
     */
    protected $container;
    
    /**
     * Commandes disponibles
     * @var array
     */
    protected $commands = [];
    
    /**
     * Constructeur
     * 
     * @param Container $container Container de dépendances
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->discoverCommands();
    }
    
    /**
     * Découvrir automatiquement les commandes disponibles
     */
    protected function discoverCommands()
    {
        // Répertoire contenant les commandes
        $commandsDir = ROOT_PATH . '/src/CLI/Commands';
        
        if (!is_dir($commandsDir)) {
            return;
        }
        
        // Parcourir les fichiers dans le répertoire des commandes
        $files = glob($commandsDir . '/*.php');
        foreach ($files as $file) {
            $className = 'CLI\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
            
            // Vérifier si la classe existe et implémente CommandInterface
            if (class_exists($className) && is_subclass_of($className, CommandInterface::class)) {
                try {
                    $command = $this->container->make($className);
                    $this->addCommand($command);
                } catch (\Exception $e) {
                    // Ignorer les erreurs de création
                }
            }
        }
        
        // Ajouter manuellement la commande de migration existante
        if (class_exists('CLI\\MigrateCommand') && is_subclass_of('CLI\\MigrateCommand', CommandInterface::class)) {
            $this->addCommand($this->container->make('CLI\\MigrateCommand', [
                'migrationManager' => $this->container->make('migration')
            ]));
        }
    }
    
    /**
     * Ajouter une commande
     * 
     * @param CommandInterface $command Commande à ajouter
     * @return $this
     */
    public function addCommand(CommandInterface $command)
    {
        $this->commands[$command->getName()] = $command;
        
        return $this;
    }
    
    /**
     * Vérifier si une commande existe
     * 
     * @param string $name Nom de la commande
     * @return bool
     */
    public function hasCommand($name)
    {
        return isset($this->commands[$name]);
    }
    
    /**
     * Obtenir une commande par son nom
     * 
     * @param string $name Nom de la commande
     * @return CommandInterface|null
     */
    public function getCommand($name)
    {
        return $this->hasCommand($name) ? $this->commands[$name] : null;
    }
    
    /**
     * Obtenir toutes les commandes disponibles
     * 
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
    
    /**
     * Exécuter une commande
     * 
     * @param string $name Nom de la commande
     * @param array $args Arguments de la commande
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function execute($name, array $args = [])
    {
        $command = $this->getCommand($name);
        
        if (!$command) {
            throw new \InvalidArgumentException("Commande non trouvée : {$name}");
        }
        
        return $command->setArgs($args)->execute();
    }
    
    /**
     * Afficher l'aide générale ou l'aide d'une commande spécifique
     * 
     * @param string|null $name Nom de la commande (null pour l'aide générale)
     * @return string
     */
    public function showHelp($name = null)
    {
        if ($name !== null) {
            $command = $this->getCommand($name);
            
            if ($command) {
                return $command->getHelp();
            }
            
            return "Commande non trouvée : {$name}\n";
        }
        
        // Aide générale
        $help = "Usage: php mvc [commande] [options]\n\n";
        $help .= "Commandes disponibles :\n";
        
        // Trier les commandes par nom
        $commands = $this->getCommands();
        ksort($commands);
        
        foreach ($commands as $cmdName => $command) {
            $help .= sprintf("  %-15s %s\n", $cmdName, $command->getDescription());
        }
        
        $help .= "\nPour voir l'aide détaillée d'une commande, exécutez : php mvc help [commande]";
        
        return $help;
    }
}