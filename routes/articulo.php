<?php

declare(strict_types=1);

use App\Catalogo\Articulos\Controllers\ArticuloController;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Articulos\Services\ArticuloService;

/**
 * @var \Bramus\Router\Router $router
 */

$articuloRepository = new ArticuloRepository();
$articuloService = new ArticuloService($articuloRepository);
$articuloController = new ArticuloController($articuloService);

$router->get('/articulos', function () use ($articuloController) {
    $articuloController->getAll();
});

$router->get('/articulos/{id}', function ($id) use ($articuloController) {
    $articuloController->getById($id);
});

$router->patch('/articulos/{id}', function ($id) use ($articuloController) {
    $articuloController->patchArticulo($id);
});

$router->delete('/articulos/{id}', function ($id) use ($articuloController) {
    $articuloController->deleteArticulo($id);
});
