<?php

use App\Catalogo\Articulos\Controllers\ArticuloController;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Articulos\Services\ArticuloService;
use Tests\Helper\TestStreamWrapper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $repository = new ArticuloRepository($this->pdo);
    $service = new ArticuloService($repository);
    $this->controller = new ArticuloController($service);

    $_GET = [];
});

afterEach(function () {
    $_GET = [];
});

function withJsonInput(array $payload, callable $callback): void
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

test('create crea articulo correctamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    withJsonInput([
        'titulo' => 'Articulo Integracion',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['titulo'])->toBe('Articulo Integracion')
        ->and($response['data']['tipo_documento_id'])->toBe($tipoDocumentoId);

    expect($this->recordExists('articulo', ['id' => $response['data']['id']]))->toBeTrue();
});

test('getAll lista articulos paginados', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $this->insertInto('articulo', [
        'titulo' => 'Articulo A',
        'anio_publicacion' => 2020,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('articulo', [
        'titulo' => 'Articulo B',
        'anio_publicacion' => 2021,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $_GET = ['page' => '1', 'per_page' => '10'];

    ob_start();
    $this->controller->getAll();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data'])->toHaveCount(2)
        ->and($response['pagination']['total'])->toBe(2);
});

test('showById devuelve 404 para articulo inexistente', function () {
    ob_start();
    $this->controller->showById(99999);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(true)
        ->and($response)->toHaveKey('message');
});
