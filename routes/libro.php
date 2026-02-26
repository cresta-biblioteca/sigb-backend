<?php

declare(strict_types=1);

use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;

/**
 * @var \Bramus\Router\Router $router
 */

$libroRepository = new LibroRepository();
$libroService = new LibroService($libroRepository);
$libroController = new LibroController($libroService);

$router->get('/libros', function () use ($libroController) {
    $libroController->listAll();
});

$router->get('/libros/{id}', function ($id) use ($libroController) {
    $libroController->showById((int) $id);
});

$router->post('/libros', function () use ($libroController) {
    $libroController->create();
});

$router->put('/libros/{id}', function ($id) use ($libroController) {
    $libroController->update((int) $id);
});

$router->delete('/libros/{id}', function ($id) use ($libroController) {
    $libroController->destroy((int) $id);
});
