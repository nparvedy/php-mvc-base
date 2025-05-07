<?php
namespace Core;

class View
{
    private $layoutDir;
    private $viewsDir;
    private $layout = 'default';

    public function __construct()
    {
        $this->layoutDir = ROOT_PATH . '/src/Views/layouts/';
        $this->viewsDir = ROOT_PATH . '/src/Views/';
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function render($template, array $data = [])
    {
        // Extraire les données pour les rendre accessibles dans la vue
        extract($data);
        
        // Commencer la mise en mémoire tampon
        ob_start();
        
        // Inclure le template
        $templatePath = $this->viewsDir . $template . '.php';
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new \Exception("Vue introuvable : {$templatePath}");
        }
        
        // Récupérer le contenu
        $content = ob_get_clean();
        
        // Charger le layout
        $layoutPath = $this->layoutDir . $this->layout . '.php';
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            throw new \Exception("Layout introuvable : {$layoutPath}");
        }
    }
}