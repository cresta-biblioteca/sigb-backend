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

    expect($response)->toHaveKey('data')
        ->and($response['data']['isbn'])->toBe('9780132350884')
        ->and($response['data']['autor'])->toBe('Robert C. Martin')
        ->and($response['data']['autores'])->toBe('Robert C. Martin, Uncle Bob')
        ->and($response['data']['colaboradores'])->toBe('Prentice Hall')
        ->and($response['data']['titulo_informativo'])->toBe('A Handbook of Agile Software Craftsmanship')
        ->and($response['data']['cdu'])->toBe(004);

    // Verificar que tanto artículo como libro se crearon
    $articuloId = $response['data']['id']; // id y articulo_id son lo mismo
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
        ]
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response)->toHaveKey('error')
        ->and($response['error'])->toBeArray()
        ->and($response['error']['code'])->toBe('ENTITY_ALREADY_EXISTS');
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
    ]);

    withJsonInputLibro([
        'autor' => 'Autor Actualizado',
        'autores' => 'Autores Actualizados',
        'colaboradores' => 'Colaboradores Nuevos',
        'titulo_informativo' => 'Título informativo nuevo',
        'cdu' => 123
    ], function () use ($articuloId) {
        ob_start();
        $this->controller->updateLibro($articuloId);
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data']['isbn'])->toBe('9780132350884') // ISBN original (inmutable)
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
    ]);

    ob_start();
    $this->controller->deleteLibro($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('message');
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
            'isbn' => sprintf('978013235%03d', $i), // Genera ISBNs como 9780132350001, 9780132350002, etc.
            'autor' => "Autor $i",
            'autores' => "Autores $i",
            'colaboradores' => null,
            'titulo_informativo' => "Info $i",
            'cdu' => 100 + $i,
        ]);
    }

    $_GET = ['titulo' => 'Libro Test', 'page' => '1', 'per_page' => '5'];

    ob_start();
    $this->controller->searchPaginated();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data'])->toHaveCount(5) // 5 por página
        ->and($response['pagination']['page'])->toBe(1)
        ->and($response['pagination']['per_page'])->toBe(5)
        ->and($response['pagination']['total'])->toBeGreaterThan(10);

    // Verificar que los datos del libro incluyen todos los campos
    $firstBook = $response['data'][0];
    expect($firstBook)->toHaveKey('isbn')
        ->and($firstBook)->toHaveKey('autor')
        ->and($firstBook)->toHaveKey('autores')
        ->and($firstBook)->toHaveKey('titulo_informativo')
        ->and($firstBook)->toHaveKey('cdu');
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
    ]);

    ob_start();
    $this->controller->getById($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data']['id'])->toBe($articuloId) // id y articulo_id son lo mismo
        ->and($response['data']['isbn'])->toBe('9780132350884')
        ->and($response['data']['autor'])->toBe('Autor Completo')
        ->and($response['data']['autores'])->toBe('Autor Principal, Coautor')
        ->and($response['data']['colaboradores'])->toBe('Editor, Revisor')
        ->and($response['data']['titulo_informativo'])->toBe('Subtítulo informativo')
        ->and($response['data']['cdu'])->toBe(500);
});

test('search filtra libros por titulos de temas', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $temaProgramacionId = $this->insertInto('tema', [
        'titulo' => 'Programacion',
    ]);

    $temaHistoriaId = $this->insertInto('tema', [
        'titulo' => 'Historia',
    ]);

    $articuloProgramacionId = $this->insertInto('articulo', [
        'titulo' => 'Libro de Programacion',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloProgramacionId,
        'isbn' => '9780132350001',
        'autor' => 'Autor Programacion',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    $this->insertInto('articulo_tema', [
        'articulo_id' => $articuloProgramacionId,
        'tema_id' => $temaProgramacionId,
    ]);

    $articuloHistoriaId = $this->insertInto('articulo', [
        'titulo' => 'Libro de Historia',
        'anio_publicacion' => 2023,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloHistoriaId,
        'isbn' => '9780132350002',
        'autor' => 'Autor Historia',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    $this->insertInto('articulo_tema', [
        'articulo_id' => $articuloHistoriaId,
        'tema_id' => $temaHistoriaId,
    ]);

    $_GET = ['temas' => 'Programacion'];

    ob_start();
    $this->controller->searchPaginated();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data'])->toHaveCount(1)
        ->and($response['data'][0]['id'])->toBe($articuloProgramacionId)
        ->and($response['data'][0]['articulo']['titulo'])->toBe('Libro de Programacion');
});

test('searchPaginated filtra libros por multiples temas', function () {
    $tipoDocumentoId = $this->insertInto('tipo_documento', [
        'codigo' => 'LIB',
        'descripcion' => 'Libro',
        'renovable' => 1,
        'detalle' => 'Material bibliografico',
    ]);

    $temaProgramacionId = $this->insertInto('tema', [
        'titulo' => 'Programacion',
    ]);

    $temaHistoriaId = $this->insertInto('tema', [
        'titulo' => 'Historia',
    ]);

    $articuloUnoId = $this->insertInto('articulo', [
        'titulo' => 'Libro Uno',
        'anio_publicacion' => 2024,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloUnoId,
        'isbn' => '9780132350003',
        'autor' => 'Autor Uno',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    $this->insertInto('articulo_tema', [
        'articulo_id' => $articuloUnoId,
        'tema_id' => $temaProgramacionId,
    ]);

    $articuloDosId = $this->insertInto('articulo', [
        'titulo' => 'Libro Dos',
        'anio_publicacion' => 2022,
        'tipo_documento_id' => $tipoDocumentoId,
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloDosId,
        'isbn' => '9780132350004',
        'autor' => 'Autor Dos',
        'autores' => null,
        'colaboradores' => null,
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    $this->insertInto('articulo_tema', [
        'articulo_id' => $articuloDosId,
        'tema_id' => $temaHistoriaId,
    ]);

    $_GET = [
        'temas' => ['Programacion', 'Historia'],
        'page' => '1',
        'per_page' => '10',
    ];

    ob_start();
    $this->controller->searchPaginated();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data'])->toHaveCount(2)
        ->and($response['pagination']['total'])->toBe(2);
});
