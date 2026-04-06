<?php
declare(strict_types=1);

use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Circulacion\Controllers\ReservaController;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Services\ReservaService;

$reservaRepository = new ReservaRepository();
$prestamoRepository = new PrestamoRepository();
$ejemplarRepository = new EjemplarRepository();
$libroRepository = new LibroRepository();
$reservaService = new ReservaService($reservaRepository, $prestamoRepository, $ejemplarRepository, $libroRepository);
$reservaController = new ReservaController($reservaService);

$router->post('/reservas', function () use ($reservaController) {
    $reservaController->addReserva();
});
