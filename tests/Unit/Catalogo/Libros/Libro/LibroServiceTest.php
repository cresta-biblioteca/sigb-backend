<?php

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Services\LibroService;

beforeEach(function () {
    $this->repositoryMock = $this->createMock(LibroRepository::class);
    $this->articuloRepositoryMock = $this->createMock(ArticuloRepository::class);
    $this->pdoMock = $this->createMock(PDO::class);

    $this->service = new LibroService(
        repository: $this->repositoryMock,
        articuloRepository: $this->articuloRepositoryMock,
        pdo: $this->pdoMock
    );
});

test('crea un libro exitosamente', function () {
    $request = new LibroRequest(
        articuloId: 1,
        isbn: '9780132350884',
        exportMarc: 'MARC',
        autor: 'Autor Test'
    );

    $libro = Libro::create(1, '9780132350884', 'MARC', 'Autor Test');

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByArticuloId')
        ->with(1)
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByIsbn')
        ->with('9780132350884')
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(Libro::class));

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(1)
        ->willReturn($libro);

    $result = $this->service->create($request);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    expect($result->articuloId)->toBe(1);
    expect($result->isbn)->toBe('9780132350884');
});

test('lanza excepcion si isbn ya existe al crear', function () {
    $request = new LibroRequest(
        articuloId: 2,
        isbn: '9780132350884',
        exportMarc: 'MARC'
    );

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByArticuloId')
        ->with(2)
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByIsbn')
        ->with('9780132350884')
        ->willReturn(true);

    expect(fn () => $this->service->create($request))
        ->toThrow(LibroAlreadyExistsException::class);
});

test('obtiene libro por id exitosamente', function () {
    $libro = Libro::create(5, '9780132350884', 'MARC');

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(5)
        ->willReturn($libro);

    $result = $this->service->getById(5);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    expect($result->articuloId)->toBe(5);
});

test('lanza excepcion al obtener libro inexistente', function () {
    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    expect(fn () => $this->service->getById(999))
        ->toThrow(LibroNotFoundException::class);
});

test('actualiza libro exitosamente', function () {
    $request = new LibroRequest(
        articuloId: 7,
        isbn: '9780132350884',
        exportMarc: 'MARC-UPDATED'
    );

    $existing = Libro::create(7, '9780132350884', 'MARC');
    $updated = Libro::create(7, '9780132350884', 'MARC-UPDATED');

    $this->repositoryMock
        ->expects($this->exactly(2))
        ->method('findById')
        ->with(7)
        ->willReturnOnConsecutiveCalls($existing, $updated);

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByIsbn')
        ->with('9780132350884', 7)
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('update')
        ->with($this->isInstanceOf(Libro::class))
        ->willReturn(true);

    $result = $this->service->update(7, $request);

    expect($result->articuloId)->toBe(7);
    expect($result->exportMarc)->toBe('MARC-UPDATED');
});

test('elimina libro exitosamente', function () {
    $libro = Libro::create(11, '9780132350884', 'MARC');

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(11)
        ->willReturn($libro);

    $this->repositoryMock
        ->expects($this->once())
        ->method('delete')
        ->with(11);

    $this->service->delete(11);

    expect(true)->toBeTrue();
});

test('lista libros paginados con metadatos', function () {
    $libro1 = Libro::create(1, '9780132350884', 'MARC-1');
    $libro2 = Libro::create(2, '9780137081073', 'MARC-2');

    $filters = ['titulo' => 'clean'];

    $this->repositoryMock
        ->expects($this->once())
        ->method('countSearch')
        ->with($filters)
        ->willReturn(2);

    $this->repositoryMock
        ->expects($this->once())
        ->method('searchPaginated')
        ->with($filters, 1, 10)
        ->willReturn([$libro1, $libro2]);

    $result = $this->service->listPaginated($filters, 1, 10);

    expect($result['items'])->toHaveCount(2);
    expect($result['pagination']['total'])->toBe(2);
});

test('createFromCatalog con articulo_id reutiliza flujo de create', function () {
    $payload = [
        'articulo_id' => 15,
        'isbn' => '9780132350884',
        'export_marc' => 'MARC',
    ];

    $libro = Libro::create(15, '9780132350884', 'MARC');

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByArticuloId')
        ->with(15)
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsByIsbn')
        ->with('9780132350884')
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('save');

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(15)
        ->willReturn($libro);

    $result = $this->service->createFromCatalog($payload);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    expect($result->articuloId)->toBe(15);
});
