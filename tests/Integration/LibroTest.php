<?php

use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;
use Tests\Helper\TestStreamWrapper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $libroRepository = new LibroRepository($this->pdo);
    $articuloService = new ArticuloService($this->pdo);
    $service = new LibroService($libroRepository, $articuloService);

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

test('create crea libro completo con articulo y datos del libro', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    withJsonInputLibro([
        'articulo' => [
            'titulo' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'anio_publicacion' => 2008,
            'tipo_documento_id' => $tipoDocumentoId,
            'idioma' => 'en'
        ],
        'libro' => [
            'isbn' => '9780132350884',
            'export_marc' => 'MARC-CLEAN-CODE',
            'autor' => 'Robert C. Martin',
            'autores' => 'Robert C. Martin, Uncle Bob',
            'colaboradores' => 'Prentice Hall',
            'titulo_informativo' => 'A Handbook of Agile Software Craftsmanship',
            'cdu' => 004
        ]
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['isbn'])->toBe('9780132350884')
        ->and($response['data']['autor'])->toBe('Robert C. Martin')
        ->and($response['data']['autores'])->toBe('Robert C. Martin, Uncle Bob')
        ->and($response['data']['colaboradores'])->toBe('Prentice Hall')
        ->and($response['data']['titulo_informativo'])->toBe('A Handbook of Agile Software Craftsmanship')
        ->and($response['data']['cdu'])->toBe(004);

    // Verificar que tanto artículo como libro se crearon
    $articuloId = $response['data']['articulo_id'];
    expect($this->recordExists('articulo', ['id' => $articuloId]))->toBeTrue();
    expect($this->recordExists('libro', ['articulo_id' => $articuloId]))->toBeTrue();
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

    $this->insertInto('libro', [
        'articulo_id' => $articuloId1,
        'isbn' => '9780132350884',
        'autor' => 'Autor Original',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
        'export_marc' => 'MARC-ORIGINAL',
    ]);

    withJsonInputLibro([
        'articulo' => [
            'titulo' => 'Otro libro',
            'anio_publicacion' => 2024,
            'tipo_documento_id' => $tipoDocumentoId,
            'idioma' => 'es'
        ],
        'libro' => [
            'isbn' => '9780132350884', // ISBN duplicado
            'export_marc' => 'MARC-OTRO'
        ]
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(true)
        ->and($response)->toHaveKey('message');
});

test('update actualiza libro exitosamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Libro Original',
        'anio_publicacion' => 2020,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'autor' => 'Autor Original',
        'autores' => 'Autores Originales',
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
        'export_marc' => 'MARC-ORIGINAL',
    ]);

    withJsonInputLibro([
        'articulo_id' => $articuloId,
        'isbn' => '9780137081073', // Nuevo ISBN
        'export_marc' => 'MARC-ACTUALIZADO',
        'autor' => 'Autor Actualizado',
        'autores' => 'Autores Actualizados',
        'colaboradores' => 'Colaboradores Nuevos',
        'titulo_informativo' => 'Título informativo nuevo',
        'cdu' => 123
    ], function () use ($articuloId) {
        ob_start();
        $this->controller->update($articuloId);
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['isbn'])->toBe('9780137081073')
        ->and($response['data']['autor'])->toBe('Autor Actualizado')
        ->and($response['data']['autores'])->toBe('Autores Actualizados')
        ->and($response['data']['colaboradores'])->toBe('Colaboradores Nuevos')
        ->and($response['data']['titulo_informativo'])->toBe('Título informativo nuevo')
        ->and($response['data']['cdu'])->toBe(123);
});

test('delete elimina libro exitosamente', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Libro a Eliminar',
        'anio_publicacion' => 2020,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'autor' => 'Autor Test',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
        'export_marc' => 'MARC-ELIMINAR',
    ]);

    ob_start();
    $this->controller->delete($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false);
    expect($this->recordExists('libro', ['articulo_id' => $articuloId]))->toBeFalse();
});

test('listAll filtra libros con paginacion y metadatos', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    // Crear múltiples libros para probar paginación
    for ($i = 1; $i <= 15; $i++) {
        $articuloId = $this->insertInto('articulo', [
            'titulo' => "Libro Test $i",
            'anio_publicacion' => 2020 + $i,
            'tipo_documento_id' => $tipoDocumentoId,
            'idioma' => 'es',
        ]);

        $this->insertInto('libro', [
            'articulo_id' => $articuloId,
            'isbn' => '978013235088' . $i,
            'autor' => "Autor $i",
            'autores' => "Autores $i",
            'colaboradores' => null,
            'titulo_informativo' => "Info $i",
            'cdu' => 100 + $i,
            'export_marc' => "MARC-$i",
        ]);
    }

    $_GET = ['titulo' => 'Libro Test', 'page' => '1', 'per_page' => '5'];

    ob_start();
    $this->controller->listAll();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['items'])->toHaveCount(5) // 5 por página
        ->and($response['data']['pagination']['page'])->toBe(1)
        ->and($response['data']['pagination']['per_page'])->toBe(5)
        ->and($response['data']['pagination']['total'])->toBeGreaterThan(10);

    // Verificar que los datos del libro incluyen todos los campos
    $firstBook = $response['data']['items'][0];
    expect($firstBook)->toHaveKey('isbn')
        ->and($firstBook)->toHaveKey('autor')
        ->and($firstBook)->toHaveKey('autores')
        ->and($firstBook)->toHaveKey('titulo_informativo')
        ->and($firstBook)->toHaveKey('cdu')
        ->and($firstBook)->toHaveKey('export_marc');
});

test('getById retorna libro con todos los campos', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Libro Completo Test',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'autor' => 'Autor Completo',
        'autores' => 'Autor Principal, Coautor',
        'colaboradores' => 'Editor, Revisor',
        'titulo_informativo' => 'Subtítulo informativo',
        'cdu' => 500,
        'export_marc' => 'MARC-COMPLETO',
    ]);

    ob_start();
    $this->controller->getById($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response['error'])->toBe(false)
        ->and($response['data']['articulo_id'])->toBe($articuloId)
        ->and($response['data']['isbn'])->toBe('9780132350884')
        ->and($response['data']['autor'])->toBe('Autor Completo')
        ->and($response['data']['autores'])->toBe('Autor Principal, Coautor')
        ->and($response['data']['colaboradores'])->toBe('Editor, Revisor')
        ->and($response['data']['titulo_informativo'])->toBe('Subtítulo informativo')
        ->and($response['data']['cdu'])->toBe(500)
        ->and($response['data']['export_marc'])->toBe('MARC-COMPLETO');
});
