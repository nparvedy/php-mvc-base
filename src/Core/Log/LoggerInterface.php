<?php
namespace Core\Log;

/**
 * Interface pour tous les loggers
 */
interface LoggerInterface
{
    /**
     * Log d'erreur système
     * 
     * @param string $message Message d'erreur
     * @param array $context Contexte additionnel
     * @return void
     */
    public function emergency($message, array $context = []);
    
    /**
     * Action immédiate requise
     * 
     * @param string $message Message d'alerte
     * @param array $context Contexte additionnel
     * @return void
     */
    public function alert($message, array $context = []);
    
    /**
     * Condition critique
     * 
     * @param string $message Message critique
     * @param array $context Contexte additionnel
     * @return void
     */
    public function critical($message, array $context = []);
    
    /**
     * Erreur d'exécution
     * 
     * @param string $message Message d'erreur
     * @param array $context Contexte additionnel
     * @return void
     */
    public function error($message, array $context = []);
    
    /**
     * Avertissement non-critique
     * 
     * @param string $message Message d'avertissement
     * @param array $context Contexte additionnel
     * @return void
     */
    public function warning($message, array $context = []);
    
    /**
     * Événement normal mais significatif
     * 
     * @param string $message Message de notification
     * @param array $context Contexte additionnel
     * @return void
     */
    public function notice($message, array $context = []);
    
    /**
     * Information générale
     * 
     * @param string $message Message d'information
     * @param array $context Contexte additionnel
     * @return void
     */
    public function info($message, array $context = []);
    
    /**
     * Information de débogage
     * 
     * @param string $message Message de débogage
     * @param array $context Contexte additionnel
     * @return void
     */
    public function debug($message, array $context = []);
    
    /**
     * Log avec niveau personnalisé
     * 
     * @param string $level Niveau du log
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     * @return void
     */
    public function log($level, $message, array $context = []);
}