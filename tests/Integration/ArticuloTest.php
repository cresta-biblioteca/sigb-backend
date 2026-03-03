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

test('getById obtiene articulo por id correctamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo Integracion',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    ob_start();
    $this->controller->getById($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['titulo'])->toBe('Articulo Integracion')
        ->and($response['tipo_documento_id'])->toBe($tipoDocumentoId);
});

test('getAll lista articulos correctamente', function () {
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

    ob_start();
    $this->controller->getAll();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveCount(2)
        ->and($response[0])->toHaveKey('titulo')
        ->and($response[1])->toHaveKey('titulo');
});

test('getById devuelve 404 para articulo inexistente', function () {
    ob_start();
    $this->controller->getById(99999);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('message');
});

test('patchArticulo actualiza articulo correctamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Titulo Original',
        'anio_publicacion' => 2023,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    withJsonInput([
        'titulo' => 'Titulo Actualizado',
        'idioma' => 'en'
    ], function () use ($articuloId) {
        ob_start();
        $this->controller->patchArticulo($articuloId);
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['titulo'])->toBe('Titulo Actualizado')
        ->and($response['anio_publicacion'])->toBe(2023)
        ->and($response['idioma'])->toBe('en');
});

test('deleteArticulo elimina articulo correctamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo a Eliminar',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    ob_start();
    $this->controller->deleteArticulo($articuloId);
    $output = ob_get_clean();

    expect($output)->toBe('');
    expect(http_response_code())->toBe(204);

    expect($this->recordExists('articulo', ['id' => $articuloId]))->toBeFalse();
});
