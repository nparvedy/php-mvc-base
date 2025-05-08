<?php
namespace CLI;

/**
 * Interface pour toutes les commandes CLI
 */
interface CommandInterface
{
    /**
     * Définir les arguments pour la commande
     * 
     * @param array $args Arguments de la commande
     * @return $this
     */
    public function setArgs(array $args);
    
    /**
     * Obtenir le nom de la commande
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Obtenir la description de la commande
     * 
     * @return string
     */
    public function getDescription();
    
    /**
     * Obtenir l'aide détaillée de la commande
     * 
     * @return string
     */
    public function getHelp();
    
    /**
     * Exécuter la commande
     * 
     * @return mixed
     */
    public function execute();
}