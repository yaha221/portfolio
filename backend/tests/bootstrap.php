<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

// KernelTestCase prefers a real APP_ENV env var over phpunit.xml; outside "test" Foundry would reset the dev database
$appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
if ('test' !== $appEnv) {
    throw new LogicException(sprintf('Tests must run with APP_ENV=test, got "%s". Unset APP_ENV in the container environment.', $appEnv));
}

if ($_SERVER['APP_DEBUG']) {
    umask(0o000);
}
