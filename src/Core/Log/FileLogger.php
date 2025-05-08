<?php
namespace Core\Log;

/**
 * Logger qui écrit les messages dans des fichiers
 */
class FileLogger extends AbstractLogger
{
    /**
     * Chemin vers le répertoire des logs
     * @var string
     */
    protected $path;
    
    /**
     * Format du nom de fichier
     * @var string
     */
    protected $fileNameFormat = 'Y-m-d';
    
    /**
     * Préfixe du nom de fichier
     * @var string
     */
    protected $prefix;
    
    /**
     * Extension du fichier de log
     * @var string
     */
    protected $extension = '.log';
    
    /**
     * Taille maximale d'un fichier de log en octets
     * @var int|null
     */
    protected $maxFileSize = null;
    
    /**
     * Nombre maximum de fichiers de rotation
     * @var int
     */
    protected $maxFiles = 5;
    
    /**
     * Format de date pour les entrées de log
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';
    
    /**
     * Constructeur
     * 
     * @param string $path Chemin vers le répertoire des logs
     * @param string $prefix Préfixe des fichiers de log
     * @param string $minimumLevel Niveau minimum à logger
     */
    public function __construct($path = null, $prefix = '', $minimumLevel = self::DEBUG)
    {
        parent::__construct($minimumLevel);
        
        $this->path = $path ?: ROOT_PATH . '/var/logs';
        $this->prefix = $prefix;
        
        // Créer le répertoire des logs s'il n'existe pas
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }
    
    /**
     * Définir la taille maximale du fichier avant rotation
     * 
     * @param int|null $size Taille en octets, null pour désactiver
     * @return $this
     */
    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
        
        return $this;
    }
    
    /**
     * Définir le nombre maximum de fichiers de rotation
     * 
     * @param int $count Nombre de fichiers
     * @return $this
     */
    public function setMaxFiles($count)
    {
        $this->maxFiles = max(1, (int)$count);
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function writeLog($level, $message, array $context = [])
    {
        $file = $this->getLogFilePath();
        
        // Construire la ligne de log avec le niveau, la date et le message
        $line = sprintf(
            '[%s] [%s] %s%s',
            date($this->dateFormat),
            strtoupper($level),
            $message,
            PHP_EOL
        );
        
        // Vérifier et effectuer la rotation des logs si nécessaire
        $this->rotate($file);
        
        // Écrire dans le fichier de log
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obtenir le chemin du fichier de log actuel
     * 
     * @return string
     */
    protected function getLogFilePath()
    {
        $date = '';
        
        if ($this->fileNameFormat) {
            $date = '-' . date($this->fileNameFormat);
        }
        
        return $this->path . '/' . $this->prefix . $date . $this->extension;
    }
    
    /**
     * Effectuer la rotation des logs si nécessaire
     * 
     * @param string $file Chemin du fichier
     * @return bool True si une rotation a été effectuée
     */
    protected function rotate($file)
    {
        // Si la taille maximale est désactivée ou le fichier n'existe pas, ne pas faire de rotation
        if ($this->maxFileSize === null || !file_exists($file)) {
            return false;
        }
        
        // Si le fichier est plus petit que la taille maximale, ne pas faire de rotation
        if (filesize($file) < $this->maxFileSize) {
            return false;
        }
        
        // Faire la rotation des fichiers existants
        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $source = ($i === 0) ? $file : $file . '.' . $i;
            $target = $file . '.' . ($i + 1);
            
            if (file_exists($source)) {
                // Supprimer le fichier le plus ancien si nous avons atteint la limite
                if ($i === $this->maxFiles - 1) {
                    unlink($source);
                } else {
                    rename($source, $target);
                }
            }
        }
        
        return true;
    }
}