<?php

declare(strict_types=1);

use App\Circulacion\Controllers\TipoPrestamoController;
use App\Circulacion\Repositories\TipoPrestamoRepository;
use App\Circulacion\Services\TipoPrestamoService;

$tipoPrestamoRepository = new TipoPrestamoRepository();
$tipoPrestamoService = new TipoPrestamoService($tipoPrestamoRepository);
$tipoPrestamoController = new TipoPrestamoController($tipoPrestamoService);

$router->get('/tipos-prestamos', withRole(['admin', 'auxiliar'], function () use ($tipoPrestamoController) {
    $tipoPrestamoController->getAll();
}));

$router->get('/tipos-prestamos/{id}', withRole(['admin', 'auxiliar'], function ($id) use ($tipoPrestamoController) {
    $tipoPrestamoController->getById($id);
}));

$router->post('/tipos-prestamos', withRole(['admin'], function () use ($tipoPrestamoController) {
    $tipoPrestamoController->createTipoPrestamo();
}));

$router->patch('/tipos-prestamos/{id}/deshabilitar', withRole(['admin'], function ($id) use ($tipoPrestamoController) {
    $tipoPrestamoController->disableTipoPrestamo($id);
}));

$router->patch('/tipos-prestamos/{id}/habilitar', withRole(['admin'], function ($id) use ($tipoPrestamoController) {
    $tipoPrestamoController->enableTipoPrestamo($id);
}));

$router->patch('/tipos-prestamos/{id}', withRole(['admin'], function ($id) use ($tipoPrestamoController) {
    $tipoPrestamoController->updateTipoPrestamo($id);
}));
