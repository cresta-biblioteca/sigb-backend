<?php

use App\Catalogo\Ejemplares\Controllers\EjemplarController;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Ejemplares\Services\EjemplarService;
use Tests\Helper\TestStreamWrapper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $repository = new EjemplarRepository($this->pdo);
    $service = new EjemplarService($repository);
    $this->controller = new EjemplarController($service);

    $_GET = [];
});

afterEach(function () {
    $_GET = [];
});

function withJsonInputEjemplar(array $payload, callable $callback): void
{
    $input = json_encode($payload, JSON_THROW_ON_ERROR);

    stream_wrapper_unregister('php');
    stream_wrapper_register('php', TestStreamWrapper::class);
    TestStreamWrapper::$data = $input;

    try {
        $callback();
    } finally {
        stream_wrapper_restore('php');
    }
}

test('create crea ejemplar correctamente', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo Ejemplar',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    withJsonInputEjemplar([
        'articulo_id' => $articuloId,
        'codigo_barras' => '1234567890123',
        'habilitado' => true,
    ], function () {
        ob_start();
        $this->controller->createEjemplar();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['articulo_id'])->toBe($articuloId)
        ->and($response['data']['codigo_barras'])->toBe('1234567890123');

    expect($this->recordExists('ejemplar', ['id' => $response['data']['id']]))->toBeTrue();
});

test('getByArticuloId devuelve ejemplares del articulo', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo Ejemplar',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('ejemplar', [
        'codigo_barras' => '10001',
        'habilitado' => 1,
        'articulo_id' => $articuloId,
    ]);

    $this->insertInto('ejemplar', [
        'codigo_barras' => '10002',
        'habilitado' => 0,
        'articulo_id' => $articuloId,
    ]);

    ob_start();
    $this->controller->getByArticuloId($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data'])->toHaveCount(2);
});

test('deshabilitar cambia estado del ejemplar', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo Ejemplar',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $ejemplarId = $this->insertInto('ejemplar', [
        'codigo_barras' => '20001',
        'habilitado' => 1,
        'articulo_id' => $articuloId,
    ]);

    ob_start();
    $this->controller->deshabilitar($ejemplarId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['habilitado'])->toBe(false);

    $dbEjemplar = $this->findById('ejemplar', $ejemplarId);
    expect((int) $dbEjemplar['habilitado'])->toBe(0);
});
