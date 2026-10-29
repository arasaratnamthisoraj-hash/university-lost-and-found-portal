<?php
declare(strict_types=1);

// Environment overrides keep deployment credentials out of source control.
$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'university_lost_found',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
];
if (getenv('APP_ENV') === 'production' && ($config['user'] === 'root' || $config['password'] === '' || getenv('DB_USER') === false || getenv('DB_PASSWORD') === false)) {
    throw new RuntimeException('Production requires an explicitly configured restricted database account and password.');
}
return $config;
