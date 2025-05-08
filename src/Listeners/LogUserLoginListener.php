<?php
namespace Listeners;

use Core\Event\EventListenerInterface;
use Events\UserLoggedInEvent;

class LogUserLoginListener implements EventListenerInterface
{
    /**
     * Chemin vers le fichier de journal des connexions
     * @var string
     */
    private $logPath;
    
    /**
     * Constructeur
     *
     * @param string $logPath Chemin vers le fichier de log (optionnel)
     */
    public function __construct(string $logPath = null)
    {
        // Si aucun chemin n'est fourni, utiliser le dossier de logs par défaut
        if ($logPath === null) {
            $logPath = dirname(__DIR__, 2) . '/var/logs/login-' . date('Y-m-d') . '.log';
        }
        $this->logPath = $logPath;
    }
    
    /**
     * Gestion de l'événement
     *
     * @param UserLoggedInEvent $event
     * @return void
     */
    public function handle($event): void
    {
        if (!$event instanceof UserLoggedInEvent) {
            return;
        }
        
        $user = $event->getUser();
        $ipAddress = $event->getIpAddress();
        $timestamp = date('Y-m-d H:i:s');
        
        // Récupérer les informations de l'utilisateur de manière sécurisée
        $userInfo = $this->extractUserInfo($user);
        
        // Créer le message de log incluant les informations de l'utilisateur
        $logMessage = sprintf(
            "[%s] Utilisateur %s (ID: %s) connecté depuis l'adresse IP %s\n",
            $timestamp,
            $userInfo['name'],
            $userInfo['id'],
            $ipAddress
        );
        
        // S'assurer que le répertoire existe
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Enregistrer dans le fichier de log
        file_put_contents($this->logPath, $logMessage, FILE_APPEND);
    }
    
    /**
     * Extrait les informations de l'utilisateur de manière sécurisée
     * 
     * @param mixed $user L'objet utilisateur
     * @return array Tableau contenant les informations de l'utilisateur
     */
    private function extractUserInfo($user): array
    {
        $userInfo = [
            'id' => 'inconnu',
            'name' => 'inconnu'
        ];
        
        // Méthode 1: Vérifier si l'objet a des méthodes spécifiques
        if (method_exists($user, 'getUsername') && method_exists($user, 'getEmail')) {
            // Si le modèle a des méthodes de type getter
            $userInfo['name'] = $user->getUsername() ?: $user->getEmail();
        }
        
        // Méthode 2: Utilisation de la fonction get_object_vars
        $vars = get_object_vars($user);
        if (!empty($vars)) {
            // L'ID est généralement accessible
            if (isset($vars['id'])) {
                $userInfo['id'] = $vars['id'];
            }
            
            // Si le nom n'a pas été récupéré par la méthode 1
            if ($userInfo['name'] === 'inconnu') {
                if (isset($vars['name'])) {
                    $userInfo['name'] = $vars['name'];
                } elseif (isset($vars['email'])) {
                    $userInfo['name'] = $vars['email'];
                }
            }
        }
        
        // Méthode 3: Si l'objet est stdClass (retourné par PDO)
        if ($user instanceof \stdClass) {
            if (isset($user->id)) {
                $userInfo['id'] = $user->id;
            }
            if (isset($user->name)) {
                $userInfo['name'] = $user->name;
            } elseif (isset($user->email)) {
                $userInfo['name'] = $user->email;
            }
        }
        
        return $userInfo;
    }
    
    /**
     * Retourne les événements auxquels cet écouteur souhaite s'abonner
     * 
     * @return array Tableau associatif [nom de l'événement => méthode à appeler]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserLoggedInEvent::class => 'handle'
        ];
    }
}