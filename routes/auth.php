<?php

declare(strict_types=1);

use App\Auth\Controllers\AuthController;
use App\Auth\Repositories\AuthRepository;
use App\Auth\Repositories\RoleRepository;
use App\Auth\Services\AuthService;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Database\Connection;
use App\Shared\Security\JwtTokenProvider;
use App\Shared\Security\PasswordEncoder;
use Bramus\Router\Router;

/**
 * @var Router $router
 */

$pdo = Connection::getInstance();
$authRepository = new AuthRepository($pdo);
$lectorRepository = new LectorRepository($pdo);
$roleRepository = new RoleRepository($pdo);
$jwtTokenProvider = new JwtTokenProvider();
$passwordEncoder = new PasswordEncoder();
$authService = new AuthService($pdo, $authRepository, $lectorRepository, $roleRepository, $jwtTokenProvider, $passwordEncoder);
$controller = new AuthController($authService);

$router->mount('/auth', function () use ($router, $controller) {
    $router->post('/register', function () use ($controller) {
        $controller->createUser();
    });

    $router->post('/login', function () use ($controller) {
        $controller->login();
    });
});
