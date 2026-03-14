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

test('addMateriaToArticulo agrega materia al articulo correctamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo con materia',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $materiaId = $this->insertInto('materia', [
        'titulo' => 'Contabilidad',
    ]);

    ob_start();
    $this->controller->addMateriaToArticulo((string) $articuloId, (string) $materiaId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toBe('La materia ha sido agregada al artículo');
    expect($this->recordExists('materia_articulo', [
        'articulo_id' => $articuloId,
        'materia_id' => $materiaId,
    ]))->toBeTrue();
});

test('addMateriaToArticulo devuelve 409 cuando materia ya esta agregada', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo con materia repetida',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $materiaId = $this->insertInto('materia', [
        'titulo' => 'Matematica',
    ]);

    $this->insertInto('materia_articulo', [
        'articulo_id' => $articuloId,
        'materia_id' => $materiaId,
    ]);

    ob_start();
    $this->controller->addMateriaToArticulo((string) $articuloId, (string) $materiaId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toContain('ya está agregada');
    expect(http_response_code())->toBe(409);
});

test('addMateriaToArticulo devuelve 404 cuando materia no existe', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo sin materia existente',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    ob_start();
    $this->controller->addMateriaToArticulo((string) $articuloId, '99999');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toContain('Materia');
    expect(http_response_code())->toBe(404);
});

test('deleteMateriaFromArticulo elimina materia del articulo correctamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo para eliminar materia',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $materiaId = $this->insertInto('materia', [
        'titulo' => 'Administracion',
    ]);

    $this->insertInto('materia_articulo', [
        'articulo_id' => $articuloId,
        'materia_id' => $materiaId,
    ]);

    ob_start();
    $this->controller->deleteMateriaFromArticulo((string) $articuloId, (string) $materiaId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toBe('La materia ha sido eliminada del artículo');
    expect($this->recordExists('materia_articulo', [
        'articulo_id' => $articuloId,
        'materia_id' => $materiaId,
    ]))->toBeFalse();
});

test('deleteMateriaFromArticulo devuelve 409 cuando la relacion ya fue eliminada', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo sin relacion de materia',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $materiaId = $this->insertInto('materia', [
        'titulo' => 'Economia',
    ]);

    ob_start();
    $this->controller->deleteMateriaFromArticulo((string) $articuloId, (string) $materiaId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toContain('no pertenece al artículo');
    expect(http_response_code())->toBe(409);
});

test('deleteMateriaFromArticulo devuelve 404 cuando el articulo no existe', function () {
    $materiaId = $this->insertInto('materia', [
        'titulo' => 'Finanzas',
    ]);

    ob_start();
    $this->controller->deleteMateriaFromArticulo('99999', (string) $materiaId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toContain('Articulo');
    expect(http_response_code())->toBe(404);
});

test('getMateriaTitlesByArticulo devuelve array de titulos de materias', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo con multiples materias',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $materiaId1 = $this->insertInto('materia', [
        'titulo' => 'Costos',
    ]);

    $materiaId2 = $this->insertInto('materia', [
        'titulo' => 'Estadistica',
    ]);

    $this->insertInto('materia_articulo', [
        'articulo_id' => $articuloId,
        'materia_id' => $materiaId1,
    ]);

    $this->insertInto('materia_articulo', [
        'articulo_id' => $articuloId,
        'materia_id' => $materiaId2,
    ]);

    ob_start();
    $this->controller->getMateriaTitlesByArticulo((string) $articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toBeArray();
    expect($response)->toContain('Costos');
    expect($response)->toContain('Estadistica');
});

test('getMateriaTitlesByArticulo devuelve 404 para articulo inexistente', function () {
    ob_start();
    $this->controller->getMateriaTitlesByArticulo('99999');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toContain('Articulo');
    expect(http_response_code())->toBe(404);
});

test('addMateriaToArticulo devuelve 400 cuando idArticulo no es numerico valido', function () {
    $materiaId = $this->insertInto('materia', [
        'titulo' => 'Planeamiento',
    ]);

    ob_start();
    $this->controller->addMateriaToArticulo('10foo', (string) $materiaId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toBe('Datos de entrada no válidos');
    expect($response['errors'])->toHaveKey('idArticulo');
    expect(http_response_code())->toBe(400);
});

test('getMateriaTitlesByArticulo devuelve 400 cuando idArticulo no es numerico', function () {
    ob_start();
    $this->controller->getMateriaTitlesByArticulo('abc');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toBe('Datos de entrada no válidos');
    expect($response['errors'])->toHaveKey('idArticulo');
    expect(http_response_code())->toBe(400);
});

test('addMateriaToArticulo devuelve 400 cuando idMateria no es numerico valido', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo para validar idMateria',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    ob_start();
    $this->controller->addMateriaToArticulo((string) $articuloId, '12foo');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toBe('Datos de entrada no válidos');
    expect($response['errors'])->toHaveKey('idMateria');
    expect(http_response_code())->toBe(400);
});

test('deleteMateriaFromArticulo devuelve 400 cuando idMateria no es numerico', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo para validar baja',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    ob_start();
    $this->controller->deleteMateriaFromArticulo((string) $articuloId, 'abc');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toBe('Datos de entrada no válidos');
    expect($response['errors'])->toHaveKey('idMateria');
    expect(http_response_code())->toBe(400);
});

test('deleteMateriaFromArticulo devuelve 404 cuando materia no existe', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Articulo con materia inexistente',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    ob_start();
    $this->controller->deleteMateriaFromArticulo((string) $articuloId, '99999');
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['message'])->toContain('Materia');
    expect(http_response_code())->toBe(404);
});
