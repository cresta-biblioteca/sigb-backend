<?php

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Libros\Controllers\LibroController;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Repositories\PersonaRepository;
use App\Catalogo\Libros\Services\LibroService;
use Tests\Helper\TestStreamWrapper;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $libroRepository = new LibroRepository($this->pdo);
    $articuloRepository = new ArticuloRepository($this->pdo);
    $personaRepository = new PersonaRepository($this->pdo);
    $service = new LibroService($libroRepository, $articuloRepository, $personaRepository, $this->pdo);

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

test('create crea libro completo con articulo y personas', function () {
    withJsonInputLibro([
        'articulo' => [
            'titulo' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'anio_publicacion' => 2008,
            'tipo' => 'libro',
            'idioma' => 'en'
        ],
        'libro' => [
            'isbn' => '9780132350884',
            'titulo_informativo' => 'A Handbook of Agile Software Craftsmanship',
            'cdu' => 004,
            'edicion' => '1a edición',
            'personas' => [
                ['nombre' => 'Robert C.', 'apellido' => 'Martin', 'rol' => 'autor'],
                ['nombre' => 'Michael C.', 'apellido' => 'Feathers', 'rol' => 'colaborador'],
            ]
        ]
    ], function () {
        ob_start();
        $this->controller->create();
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data']['isbn'])->toBe('9780132350884')
        ->and($response['data']['titulo_informativo'])->toBe('A Handbook of Agile Software Craftsmanship')
        ->and($response['data']['cdu'])->toBe(004)
        ->and($response['data']['edicion'])->toBe('1a edición')
        ->and($response['data']['personas'])->toHaveCount(2)
        ->and($response['data']['personas'][0]['apellido'])->toBe('Martin')
        ->and($response['data']['personas'][0]['rol'])->toBe('autor')
        ->and($response['data']['personas'][1]['apellido'])->toBe('Feathers')
        ->and($response['data']['personas'][1]['rol'])->toBe('colaborador');

    // Verificar que tanto artículo como libro se crearon
    $articuloId = $response['data']['id'];
    expect($this->recordExists('articulo', ['id' => $articuloId]))->toBeTrue();
    expect($this->recordExists('libro', ['articulo_id' => $articuloId]))->toBeTrue();
});

test('create falla con isbn duplicado', function () {
    $articuloId1 = $this->insertInto('articulo', [
        'titulo' => 'Articulo Uno',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId1,
        'isbn' => '9780132350884',
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    withJsonInputLibro([
        'articulo' => [
            'titulo' => 'Otro libro',
            'anio_publicacion' => 2024,
            'tipo' => 'libro',
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

test('update actualiza libro y personas exitosamente', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Libro Original',
        'anio_publicacion' => 2020,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    withJsonInputLibro([
        'titulo_informativo' => 'Título informativo nuevo',
        'cdu' => 123,
        'edicion' => '2a edición',
        'personas' => [
            ['nombre' => 'Juan', 'apellido' => 'Pérez', 'rol' => 'autor'],
        ]
    ], function () use ($articuloId) {
        ob_start();
        $this->controller->updateLibro($articuloId);
        $this->output = ob_get_clean();
    });

    $response = json_decode($this->output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data']['isbn'])->toBe('9780132350884') // ISBN original (inmutable)
        ->and($response['data']['titulo_informativo'])->toBe('Título informativo nuevo')
        ->and($response['data']['cdu'])->toBe(123)
        ->and($response['data']['edicion'])->toBe('2a edición')
        ->and($response['data']['personas'])->toHaveCount(1)
        ->and($response['data']['personas'][0]['apellido'])->toBe('Pérez');
});

test('delete elimina libro exitosamente', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Libro a Eliminar',
        'anio_publicacion' => 2020,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
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
    // Crear múltiples libros para probar paginación
    for ($i = 1; $i <= 15; $i++) {
        $articuloId = $this->insertInto('articulo', [
            'titulo' => "Libro Test $i",
            'anio_publicacion' => 2020 + $i,
            'tipo' => 'libro',
            'idioma' => 'es',
        ]);

        $this->insertInto('libro', [
            'articulo_id' => $articuloId,
            'isbn' => sprintf('978013235%03d', $i),
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

    // Verificar que los datos del libro incluyen los campos nuevos
    $firstBook = $response['data'][0];
    expect($firstBook)->toHaveKey('isbn')
        ->and($firstBook)->toHaveKey('titulo_informativo')
        ->and($firstBook)->toHaveKey('cdu')
        ->and($firstBook)->toHaveKey('personas');
});

test('getById retorna libro con todos los campos', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Libro Completo Test',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780132350884',
        'titulo_informativo' => 'Subtítulo informativo',
        'cdu' => 500,
        'edicion' => '3a edición',
        'dimensiones' => '24 cm',
        'pais_publicacion' => 'ar',
    ]);

    $personaId = $this->insertInto('persona', [
        'nombre' => 'Thomas H.',
        'apellido' => 'Cormen',
    ]);

    $this->insertInto('libro_persona', [
        'libro_id' => $articuloId,
        'persona_id' => $personaId,
        'rol' => 'autor',
        'orden' => 0,
    ]);

    ob_start();
    $this->controller->getById($articuloId);
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data']['id'])->toBe($articuloId)
        ->and($response['data']['isbn'])->toBe('9780132350884')
        ->and($response['data']['titulo_informativo'])->toBe('Subtítulo informativo')
        ->and($response['data']['cdu'])->toBe(500)
        ->and($response['data']['edicion'])->toBe('3a edición')
        ->and($response['data']['dimensiones'])->toBe('24 cm')
        ->and($response['data']['pais_publicacion'])->toBe('ar')
        ->and($response['data']['personas'])->toHaveCount(1)
        ->and($response['data']['personas'][0]['nombre'])->toBe('Thomas H.')
        ->and($response['data']['personas'][0]['apellido'])->toBe('Cormen');
});

test('deduplicación de personas al crear dos libros con mismo autor', function () {
    // Crear primer libro con autor
    withJsonInputLibro([
        'articulo' => [
            'titulo' => 'Libro Uno',
            'anio_publicacion' => 2020,
            'tipo' => 'libro',
            'idioma' => 'es'
        ],
        'libro' => [
            'isbn' => '9780132350001',
            'personas' => [
                ['nombre' => 'Jorge Luis', 'apellido' => 'Borges', 'rol' => 'autor'],
            ]
        ]
    ], function () {
        ob_start();
        $this->controller->create();
        ob_get_clean();
    });

    // Crear segundo libro con el mismo autor
    withJsonInputLibro([
        'articulo' => [
            'titulo' => 'Libro Dos',
            'anio_publicacion' => 2021,
            'tipo' => 'libro',
            'idioma' => 'es'
        ],
        'libro' => [
            'isbn' => '9780132350002',
            'personas' => [
                ['nombre' => 'Jorge Luis', 'apellido' => 'Borges', 'rol' => 'autor'],
            ]
        ]
    ], function () {
        ob_start();
        $this->controller->create();
        ob_get_clean();
    });

    // Solo debe haber 1 registro en persona
    $stmt = $this->pdo->query("SELECT COUNT(*) FROM persona WHERE nombre = 'Jorge Luis' AND apellido = 'Borges'");
    $count = (int) $stmt->fetchColumn();

    expect($count)->toBe(1);
});

test('search filtra libros por titulos de temas', function () {
    $temaProgramacionId = $this->insertInto('tema', [
        'titulo' => 'Programacion',
    ]);

    $temaHistoriaId = $this->insertInto('tema', [
        'titulo' => 'Historia',
    ]);

    $articuloProgramacionId = $this->insertInto('articulo', [
        'titulo' => 'Libro de Programacion',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloProgramacionId,
        'isbn' => '9780132350001',
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
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloHistoriaId,
        'isbn' => '9780132350002',
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
    $temaProgramacionId = $this->insertInto('tema', [
        'titulo' => 'Programacion',
    ]);

    $temaHistoriaId = $this->insertInto('tema', [
        'titulo' => 'Historia',
    ]);

    $articuloUnoId = $this->insertInto('articulo', [
        'titulo' => 'Libro Uno',
        'anio_publicacion' => 2024,
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloUnoId,
        'isbn' => '9780132350003',
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
        'tipo' => 'libro',
        'idioma' => 'es',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloDosId,
        'isbn' => '9780132350004',
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

test('search filtra libros por persona', function () {
    $articuloId = $this->insertInto('articulo', [
        'titulo' => 'Introduction to Algorithms',
        'anio_publicacion' => 2009,
        'tipo' => 'libro',
        'idioma' => 'en',
    ]);

    $this->insertInto('libro', [
        'articulo_id' => $articuloId,
        'isbn' => '9780262033848',
        'titulo_informativo' => null,
        'cdu' => null,
    ]);

    $personaId = $this->insertInto('persona', [
        'nombre' => 'Thomas H.',
        'apellido' => 'Cormen',
    ]);

    $this->insertInto('libro_persona', [
        'libro_id' => $articuloId,
        'persona_id' => $personaId,
        'rol' => 'autor',
        'orden' => 0,
    ]);

    $_GET = ['persona' => 'Cormen'];

    ob_start();
    $this->controller->searchPaginated();
    $output = ob_get_clean();

    $response = json_decode($output, true);

    expect($response)->toHaveKey('data')
        ->and($response['data'])->toHaveCount(1)
        ->and($response['data'][0]['articulo']['titulo'])->toBe('Introduction to Algorithms');
});
