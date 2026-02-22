<?php

declare(strict_types=1);
use App\Catalogo\Articulos\Controllers\MateriaController;
use App\Catalogo\Articulos\Repository\MateriaRepository;
use App\Catalogo\Articulos\Services\MateriaService;


/**
 * @var \Bramus\Router\Router $router
 */

$materiaRepository = new MateriaRepository();
$materiaService = new MateriaService($materiaRepository);
$materiaController = new MateriaController($materiaService);

// Busqueda total o por titulo (mediante query param)
$router->get('/materias', function () use ($materiaController) {
    $materiaController->getAll();
});

$router->get('/materias/{id}', function ($id) use ($materiaController) {
    $materiaController->getById($id);
});

$router->post('/materias', function () use ($materiaController) {
    $materiaController->createMateria();
});

$router->put("/materias/{id}", function ($id) use ($materiaController) {
    $materiaController->updateMateria($id);
});

$router->delete("/materias/{id}", function ($id) use ($materiaController) {
    $materiaController->deleteMateria($id);
});