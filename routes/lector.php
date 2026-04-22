<?php

declare(strict_types=1);

use App\Lectores\Controllers\LectorController;
use App\Lectores\Repositories\CarreraRepository;
use App\Lectores\Repositories\LectorRepository;
use App\Lectores\Services\LectorService;

/**
 * @var \Bramus\Router\Router $router
 */

$lectorRepository = new LectorRepository();
$carreraRepository = new CarreraRepository();
$lectorService = new LectorService($lectorRepository, $carreraRepository);
$lectorController = new LectorController($lectorService);

$router->get("/lectores/mi-perfil", function () use ($lectorController) {
    $lectorController->getMiPerfil();
});

$router->post("/lectores/{lectorId}/carreras/{carreraId}", function ($lectorId, $carreraId) use ($lectorController) {
    $lectorController->assignCarrera($lectorId, $carreraId);
});

$router->delete("/lectores/{lectorId}/carreras/{carreraId}", function ($lectorId, $carreraId) use ($lectorController) {
    $lectorController->removeCarrera($lectorId, $carreraId);
});
