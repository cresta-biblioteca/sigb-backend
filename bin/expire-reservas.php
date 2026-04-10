#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Services\ReservaService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $service = new ReservaService(
        new ReservaRepository(),
        new PrestamoRepository(),
        new EjemplarRepository(),
        new LibroRepository()
    );

    $service->expirarReservasVencidas();

    echo '[' . date('Y-m-d H:i:s') . '] Reservas vencidas procesadas exitosamente.' . PHP_EOL;
    exit(0);
} catch (\Throwable $e) {
    echo '[' . date('Y-m-d H:i:s') . '] Error al procesar reservas vencidas: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
