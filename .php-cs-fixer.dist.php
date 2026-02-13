<?php

// Configuration PHP-CS-Fixer : définit les règles de style du code

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)           // Cherche dans tout le projet
    ->exclude('var')        // SAUF le dossier var/
    ->exclude('vendor')     // SAUF le dossier vendor/
    ->exclude('node_modules'); // SAUF le dossier node_modules/

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,  // Utilise les règles de style Symfony
    ])
    ->setFinder($finder);
