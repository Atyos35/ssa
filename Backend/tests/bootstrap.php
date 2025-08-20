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
        // Utiliser la base de données Docker par défaut
        putenv('DATABASE_URL=postgresql://ssa:ssa@127.0.0.1:5432/ssa?serverVersion=16&charset=utf8');
    }
    
    // Configuration JWT par défaut pour les tests
    if (!getenv('JWT_SECRET_KEY')) {
        putenv('JWT_SECRET_KEY=test_jwt_secret_key_very_long_for_testing');
    }
    
    // Configuration mailer par défaut pour les tests
    if (!getenv('MAILER_DSN')) {
        putenv('MAILER_DSN=null://null');
    }
    
    // Configuration messenger par défaut pour les tests
    if (!getenv('MESSENGER_TRANSPORT_DSN')) {
        putenv('MESSENGER_TRANSPORT_DSN=in-memory://');
    }
}
