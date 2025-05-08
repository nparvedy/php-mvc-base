<?php
namespace Core\Log;

/**
 * Classe abstraite fournissant l'implémentation de base pour les loggers
 */
abstract class AbstractLogger implements LoggerInterface
{
    /**
     * Niveaux de log standards
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    
    /**
     * Niveau de log minimum à enregistrer
     * @var string
     */
    protected $minimumLevel;
    
    /**
     * Ordre des niveaux de log (du plus grave au moins grave)
     * @var array
     */
    protected static $levels = [
        self::EMERGENCY => 0,
        self::ALERT     => 1,
        self::CRITICAL  => 2,
        self::ERROR     => 3,
        self::WARNING   => 4,
        self::NOTICE    => 5,
        self::INFO      => 6,
        self::DEBUG     => 7,
    ];
    
    /**
     * Constructeur
     * 
     * @param string $minimumLevel Niveau de log minimum à enregistrer
     */
    public function __construct($minimumLevel = self::DEBUG)
    {
        $this->minimumLevel = $minimumLevel;
    }
    
    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        // Vérifier si le niveau est suffisant pour être enregistré
        if (!$this->isLoggable($level)) {
            return;
        }
        
        // Remplacer les placeholders dans le message
        $message = $this->interpolate($message, $context);
        
        // Déléguer l'enregistrement à la classe concrète
        $this->writeLog($level, $message, $context);
    }
    
    /**
     * Écriture effective du message de log (à implémenter par les classes concrètes)
     * 
     * @param string $level Niveau de log
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     * @return void
     */
    abstract protected function writeLog($level, $message, array $context = []);
    
    /**
     * Vérifier si un niveau est suffisamment important pour être enregistré
     * 
     * @param string $level Niveau à vérifier
     * @return bool
     */
    protected function isLoggable($level)
    {
        // Si le niveau n'est pas connu, on considère qu'il est loggable
        if (!isset(static::$levels[$level]) || !isset(static::$levels[$this->minimumLevel])) {
            return true;
        }
        
        // Comparer le niveau du message avec le niveau minimum
        return static::$levels[$level] <= static::$levels[$this->minimumLevel];
    }
    
    /**
     * Remplacer les placeholders {key} dans le message par les valeurs du contexte
     * 
     * @param string $message Message avec placeholders
     * @param array $context Données de contexte
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // Si le message n'est pas une chaîne ou s'il n'y a pas de contexte, retourner tel quel
        if (!is_string($message) || empty($context)) {
            return $message;
        }
        
        // Construire un tableau de remplacement
        $replace = [];
        foreach ($context as $key => $val) {
            // Ignorer les objets qui ne peuvent pas être convertis en chaîne
            if ($val instanceof \Throwable) {
                $replace['{' . $key . '}'] = get_class($val) . ': ' . $val->getMessage() . ' at ' . $val->getFile() . ':' . $val->getLine();
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                $replace['{' . $key . '}'] = (string) $val;
            } elseif (is_scalar($val)) {
                $replace['{' . $key . '}'] = $val;
            } else {
                $replace['{' . $key . '}'] = '[' . gettype($val) . ']';
            }
        }
        
        // Effectuer les remplacements dans le message
        return strtr($message, $replace);
    }
    
    /**
     * Définir le niveau de log minimum
     * 
     * @param string $level Niveau minimum
     * @return $this
     */
    public function setMinimumLevel($level)
    {
        $this->minimumLevel = $level;
        
        return $this;
    }
    
    /**
     * Obtenir le niveau de log minimum
     * 
     * @return string
     */
    public function getMinimumLevel()
    {
        return $this->minimumLevel;
    }
}