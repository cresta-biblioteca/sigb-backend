<?php
declare(strict_types=1);

use App\Catalogo\Articulos\Repository\MateriaRepository;
use App\Lectores\Controllers\CarreraController;
use App\Lectores\Repositories\CarreraRepository;
use App\Lectores\Services\CarreraService;

/**
 * @var \Bramus\Router\Router $router
 */

$carreraRepository = new CarreraRepository();
$materiaRepository = new MateriaRepository();
$carreraService = new CarreraService($carreraRepository, $materiaRepository);
$carreraController = new CarreraController($carreraService);

$router->get("/carreras", function() use($carreraController) {
    $carreraController->getAll();
});
$router->get("/carreras/{id}/materias", function($id) use($carreraController) {
    $carreraController->getMateriasByCarrera($id);
});

$router->get("/carreras/{id}", function($id) use($carreraController) {
    $carreraController->getById($id);
});

$router->post("/carreras", function() use($carreraController) {
    $carreraController->createCarrera();
});

$router->post("/carreras/{idCarrera}/materias/{idMateria}", function($idCarrera, $idMateria) use($carreraController) {
    $carreraController->addMateriaToCarrera($idCarrera, $idMateria);
});

$router->patch("/carreras/{id}", function($id) use($carreraController) {
    $carreraController->updateCarrera($id);
});

$router->delete("/carreras/{idCarrera}/materias/{idMateria}", function($idCarrera, $idMateria) use($carreraController) {
    $carreraController->deleteMateriaFromCarrera($idCarrera, $idMateria);
});

$router->delete("/carreras/{id}", function($id) use($carreraController) {
    $carreraController->deleteCarrera($id);
});
