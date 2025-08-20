<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Charge .env.test en priorité pour les tests
if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env.test');
} elseif (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env');
}

// Configuration de test par défaut
if (getenv('APP_ENV') === 'test') {
    if (!getenv('DATABASE_URL')) {
        putenv('DATABASE_URL=postgresql://ssa:ssa@127.0.0.1:5432/ssa?serverVersion=16&charset=utf8');
    }
    if (!getenv('JWT_SECRET_KEY')) {
        putenv('JWT_SECRET_KEY=test_jwt_secret_key_very_long_for_testing');
    }
    if (!getenv('MAILER_DSN')) {
        putenv('MAILER_DSN=null://null');
    }
    if (!getenv('MESSENGER_TRANSPORT_DSN')) {
        putenv('MESSENGER_TRANSPORT_DSN=in-memory://');
    }
}
