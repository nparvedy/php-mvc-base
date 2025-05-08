<?php
namespace Events;

use Core\Event\Event;
use Models\UserModel;

/**
 * Événement déclenché lorsqu'un nouvel utilisateur s'inscrit
 */
class UserRegisteredEvent extends Event
{
    /**
     * Utilisateur qui vient de s'inscrire
     * @var UserModel
     */
    protected $user;
    
    /**
     * Constructeur
     * 
     * @param UserModel $user L'utilisateur qui vient de s'inscrire
     */
    public function __construct(UserModel $user)
    {
        parent::__construct();
        $this->user = $user;
    }
    
    /**
     * Obtenir l'utilisateur inscrit
     * 
     * @return UserModel
     */
    public function getUser()
    {
        return $this->user;
    }
}