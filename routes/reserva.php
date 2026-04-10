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

$router->post('/reservas', function () use ($reservaController) {
    $reservaController->addReserva();
});

$router->get('/reservas/{id}', function ($id) use ($reservaController) {
    $reservaController->getReservaById((int) $id);
});

$router->patch('/reservas/{id}/cancelar', function ($id) use ($reservaController) {
    $reservaController->cancelarReserva((int) $id);
});
