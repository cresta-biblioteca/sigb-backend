<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Shared\Http\ExceptionHandler;
use App\Shared\Middlewares\CorsMiddleware;
use App\Shared\Middlewares\JwtMiddleware;
use App\Shared\Security\JwtTokenProvider;
use Bramus\Router\Router;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

set_exception_handler([new ExceptionHandler(), 'handle']);

$corsMiddleware = new CorsMiddleware();
$corsMiddleware->handle();

$router = new Router();

$router->setBasePath('/api/v1/');

$router->set404(function () {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['message' => 'Ruta no encontrada']);
});

$jwtMiddleware = new JwtMiddleware(new JwtTokenProvider());

$router->before(
    'GET|POST|PUT|DELETE|PATCH',
    '/(?!auth\/login|auth\/register|docs\/).*',
    function () use ($jwtMiddleware) {
        if (!$jwtMiddleware->handle()) {
            exit();
        }
    }
);

require_once __DIR__ . '/../routes/auth.php';
require_once __DIR__ . '/../routes/articulo.php';
require_once __DIR__ . '/../routes/libro.php';
require_once __DIR__ . '/../routes/ejemplar.php';
require_once __DIR__ . '/../routes/carrera.php';
require_once __DIR__ . '/../routes/tema.php';
require_once __DIR__ . '/../routes/tipoDocumento.php';
require_once __DIR__ . '/../routes/libro.php';
require_once __DIR__ . '/../routes/docs.php';
require_once __DIR__ . '/../routes/prestamo.php';
require_once __DIR__ . '/../routes/tipoPrestamo.php';
require_once __DIR__ . '/../routes/reserva.php';
require_once __DIR__ . '/../routes/lector.php';

$router->run();
