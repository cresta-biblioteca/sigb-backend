<?php

declare(strict_types=1);

use App\Auth\Controllers\AuthController;
use App\Auth\Repositories\AuthRepository;
use App\Auth\Services\AuthService;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Database\Connection;

/**
 * @var \Bramus\Router\Router $router
 */

$router->mount('/auth', function () use ($router) {
    $router->post('/register', function () {
        $pdo = Connection::getInstance();
        $authRepository = new AuthRepository($pdo);
        $lectorRepository = new LectorRepository($pdo);
        $authService = new AuthService($pdo, $authRepository, $lectorRepository);
        $controller = new AuthController($authService);
        $controller->createUser();
    });
});
