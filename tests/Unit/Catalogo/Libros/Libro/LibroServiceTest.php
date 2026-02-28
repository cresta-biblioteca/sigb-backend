<?php

use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;
use Mockery\MockInterface;

// Variables de prueba (compartidas entre tests)

/** @var MockInterface */
$repositoryMock = null;
/** @var MockInterface */
$articuloServiceMock = null;
/** @var LibroService */
$service = null;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(LibroRepository::class);
    $this->articuloServiceMock = Mockery::mock(ArticuloService::class);
    $this->service = new LibroService($this->repositoryMock, $this->articuloServiceMock);
});

afterEach(function () {
    Mockery::close();
});

test('obtiene todos los libros exitosamente', function () {
    $libro1 = Libro::create(1, '9780132350884', 'MARC-1', 'Autor 1');
    $libro2 = Libro::create(2, '9780137081073', 'MARC-2', 'Autor 2');

    $this->repositoryMock
        ->shouldReceive('findAll')
        ->once()
        ->andReturn([$libro1, $libro2]);

    $result = $this->service->getAll();

    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(LibroResponse::class);
    expect($result[1])->toBeInstanceOf(LibroResponse::class);
    expect($result[0]->isbn)->toBe('9780132350884');
    expect($result[1]->isbn)->toBe('9780137081073');
});

test('obtiene libro por id exitosamente', function () {
    $libro = Libro::create(5, '9780132350884', 'MARC', 'Autor Test', 'Autores Test');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(5)
        ->once()
        ->andReturn($libro);

    $result = $this->service->getById(5);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    expect($result->articuloId)->toBe(5);
    expect($result->isbn)->toBe('9780132350884');
    expect($result->autor)->toBe('Autor Test');
    expect($result->autores)->toBe('Autores Test');
});

test('lanza excepcion al obtener libro inexistente', function () {
    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn () => $this->service->getById(999))
        ->toThrow(LibroNotFoundException::class);
});

test('crea un libro completo exitosamente', function () {
    $articuloData = [
        'titulo' => 'Clean Code',
        'anio_publicacion' => 2008,
        'tipo_documento_id' => 1,
        'idioma' => 'en'
    ];

    $libroData = [
        'isbn' => '9780132350884',
        'export_marc' => 'MARC Test',
        'autor' => 'Robert Martin',
        'autores' => 'Robert Martin, Uncle Bob',
        'colaboradores' => 'Editor Test',
        'titulo_informativo' => 'Título informativo test',
        'cdu' => 123
    ];

    $articuloResponse = new ArticuloResponse(1, 'Clean Code', 2008, 1, 'en');
    $libro = Libro::create(1, '9780132350884', 'MARC Test', 'Robert Martin', 'Robert Martin, Uncle Bob', 'Editor Test', 'Título informativo test', 123);

    $this->repositoryMock
        ->shouldReceive('existsByIsbn')
        ->with('9780132350884')
        ->once()
        ->andReturn(false);

    $this->articuloServiceMock
        ->shouldReceive('create')
        ->once()
        ->andReturn($articuloResponse);

    $this->repositoryMock
        ->shouldReceive('save')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($libro);

    $result = $this->service->create($articuloData, $libroData);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    expect($result->articuloId)->toBe(1);
    expect($result->isbn)->toBe('9780132350884');
    expect($result->autor)->toBe('Robert Martin');
    expect($result->autores)->toBe('Robert Martin, Uncle Bob');
    expect($result->colaboradores)->toBe('Editor Test');
    expect($result->tituloInformativo)->toBe('Título informativo test');
    expect($result->cdu)->toBe(123);
});

test('lanza excepcion si isbn ya existe al crear libro', function () {
    $articuloData = [
        'titulo' => 'Clean Code',
        'anio_publicacion' => 2008,
        'tipo_documento_id' => 1
    ];

    $libroData = [
        'isbn' => '9780132350884',
        'export_marc' => 'MARC'
    ];

    $this->repositoryMock
        ->shouldReceive('existsByIsbn')
        ->with('9780132350884')
        ->once()
        ->andReturn(true);

    expect(fn () => $this->service->create($articuloData, $libroData))
        ->toThrow(LibroAlreadyExistsException::class);
});

test('actualiza libro exitosamente', function () {
    $request = new LibroRequest(
        articuloId: 7,
        isbn: '9780132350884',
        exportMarc: 'MARC-UPDATED',
        autor: 'Autor Actualizado',
        autores: 'Autores Actualizados',
        colaboradores: 'Colaboradores Actualizados',
        tituloInformativo: 'Título informativo actualizado',
        cdu: 456
    );

    $existing = Libro::create(7, '9780132350883', 'MARC-OLD');
    $updated = Libro::create(7, '9780132350884', 'MARC-UPDATED', 'Autor Actualizado', 'Autores Actualizados');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(7)
        ->twice()
        ->andReturn($existing, $updated);

    $this->repositoryMock
        ->shouldReceive('existsByIsbn')
        ->with('9780132350884', 7)
        ->once()
        ->andReturn(false);

    $this->repositoryMock
        ->shouldReceive('update')
        ->once()
        ->andReturnNull();

    $result = $this->service->updateLibro(7, $request);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    expect($result->articuloId)->toBe(7);
    expect($result->isbn)->toBe('9780132350884');
    expect($result->exportMarc)->toBe('MARC-UPDATED');
    expect($result->autor)->toBe('Autor Actualizado');
});

test('lanza excepcion al actualizar libro inexistente', function () {
    $request = new LibroRequest(
        articuloId: 999,
        isbn: '9780132350884',
        exportMarc: 'MARC'
    );

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn () => $this->service->updateLibro(999, $request))
        ->toThrow(LibroNotFoundException::class);
});

test('lanza excepcion al actualizar con isbn duplicado', function () {
    $request = new LibroRequest(
        articuloId: 7,
        isbn: '9780132350884',
        exportMarc: 'MARC'
    );

    $existing = Libro::create(7, '9780132350883', 'MARC-OLD');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(7)
        ->once()
        ->andReturn($existing);

    $this->repositoryMock
        ->shouldReceive('existsByIsbn')
        ->with('9780132350884', 7)
        ->once()
        ->andReturn(true);

    expect(fn () => $this->service->updateLibro(7, $request))
        ->toThrow(LibroAlreadyExistsException::class);
});

test('elimina libro exitosamente', function () {
    $libro = Libro::create(11, '9780132350884', 'MARC');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(11)
        ->once()
        ->andReturn($libro);

    $this->repositoryMock
        ->shouldReceive('delete')
        ->with(11)
        ->once()
        ->andReturn(true);

    $this->service->deleteLibro(11);

    // Si llega aquí sin excepción, el test pasa
    expect(true)->toBeTrue();
});

test('lanza excepcion al eliminar libro inexistente', function () {
    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn () => $this->service->deleteLibro(999))
        ->toThrow(LibroNotFoundException::class);
});

test('busca libros exitosamente', function () {
    $filters = ['titulo' => 'Clean Code'];
    $libro1 = Libro::create(1, '9780132350884', 'MARC-1', 'Robert Martin');
    $libro2 = Libro::create(2, '9780137081073', 'MARC-2', 'Martin Fowler');

    $this->repositoryMock
        ->shouldReceive('search')
        ->with($filters)
        ->once()
        ->andReturn([$libro1, $libro2]);

    $result = $this->service->search($filters);

    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(LibroResponse::class);
    expect($result[1])->toBeInstanceOf(LibroResponse::class);
    expect($result[0]->isbn)->toBe('9780132350884');
    expect($result[1]->isbn)->toBe('9780137081073');
});

test('busca libros paginados exitosamente', function () {
    $filters = ['titulo' => 'Clean Code'];
    $libro1 = Libro::create(1, '9780132350884', 'MARC-1', 'Robert Martin');
    $libro2 = Libro::create(2, '9780137081073', 'MARC-2', 'Martin Fowler');

    $this->repositoryMock
        ->shouldReceive('countSearch')
        ->with($filters)
        ->once()
        ->andReturn(25);

    $this->repositoryMock
        ->shouldReceive('searchPaginated')
        ->with($filters, 1, 10)
        ->once()
        ->andReturn([$libro1, $libro2]);

    $result = $this->service->searchPaginated($filters, 1, 10);

    expect($result['items'])->toHaveCount(2);
    expect($result['items'][0])->toBeInstanceOf(LibroResponse::class);
    expect($result['pagination']['page'])->toBe(1);
    expect($result['pagination']['per_page'])->toBe(10);
    expect($result['pagination']['total'])->toBe(25);
    expect($result['pagination']['total_pages'])->toBe(3);
});

test('busca libros paginados con pagina fuera de rango normaliza parametros', function () {
    $filters = [];

    $this->repositoryMock
        ->shouldReceive('countSearch')
        ->with($filters)
        ->once()
        ->andReturn(0);

    $this->repositoryMock
        ->shouldReceive('searchPaginated')
        ->with($filters, 1, 1) // page y perPage normalizados
        ->once()
        ->andReturn([]);

    $result = $this->service->searchPaginated($filters, -5, 0); // Parámetros inválidos

    expect($result['pagination']['page'])->toBe(1);
    expect($result['pagination']['per_page'])->toBe(1);
    expect($result['pagination']['total'])->toBe(0);
    expect($result['pagination']['total_pages'])->toBe(1);
});
