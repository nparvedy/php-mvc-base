<?php
namespace Events;

use Core\Event\Event;
use Models\UserModel;

class UserLoggedInEvent extends Event
{
    /**
     * L'utilisateur qui vient de se connecter
     * @var UserModel
     */
    private $user;
    
    /**
     * Adresse IP de l'utilisateur
     * @var string
     */
    private $ipAddress;
    
    /**
     * Constructeur
     *
     * @param UserModel $user Utilisateur qui vient de se connecter
     * @param string $ipAddress Adresse IP de l'utilisateur
     */
    public function __construct(UserModel $user, string $ipAddress)
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress;
    }
    
    /**
     * Récupérer l'utilisateur connecté
     *
     * @return UserModel
     */
    public function getUser(): UserModel
    {
        return $this->user;
    }
    
    /**
     * Récupérer l'adresse IP
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }
}