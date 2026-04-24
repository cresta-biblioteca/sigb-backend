<?php

declare(strict_types=1);

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Marc21\Marc21ExportController;
use App\Catalogo\Libros\Marc21\Marc21ExportService;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Repositories\PersonaRepository;
use App\Catalogo\Libros\Services\LibroService;
use App\Shared\Database\Connection;

/**
 * @var \Bramus\Router\Router $router
 */

$pdo = Connection::getInstance();
$libroRepository = new LibroRepository($pdo);
$articuloRepository = new ArticuloRepository($pdo);
$personaRepository = new PersonaRepository($pdo);
$libroService = new LibroService($libroRepository, $articuloRepository, $personaRepository, $pdo);
$libroController = new LibroController($libroService);
$marc21ExportService = new Marc21ExportService($libroRepository);
$marc21ExportController = new Marc21ExportController($marc21ExportService);

$router->get('/libros', withRole(['admin', 'auxiliar', 'lector'], function () use ($libroController) {
    $libroController->searchPaginated();
}));

$router->get('/libros/marc21', withRole(['admin', 'auxiliar'], function () use ($marc21ExportController) {
    $marc21ExportController->exportBulk();
}));

$router->get('/libros/{id}/marc21', withRole(['admin', 'auxiliar'], function ($id) use ($marc21ExportController) {
    $marc21ExportController->exportSingle($id);
}));

$router->get('/libros/{id}', withRole(['admin', 'auxiliar', 'lector'], function ($id) use ($libroController) {
    $libroController->getById($id);
}));

$router->post('/libros', withRole(['admin'], function () use ($libroController) {
    $libroController->create();
}));

$router->patch('/libros/{id}', withRole(['admin'], function ($id) use ($libroController) {
    $libroController->updateLibro($id);
}));

$router->delete('/libros/{id}', withRole(['admin'], function ($id) use ($libroController) {
    $libroController->deleteLibro($id);
}));
