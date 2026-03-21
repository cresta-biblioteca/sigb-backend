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

$router->get('/articulos/{idArticulo}/materias', function ($idArticulo) use ($articuloController) {
    $articuloController->getMateriaTitlesByArticulo($idArticulo);
});

$router->post('/articulos/{idArticulo}/materias/{idMateria}', function ($idArticulo, $idMateria) use ($articuloController) {
    $articuloController->addMateriaToArticulo($idArticulo, $idMateria);
});

$router->delete('/articulos/{idArticulo}/materias/{idMateria}', function ($idArticulo, $idMateria) use ($articuloController) {
    $articuloController->deleteMateriaFromArticulo($idArticulo, $idMateria);
});

$router->get('/articulos/{idArticulo}/temas', function ($idArticulo) use ($articuloController) {
    $articuloController->getTemaTitlesByArticulo($idArticulo);
});

$router->post('/articulos/{idArticulo}/temas/{idTema}', function ($idArticulo, $idTema) use ($articuloController) {
    $articuloController->addTemaToArticulo($idArticulo, $idTema);
});

$router->delete('/articulos/{idArticulo}/temas/{idTema}', function ($idArticulo, $idTema) use ($articuloController) {
    $articuloController->deleteTemaFromArticulo($idArticulo, $idTema);
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
