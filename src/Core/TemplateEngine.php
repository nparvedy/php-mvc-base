<?php
namespace Core;

class TemplateEngine
{
    /**
     * Répertoire des vues
     * @var string
     */
    protected $viewsPath;
    
    /**
     * Répertoire de cache
     * @var string
     */
    protected $cachePath;
    
    /**
     * Mode de débogage
     * @var bool
     */
    protected $debug;
    
    /**
     * Variables partagées entre tous les templates
     * @var array
     */
    protected $shared = [];
    
    /**
     * Extensions de fichiers de vues
     * @var string
     */
    protected $fileExtension = '.php';
    
    /**
     * Instance du moteur de template
     * @var TemplateEngine
     */
    protected static $instance;
    
    /**
     * Constructeur
     *
     * @param string $viewsPath Chemin vers les fichiers de vues
     * @param string $cachePath Chemin vers le cache des vues compilées
     * @param bool $debug Mode débogage
     */
    public function __construct($viewsPath = null, $cachePath = null, $debug = false)
    {
        $this->viewsPath = $viewsPath ?: ROOT_PATH . '/src/Views';
        $this->cachePath = $cachePath ?: ROOT_PATH . '/var/cache/views';
        $this->debug = $debug;
        
        // Créer le répertoire de cache s'il n'existe pas
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
        
        self::$instance = $this;
    }
    
    /**
     * Récupérer l'instance du moteur de template
     *
     * @return TemplateEngine
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Définir le chemin vers les vues
     *
     * @param string $path
     * @return $this
     */
    public function setViewsPath($path)
    {
        $this->viewsPath = $path;
        return $this;
    }
    
    /**
     * Définir le chemin vers le cache
     *
     * @param string $path
     * @return $this
     */
    public function setCachePath($path)
    {
        $this->cachePath = $path;
        return $this;
    }
    
    /**
     * Définir l'extension des fichiers de vues
     *
     * @param string $extension
     * @return $this
     */
    public function setFileExtension($extension)
    {
        $this->fileExtension = $extension;
        return $this;
    }
    
    /**
     * Activer ou désactiver le mode débogage
     *
     * @param bool $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }
    
    /**
     * Partager des variables avec tous les templates
     *
     * @param string|array $key Nom de la variable ou tableau de variables
     * @param mixed $value Valeur de la variable (si $key est une chaîne)
     * @return $this
     */
    public function share($key, $value = null)
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Rendre une vue
     *
     * @param string $view Nom de la vue
     * @param array $data Variables à passer à la vue
     * @return string Contenu de la vue rendue
     * @throws \Exception
     */
    public function render($view, array $data = [])
    {
        // Fusionner les variables partagées avec les variables spécifiques
        $data = array_merge($this->shared, $data);
        
        // Résoudre le chemin complet de la vue
        $viewPath = $this->resolvePath($view);
        
        if (!file_exists($viewPath)) {
            throw new \Exception("Vue non trouvée: {$view}");
        }
        
        // Générer le nom du fichier cache
        $cachePath = $this->getCachePath($viewPath);
        
        // Compiler la vue si nécessaire
        if ($this->shouldCompileView($viewPath, $cachePath)) {
            $this->compileView($viewPath, $cachePath);
        }
        
        // Extraire les données pour les rendre accessibles dans la vue
        extract($data);
        
        // Capturer la sortie
        ob_start();
        
        require $cachePath;
        
        return ob_get_clean();
    }
    
    /**
     * Vérifier si une vue doit être compilée
     *
     * @param string $viewPath Chemin vers le fichier de vue
     * @param string $cachePath Chemin vers le fichier cache
     * @return bool True si la vue doit être compilée
     */
    protected function shouldCompileView($viewPath, $cachePath)
    {
        // En mode débogage, toujours compiler
        if ($this->debug) {
            return true;
        }
        
        // Si le fichier cache n'existe pas, compiler
        if (!file_exists($cachePath)) {
            return true;
        }
        
        // Compiler si la vue a été modifiée depuis la dernière compilation
        return filemtime($viewPath) > filemtime($cachePath);
    }
    
    /**
     * Obtenir le chemin vers le fichier cache d'une vue
     *
     * @param string $viewPath Chemin vers le fichier de vue
     * @return string Chemin vers le fichier cache
     */
    protected function getCachePath($viewPath)
    {
        $relativePath = str_replace(['/', '\\'], '_', ltrim(str_replace($this->viewsPath, '', $viewPath), '/\\'));
        return $this->cachePath . '/' . $relativePath . '.compiled.php';
    }
    
    /**
     * Résoudre le chemin complet d'une vue
     *
     * @param string $view Nom de la vue
     * @return string Chemin complet
     */
    protected function resolvePath($view)
    {
        $view = str_replace('.', '/', $view);
        
        // Si l'extension n'est pas fournie, l'ajouter
        if (pathinfo($view, PATHINFO_EXTENSION) === '') {
            $view .= $this->fileExtension;
        }
        
        return $this->viewsPath . '/' . $view;
    }
    
    /**
     * Compiler une vue
     *
     * @param string $viewPath Chemin vers le fichier de vue
     * @param string $cachePath Chemin vers le fichier cache
     */
    protected function compileView($viewPath, $cachePath)
    {
        // Lire le contenu de la vue
        $content = file_get_contents($viewPath);
        
        // Compiler les directives du template
        $content = $this->compileDirectives($content);
        
        // Écrire le contenu compilé dans le fichier cache
        file_put_contents($cachePath, $content);
    }
    
    /**
     * Compiler les directives du template
     *
     * @param string $content Contenu à compiler
     * @return string Contenu compilé
     */
    protected function compileDirectives($content)
    {
        // Compiler les commentaires
        $content = preg_replace('/{{--(.+?)--}}/s', '<?php /* $1 */ ?>', $content);
        
        // Compiler les instructions PHP
        $content = preg_replace('/@php(.*?)@endphp/s', '<?php $1 ?>', $content);
        
        // Compiler les structures de contrôle
        $content = $this->compileControlStructures($content);
        
        // Compiler les inclusions
        $content = preg_replace('/@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/', '<?php echo $this->render("$1", $2 ?? []); ?>', $content);
        
        // Compiler les expressions échappées
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', $content);
        
        // Compiler les expressions non échappées
        $content = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }
    
    /**
     * Compiler les structures de contrôle
     *
     * @param string $content Contenu à compiler
     * @return string Contenu compilé
     */
    protected function compileControlStructures($content)
    {
        $patterns = [
            // If
            '/@if\s*\((.*?)\)/' => '<?php if ($1): ?>',
            '/@elseif\s*\((.*?)\)/' => '<?php elseif ($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
            
            // Foreach
            '/@foreach\s*\((.*?)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            
            // For
            '/@for\s*\((.*?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            
            // While
            '/@while\s*\((.*?)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
            
            // Switch
            '/@switch\s*\((.*?)\)/' => '<?php switch ($1): ?>',
            '/@case\s*\((.*?)\)/' => '<?php case $1: ?>',
            '/@default/' => '<?php default: ?>',
            '/@break/' => '<?php break; ?>',
            '/@endswitch/' => '<?php endswitch; ?>',
            
            // Unless (inverse of if)
            '/@unless\s*\((.*?)\)/' => '<?php if (! ($1)): ?>',
            '/@endunless/' => '<?php endif; ?>',
            
            // isset
            '/@isset\s*\((.*?)\)/' => '<?php if (isset($1)): ?>',
            '/@endisset/' => '<?php endif; ?>',
            
            // empty
            '/@empty\s*\((.*?)\)/' => '<?php if (empty($1)): ?>',
            '/@endempty/' => '<?php endif; ?>',
            
            // auth
            '/@auth/' => '<?php if (isset($currentUser) && $currentUser): ?>',
            '/@endauth/' => '<?php endif; ?>',
            
            // guest
            '/@guest/' => '<?php if (!isset($currentUser) || !$currentUser): ?>',
            '/@endguest/' => '<?php endif; ?>',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        return $content;
    }
}