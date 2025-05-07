<?php
namespace Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return $this->render('home/index', [
            'title' => 'Accueil',
            'content' => 'Bienvenue sur notre application MVC PHP'
        ]);
    }

    public function about()
    {
        return $this->render('home/about', [
            'title' => 'À propos',
            'content' => 'Informations sur notre application MVC PHP personnalisée'
        ]);
    }
}