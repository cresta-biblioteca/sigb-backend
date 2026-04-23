<?php

declare(strict_types=1);

use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Circulacion\Controllers\PrestamoController;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Repositories\TipoPrestamoRepository;
use App\Circulacion\Services\PrestamoService;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Database\Connection;

$prestamoRepository = new PrestamoRepository();
$reservaRepository = new ReservaRepository();
$tipoPrestamoRepository = new TipoPrestamoRepository();
$ejemplarRepository = new EjemplarRepository();
$lectorRepository = new LectorRepository();

$prestamoService = new PrestamoService(
    Connection::getInstance(),
    $prestamoRepository,
    $reservaRepository,
    $tipoPrestamoRepository,
    $ejemplarRepository,
    $lectorRepository
);

$prestamoController = new PrestamoController($prestamoService);

$router->get('/lectores/me/prestamos', withRole(['lector'], function () use ($prestamoController) {
    $prestamoController->getMisPrestamos();
}));

$router->get('/lector/{lectorId}/prestamos', withRole(['admin'], function ($lectorId) use ($prestamoController) {
    $prestamoController->getByLector($lectorId);
}));

$router->get('/prestamos', withRole(['admin', 'auxiliar'], function () use ($prestamoController) {
    $prestamoController->getAll();
}));

$router->get('/prestamos/{id}', withRole(['admin', 'auxiliar'], function ($id) use ($prestamoController) {
    $prestamoController->getById($id);
}));

$router->post('/prestamos', withRole(['admin', 'auxiliar'], function () use ($prestamoController) {
    $prestamoController->createPrestamo();
}));

$router->patch('/prestamos/{id}/devolver', withRole(['admin'], function ($id) use ($prestamoController) {
    $prestamoController->return($id);
}));

$router->patch('/prestamos/{id}/renovar', withRole(['admin', 'auxiliar', 'lector'], function ($id) use ($prestamoController) {
    $prestamoController->renew($id);
}));
