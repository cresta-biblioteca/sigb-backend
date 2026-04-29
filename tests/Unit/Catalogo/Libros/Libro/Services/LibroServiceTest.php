<?php

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Repositories\PersonaRepository;
use App\Catalogo\Libros\Services\LibroService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(LibroRepository::class);
    $this->articuloRepositoryMock = Mockery::mock(ArticuloRepository::class);
    $this->personaRepositoryMock = Mockery::mock(PersonaRepository::class);
    $this->articuloServiceMock = Mockery::mock(ArticuloService::class);
    $this->pdoMock = Mockery::mock(PDO::class);
    $this->service = new LibroService(
        $this->repositoryMock,
        $this->articuloRepositoryMock,
        $this->personaRepositoryMock,
        $this->articuloServiceMock,
        $this->pdoMock
    );
});

afterEach(function () {
    Mockery::close();
});

test('obtiene libro por id exitosamente', function () {
    $libro = Libro::create(5, '9780132350884');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(5)
        ->once()
        ->andReturn($libro);

    $result = $this->service->getById(5);

    expect($result)->toBeInstanceOf(LibroResponse::class);

    $json = $result->jsonSerialize();
    expect($json['id'])->toBe(5);
    expect($json['isbn'])->toBe('9780132350884');
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

test('elimina libro exitosamente', function () {
    $libro = Libro::create(11, '9780132350884');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(11)
        ->once()
        ->andReturn($libro);

    $this->articuloServiceMock
        ->shouldReceive('deleteArticulo')
        ->with(11)
        ->once();

    $this->service->deleteLibro(11);

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

test('busca libros paginados exitosamente', function () {
    $filters = ['titulo' => 'Clean Code'];
    $libro1 = Libro::create(1, '9780132350884');
    $libro2 = Libro::create(2, '9780137081073');

    $this->repositoryMock
        ->shouldReceive('countSearch')
        ->with($filters)
        ->once()
        ->andReturn(25);

    $this->repositoryMock
        ->shouldReceive('searchPaginated')
        ->with($filters, 1, 10, 'titulo', 'asc')
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
        ->with($filters, 1, 1, 'titulo', 'asc')
        ->once()
        ->andReturn([]);

    $result = $this->service->searchPaginated($filters, -5, 0);

    expect($result['pagination']['page'])->toBe(1);
    expect($result['pagination']['per_page'])->toBe(1);
    expect($result['pagination']['total'])->toBe(0);
    expect($result['pagination']['total_pages'])->toBe(1);
});
