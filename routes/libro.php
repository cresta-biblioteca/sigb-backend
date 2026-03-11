<?php
declare(strict_types=1);

use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Repository\ArticuloRepository;

$libroRepository = new LibroRepository();
$articuloRepository = new ArticuloRepository();

$libroService = new LibroService($libroRepository);
$articuloService = new ArticuloService($articuloRepository);

$libroController = new LibroController($libroService, $articuloService);

$router->get('/libros', function () use ($libroController) {
    $libroController->getAll();
});

// Rutas de búsqueda (ANTES de /libros/{id} para evitar conflictos)
$router->get('/libros/search', function () use ($libroController) {
    $libroController->search();
});

$router->get('/libros/search/paginated', function () use ($libroController) {
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
