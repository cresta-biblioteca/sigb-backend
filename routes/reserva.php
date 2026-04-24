<?php

declare(strict_types=1);

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Circulacion\Controllers\ReservaController;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Services\ReservaService;

$reservaRepository = new ReservaRepository();
$prestamoRepository = new PrestamoRepository();
$ejemplarRepository = new EjemplarRepository();
$articuloRepository = new ArticuloRepository();
$reservaService = new ReservaService($reservaRepository, $prestamoRepository, $ejemplarRepository, $articuloRepository);
$reservaController = new ReservaController($reservaService);

$router->get('/reservas', withRole(['admin', 'auxiliar'], function () use ($reservaController) {
    $reservaController->getReservas();
}));

$router->get('/lectores/me/reservas', withRole(['lector'], function () use ($reservaController) {
    $reservaController->getMisReservas();
}));

$router->post('/reservas', withRole(['lector'], function () use ($reservaController) {
    $reservaController->addReserva();
}));

$router->get('/reservas/{id}', withRole(['admin', 'auxiliar'], function ($id) use ($reservaController) {
    $reservaController->getReservaById($id);
}));

$router->patch('/reservas/{id}/cancelar', withRole(['admin', 'auxiliar', 'lector'], function ($id) use ($reservaController) {
    $reservaController->cancelarReserva($id);
}));
