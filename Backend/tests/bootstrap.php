<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Charge .env.test en priorité pour les tests
if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env.test');
} elseif (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env');
}

if (isset($_SERVER['APP_DEBUG']) && $_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Configuration pour les tests
if (getenv('APP_ENV') === 'test') {
    // Désactiver les erreurs fatales pour les tests
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    
    // Configuration de test par défaut si pas de base de données
    if (!getenv('DATABASE_URL')) {
        putenv('DATABASE_URL=postgresql://postgres:password@127.0.0.1:5432/ssa_test?serverVersion=16&charset=utf8');
    }
}
