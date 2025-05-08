<?php
namespace CLI\Commands;

use CLI\AbstractCommand;
use CLI\CommandManager;

/**
 * Commande pour afficher l'aide
 */
class HelpCommand extends AbstractCommand
{
    /**
     * Le gestionnaire de commandes
     * @var CommandManager
     */
    protected $commandManager;
    
    /**
     * Constructeur
     * 
     * @param CommandManager $commandManager
     */
    public function __construct(CommandManager $commandManager)
    {
        $this->commandManager = $commandManager;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'help';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Afficher l\'aide pour les commandes disponibles';
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $commandName = isset($this->args[0]) ? $this->args[0] : null;
        
        echo $this->commandManager->showHelp($commandName);
        
        return 0;
    }
}