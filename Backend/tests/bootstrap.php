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
