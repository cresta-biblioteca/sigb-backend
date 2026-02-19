<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Bramus\Router\Router;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$router = new Router();

$router->setBasePath('/api/v1/');

$router->set404(function () {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['message' => 'Ruta no encontrada']);
});

// TODO: Agregar middleware JWT para rutas protegidas
// $router->before('GET|POST|PUT|DELETE|PATCH', '/.*', function () { JwtMiddleware::handle(); });

require_once __DIR__ . '/../routes/auth.php';

$router->run();
