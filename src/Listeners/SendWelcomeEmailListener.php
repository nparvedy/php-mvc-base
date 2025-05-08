<?php
namespace Listeners;

use Core\Event\EventListenerInterface;
use Events\UserRegisteredEvent;

/**
 * Écouteur qui envoie un email de bienvenue aux nouveaux utilisateurs
 */
class SendWelcomeEmailListener implements EventListenerInterface
{
    /**
     * Traite l'événement
     *
     * @param object $event L'événement à traiter
     * @return void
     */
    public function handle($event)
    {
        if (!($event instanceof UserRegisteredEvent)) {
            return;
        }
        
        $user = $event->getUser();
        $email = $user->getEmail();
        $username = $user->getUsername();
        
        // Dans un environnement réel, vous utiliseriez une classe dédiée pour envoyer des emails
        $this->sendEmail($email, $username);
    }
    
    /**
     * Envoie un email (simulation)
     *
     * @param string $to Adresse email du destinataire
     * @param string $username Nom d'utilisateur
     * @return bool
     */
    private function sendEmail($to, $username)
    {
        // Simulation d'envoi d'email - dans un environnement réel, vous utiliseriez PHPMailer ou une autre bibliothèque
        $subject = "Bienvenue sur notre site !";
        $message = "Bonjour {$username},\n\n";
        $message .= "Merci de votre inscription sur notre site. Nous sommes ravis de vous compter parmi nos membres.\n\n";
        $message .= "Si vous avez des questions, n'hésitez pas à nous contacter.\n\n";
        $message .= "Cordialement,\nL'équipe";
        
        // Journaliser l'envoi d'email (pour le développement/débogage)
        $logMessage = date('Y-m-d H:i:s') . " - Email envoyé à {$to} avec le sujet '{$subject}'\n";
        file_put_contents(
            dirname(__DIR__, 2) . '/var/logs/emails-' . date('Y-m-d') . '.log',
            $logMessage,
            FILE_APPEND
        );
        
        return true;
    }
}