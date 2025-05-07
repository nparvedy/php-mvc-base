<?php
namespace Core;

class ErrorHandler
{
    /**
     * Mode de développement
     * @var bool
     */
    private $debug;
    
    /**
     * Chemin du dossier des logs
     * @var string
     */
    private $logPath;
    
    /**
     * Instance de View
     * @var View
     */
    private $view;

    /**
     * Constructeur
     *
     * @param bool $debug Mode de développement
     * @param string $logPath Chemin du dossier des logs
     */
    public function __construct($debug = true, $logPath = null)
    {
        $this->debug = $debug;
        $this->logPath = $logPath ?: ROOT_PATH . '/var/logs';
        $this->view = new View();
        
        // Créer le dossier de logs s'il n'existe pas
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Configurer les gestionnaires d'erreurs et d'exceptions
     */
    public function register()
    {
        // Définir le gestionnaire d'exceptions
        set_exception_handler([$this, 'handleException']);
        
        // Définir le gestionnaire d'erreurs
        set_error_handler([$this, 'handleError']);
        
        // Définir le gestionnaire d'arrêt
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Gérer les exceptions non capturées
     *
     * @param \Throwable $exception L'exception à gérer
     */
    public function handleException(\Throwable $exception)
    {
        // Logger l'exception
        $this->logException($exception);
        
        // Afficher la page d'erreur appropriée
        $this->displayException($exception);
    }

    /**
     * Gérer les erreurs PHP
     *
     * @param int $level Niveau d'erreur
     * @param string $message Message d'erreur
     * @param string $file Fichier où l'erreur s'est produite
     * @param int $line Ligne où l'erreur s'est produite
     * @return bool
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0)
    {
        if (error_reporting() & $level) {
            // Convertir les erreurs en exceptions
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        
        return true;
    }

    /**
     * Gérer les erreurs fatales lors de l'arrêt du script
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $exception = new \ErrorException(
                $error['message'], 
                0, 
                $error['type'], 
                $error['file'], 
                $error['line']
            );
            
            $this->handleException($exception);
        }
    }

    /**
     * Logger une exception dans un fichier
     *
     * @param \Throwable $exception L'exception à logger
     */
    private function logException(\Throwable $exception)
    {
        $date = date('Y-m-d');
        $logFile = $this->logPath . "/error-{$date}.log";
        
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        error_log($message, 3, $logFile);
    }

    /**
     * Afficher une exception à l'utilisateur
     *
     * @param \Throwable $exception L'exception à afficher
     */
    private function displayException(\Throwable $exception)
    {
        $statusCode = 500;
        
        // Définir le code de statut HTTP approprié
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }
        
        http_response_code($statusCode);
        
        if ($this->debug) {
            // En mode développement, afficher les détails de l'erreur
            echo $this->renderDebugView($exception);
        } else {
            // En mode production, afficher une page d'erreur générique
            try {
                // Essayer d'utiliser le système de vues pour rendre la page d'erreur
                echo $this->view->render('errors/error', [
                    'title' => "Erreur {$statusCode}",
                    'statusCode' => $statusCode,
                    'message' => $statusCode === 404 
                        ? 'Page non trouvée' 
                        : 'Une erreur est survenue'
                ]);
            } catch (\Exception $e) {
                // Si le rendu de la vue échoue, afficher un message simple
                echo "Erreur {$statusCode}: " . ($statusCode === 404 
                    ? 'Page non trouvée' 
                    : 'Une erreur est survenue');
            }
        }
    }

    /**
     * Afficher une vue de débogage détaillée pour les développeurs
     *
     * @param \Throwable $exception L'exception à afficher
     * @return string
     */
    private function renderDebugView(\Throwable $exception)
    {
        $title = get_class($exception);
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        // Récupérer le code source autour de la ligne d'erreur
        $source = $this->getSourceContext($file, $line);
        
        $html = "<!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Erreur: {$title}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                .error-container { max-width: 1200px; margin: 0 auto; background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 20px; }
                .error-title { color: #e74c3c; margin-top: 0; }
                .error-message { font-size: 18px; margin-bottom: 20px; }
                .error-details { background: #fff; border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 20px; }
                .error-file { color: #3498db; }
                .error-source { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; }
                .error-line { background: #c0392b; }
                .error-trace { font-family: monospace; overflow-x: auto; background: #f5f5f5; padding: 15px; margin-top: 20px; }
                .error-trace pre { margin: 0; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <h1 class='error-title'>{$title}</h1>
                <div class='error-message'>{$message}</div>
                <div class='error-details'>
                    <p class='error-file'>
                        <strong>Fichier:</strong> {$file} sur la ligne <strong>{$line}</strong>
                    </p>
                    <div class='error-source'>
                        {$source}
                    </div>
                </div>
                <h3>Stack Trace:</h3>
                <div class='error-trace'>
                    <pre>{$trace}</pre>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Récupérer le contexte du code source autour d'une ligne spécifique
     *
     * @param string $file Le fichier à analyser
     * @param int $line La ligne à mettre en évidence
     * @param int $linesAround Nombre de lignes à afficher avant et après
     * @return string
     */
    private function getSourceContext($file, $line, $linesAround = 10)
    {
        if (!file_exists($file) || !is_readable($file)) {
            return 'Impossible de lire le fichier source';
        }
        
        $source = file($file);
        $start = max(0, $line - $linesAround - 1);
        $end = min(count($source) - 1, $line + $linesAround - 1);
        
        $html = '';
        
        for ($i = $start; $i <= $end; $i++) {
            $lineNumber = $i + 1;
            $sourceCode = htmlspecialchars($source[$i]);
            
            if ($lineNumber === $line) {
                $html .= "<div class='error-line'><strong>{$lineNumber}:</strong> {$sourceCode}</div>";
            } else {
                $html .= "<div><strong>{$lineNumber}:</strong> {$sourceCode}</div>";
            }
        }
        
        return $html;
    }
}

/**
 * Exception HTTP personnalisée avec code de statut
 */
class HttpException extends \Exception
{
    protected $statusCode;
    
    public function __construct($message = '', $statusCode = 500, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
    }
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}