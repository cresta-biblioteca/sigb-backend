<?php

declare(strict_types=1);

use App\Lectores\Controllers\LectorController;
use App\Lectores\Repositories\LectorRepository;
use App\Lectores\Services\LectorService;

/**
 * @var \Bramus\Router\Router $router
 */

$lectorRepository = new LectorRepository();
$lectorService = new LectorService($lectorRepository);
$lectorController = new LectorController($lectorService);

$router->get("/lectores/mi-perfil", function () use ($lectorController) {
    $lectorController->getMiPerfil();
});
