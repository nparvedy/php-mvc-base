<?php
namespace CLI;

/**
 * Classe abstraite fournissant l'implémentation de base pour les commandes CLI
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Arguments de la commande
     * @var array
     */
    protected $args = [];
    
    /**
     * Options parsées depuis les arguments
     * @var array
     */
    protected $options = [];
    
    /**
     * Options supportées par la commande
     * Format : ['nom' => ['alias' => 'a', 'description' => '...', 'value' => bool]]
     * @var array
     */
    protected $supportedOptions = [];
    
    /**
     * Définir les arguments de la commande
     * 
     * @param array $args Arguments
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
        $this->parseOptions();
        
        return $this;
    }
    
    /**
     * Obtenir le nom de la commande
     * 
     * @return string
     */
    abstract public function getName();
    
    /**
     * Obtenir la description de la commande
     * 
     * @return string
     */
    abstract public function getDescription();
    
    /**
     * Exécuter la commande
     * 
     * @return mixed
     */
    abstract public function execute();
    
    /**
     * Obtenir l'aide détaillée de la commande
     * 
     * @return string
     */
    public function getHelp()
    {
        $help = $this->getDescription() . "\n\n";
        $help .= "Usage: php mvc {$this->getName()} [options]\n\n";
        
        if (!empty($this->supportedOptions)) {
            $help .= "Options:\n";
            
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
        }
        
        return $help;
    }
    
    /**
     * Parser les options depuis les arguments
     */
    protected function parseOptions()
    {
        $this->options = [];
        
        $i = 0;
        while ($i < count($this->args)) {
            $arg = $this->args[$i];
            
            // Option longue (--option)
            if (substr($arg, 0, 2) === '--') {
                $name = substr($arg, 2);
                $value = true;
                
                // Si l'option a une valeur (--option=value)
                if (strpos($name, '=') !== false) {
                    list($name, $value) = explode('=', $name, 2);
                }
                // Sinon, vérifier si l'argument suivant est une valeur
                elseif (isset($this->supportedOptions[$name]) && 
                      !empty($this->supportedOptions[$name]['value']) && 
                      isset($this->args[$i + 1]) && 
                      substr($this->args[$i + 1], 0, 1) !== '-') {
                    $value = $this->args[$i + 1];
                    $i++;
                }
                
                $this->options[$name] = $value;
            }
            // Option courte (-o)
            elseif (substr($arg, 0, 1) === '-' && strlen($arg) === 2) {
                $alias = substr($arg, 1);
                $name = $this->getOptionNameFromAlias($alias);
                
                if ($name) {
                    $value = true;
                    
                    // Si l'option attend une valeur
                    if (!empty($this->supportedOptions[$name]['value']) && 
                        isset($this->args[$i + 1]) && 
                        substr($this->args[$i + 1], 0, 1) !== '-') {
                        $value = $this->args[$i + 1];
                        $i++;
                    }
                    
                    $this->options[$name] = $value;
                }
            }
            
            $i++;
        }
    }
    
    /**
     * Obtenir le nom d'une option à partir de son alias
     * 
     * @param string $alias Alias de l'option
     * @return string|null
     */
    protected function getOptionNameFromAlias($alias)
    {
        foreach ($this->supportedOptions as $name => $option) {
            if (isset($option['alias']) && $option['alias'] === $alias) {
                return $name;
            }
        }
        
        return null;
    }
    
    /**
     * Vérifier si une option est définie
     * 
     * @param string $name Nom de l'option
     * @return bool
     */
    protected function hasOption($name)
    {
        return isset($this->options[$name]);
    }
    
    /**
     * Obtenir la valeur d'une option
     * 
     * @param string $name Nom de l'option
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    protected function getOption($name, $default = null)
    {
        return $this->hasOption($name) ? $this->options[$name] : $default;
    }
    
    /**
     * Afficher un message dans la console
     * 
     * @param string $message Message à afficher
     * @return $this
     */
    protected function output($message)
    {
        echo $message . PHP_EOL;
        
        return $this;
    }
    
    /**
     * Afficher un message de succès
     * 
     * @param string $message Message à afficher
     * @return $this
     */
    protected function success($message)
    {
        return $this->output("\033[32m" . $message . "\033[0m");
    }
    
    /**
     * Afficher un message d'erreur
     * 
     * @param string $message Message à afficher
     * @return $this
     */
    protected function error($message)
    {
        return $this->output("\033[31m" . $message . "\033[0m");
    }
    
    /**
     * Afficher un message d'information
     * 
     * @param string $message Message à afficher
     * @return $this
     */
    protected function info($message)
    {
        return $this->output("\033[36m" . $message . "\033[0m");
    }
    
    /**
     * Afficher un message d'avertissement
     * 
     * @param string $message Message à afficher
     * @return $this
     */
    protected function warning($message)
    {
        return $this->output("\033[33m" . $message . "\033[0m");
    }
    
    /**
     * Poser une question et obtenir la réponse
     * 
     * @param string $question Question à poser
     * @param mixed $default Valeur par défaut
     * @return string
     */
    protected function ask($question, $default = null)
    {
        $defaultText = $default !== null ? " [" . $default . "]" : '';
        $this->output($question . $defaultText . ': ');
        
        $handle = fopen('php://stdin', 'r');
        $answer = trim(fgets($handle));
        fclose($handle);
        
        return $answer ?: $default;
    }
    
    /**
     * Poser une question de confirmation (oui/non)
     * 
     * @param string $question Question à poser
     * @param bool $default Valeur par défaut
     * @return bool
     */
    protected function confirm($question, $default = true)
    {
        $defaultText = $default ? 'O/n' : 'o/N';
        $answer = $this->ask($question . " ({$defaultText})");
        
        if ($answer === '') {
            return $default;
        }
        
        $answer = strtolower($answer);
        
        return $answer === 'o' || $answer === 'oui' || $answer === 'y' || $answer === 'yes';
    }
}