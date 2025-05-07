<?php 
use Core\Helpers; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mon Application MVC' ?></title>
    <link rel="stylesheet" href="<?= Helpers::url('css/style.css') ?>">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="<?= Helpers::url() ?>">Accueil</a></li>
                <li><a href="<?= Helpers::url('about') ?>">À propos</a></li>
                <li><a href="<?= Helpers::url('contact') ?>">Contact</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <?= $content ?>
    </main>
    
    <footer>
        <p>&copy; <?= date('Y') ?> - Mon Application MVC PHP</p>
    </footer>
    
    <script src="<?= Helpers::url('js/app.js') ?>"></script>
</body>
</html>