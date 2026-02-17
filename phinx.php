<?php

// Cargar variables de entorno
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'name' => $_ENV['DB_DATABASE'] ?? 'sigb_db',
            'user' => $_ENV['DB_USERNAME'] ?? 'sigb_user',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'secret',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'name' => $_ENV['DB_DATABASE'] ?? 'sigb_db',
            'user' => $_ENV['DB_USERNAME'] ?? 'sigb_user',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'secret',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'name' => 'sigb_db_test',
            'user' => $_ENV['DB_USERNAME'] ?? 'sigb_user',
            'pass' => $_ENV['DB_PASSWORD'] ?? 'secret',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'charset' => 'utf8mb4',
        ]
    ],
    'version_order' => 'creation'
];
