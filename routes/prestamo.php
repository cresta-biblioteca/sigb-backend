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

// Rutas específicas primero (para evitar conflicto con {id})
$router->get('/lector/{lectorId}/prestamos', function ($lectorId) use ($prestamoController) {
    $prestamoController->getByLector($lectorId);
});

$router->get('/prestamos', function () use ($prestamoController) {
    $prestamoController->getAll();
});

$router->get('/prestamos/{id}', function ($id) use ($prestamoController) {
    $prestamoController->getById($id);
});

$router->post('/prestamos', function () use ($prestamoController) {
    $prestamoController->createPrestamo();
});

$router->patch('/prestamos/{id}/devolver', function ($id) use ($prestamoController) {
    $prestamoController->return($id);
});

$router->patch('/prestamos/{id}/renovar', function ($id) use ($prestamoController) {
    $prestamoController->renew($id);
});
