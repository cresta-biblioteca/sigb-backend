<?php
declare(strict_types=1);

use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Shared\Database\Connection;

/**
 * @var \Bramus\Router\Router $router
 */

$pdo = Connection::getInstance();
$libroRepository = new LibroRepository($pdo);
$articuloRepository = new ArticuloRepository($pdo);
$libroService = new LibroService($libroRepository, $articuloRepository, $pdo);
$libroController = new LibroController($libroService);

$router->get('/libros', function () use ($libroController) {
    $libroController->searchPaginated();
});

$router->get('/libros/{id}', function ($id) use ($libroController) {
    $libroController->getById($id);
});

$router->post('/libros', function () use ($libroController) {
    $libroController->create();
});

$router->patch('/libros/{id}', function ($id) use ($libroController) {
    $libroController->updateLibro($id);
});

$router->delete('/libros/{id}', function ($id) use ($libroController) {
    $libroController->deleteLibro($id);
});
