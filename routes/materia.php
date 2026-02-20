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

$router->get('/materias', function () use ($materiaController) {
    $materiaController->getAll();
});

$router->post('/materias', function () use ($materiaController) {
    $materiaController->createMateria();
});

$router->put("/materias/{id}", function($id) use($materiaController) {
    $materiaController->updateMateria($id);
});