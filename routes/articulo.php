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

$router->get('/articulos', withRole(['admin', 'auxiliar', 'lector'], function () use ($articuloController) {
    $articuloController->getAll();
}));

$router->get('/articulos/{idArticulo}/temas', withRole(['admin', 'lector'], function ($idArticulo) use ($articuloController) {
    $articuloController->getTemaTitlesByArticulo($idArticulo);
}));

$router->post('/articulos/{idArticulo}/temas/{idTema}', withRole(['admin'], function ($idArticulo, $idTema) use ($articuloController) {
    $articuloController->addTemaToArticulo($idArticulo, $idTema);
}));

$router->delete('/articulos/{idArticulo}/temas/{idTema}', withRole(['admin'], function ($idArticulo, $idTema) use ($articuloController) {
    $articuloController->deleteTemaFromArticulo($idArticulo, $idTema);
}));

$router->get('/articulos/{id}', withRole(['admin', 'auxiliar', 'lector'], function ($id) use ($articuloController) {
    $articuloController->getById($id);
}));

$router->patch('/articulos/{id}', withRole(['admin'], function ($id) use ($articuloController) {
    $articuloController->patchArticulo($id);
}));

$router->delete('/articulos/{id}', withRole(['admin'], function ($id) use ($articuloController) {
    $articuloController->deleteArticulo($id);
}));
