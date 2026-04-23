<?php

declare(strict_types=1);

use App\Lectores\Controllers\CarreraController;
use App\Lectores\Repositories\CarreraRepository;
use App\Lectores\Services\CarreraService;

/**
 * @var \Bramus\Router\Router $router
 */

$carreraRepository = new CarreraRepository();
$carreraService = new CarreraService($carreraRepository);
$carreraController = new CarreraController($carreraService);

$router->get("/carreras", withRole(['admin', 'auxiliar', 'lector'], function () use ($carreraController) {
    $carreraController->getAll();
}));

$router->get("/carreras/{id}", withRole(['admin', 'auxiliar', 'lector'], function ($id) use ($carreraController) {
    $carreraController->getById($id);
}));

$router->post("/carreras", withRole(['admin'], function () use ($carreraController) {
    $carreraController->createCarrera();
}));

$router->patch("/carreras/{id}", withRole(['admin'], function ($id) use ($carreraController) {
    $carreraController->updateCarrera($id);
}));

$router->delete("/carreras/{id}", withRole(['admin'], function ($id) use ($carreraController) {
    $carreraController->deleteCarrera($id);
}));
