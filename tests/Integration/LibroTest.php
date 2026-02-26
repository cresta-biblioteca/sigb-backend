<?php

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;
use Tests\Helper\TestStreamWrapper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $libroRepository = new LibroRepository($this->pdo);
    $articuloRepository = new ArticuloRepository($this->pdo);
    $service = new LibroService($libroRepository, $articuloRepository, $this->pdo);

    $this->controller = new LibroController($service);

    $_GET = [];
});

afterEach(function () {
    $_GET = [];
});

function withJsonInputLibro(array $payload, callable $callback): void
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

test('create crea libro con articulo existente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo Libro Test',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    withJsonInputLibro([
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'export_marc' => 'MARC-TEST',
        'autor' => 'Autor Integracion',
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['articulo_id'])->toBe($articuloId)
        ->and($response['data']['isbn'])->toBe('9780132350884');

    expect($this->recordExists('libro', ['articulo_id' => $articuloId]))->toBeTrue();
});

test('listAll filtra libros por titulo de articulo', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo prueba',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'autor' => 'Autor',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
        'export_marc' => 'MARC',
    ]);

    $_GET = ['titulo' => 'Articulo prueba', 'page' => '1', 'per_page' => '10'];

    ob_start();
    $this->controller->listAll();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data'])->toHaveCount(1)
        ->and($response['data'][0]['articulo']['titulo'])->toBe('Articulo prueba');
});

test('create falla con isbn duplicado', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId1 = $this->insertInto('articulo', [
        'titulo' => 'Articulo Uno',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $articuloId2 = $this->insertInto('articulo', [
        'titulo' => 'Articulo Dos',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId1,
        'isbn' => '9780132350884',
        'autor' => 'Autor',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
        'export_marc' => 'MARC',
    ]);

    withJsonInputLibro([
        'articulo_id' => $articuloId2,
        'isbn' => '9780132350884',
        'export_marc' => 'MARC-OTRO',
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(true)
        ->and($response)->toHaveKey('message');
});
