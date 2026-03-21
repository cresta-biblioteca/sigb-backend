<?php

use App\Catalogo\Libros\Dtos\Request\CreateLibroRequest;
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
/** @var LibroService */
$service = null;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(LibroRepository::class);
    $this->service = new LibroService($this->repositoryMock);
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
    
    $json0 = $result[0]->jsonSerialize();
    $json1 = $result[1]->jsonSerialize();
    expect($json0['isbn'])->toBe('9780132350884');
    expect($json1['isbn'])->toBe('9780137081073');
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
    
    $json = $result->jsonSerialize();
    expect($json['id'])->toBe(5);
    expect($json['isbn'])->toBe('9780132350884');
    expect($json['autor'])->toBe('Autor Test');
    expect($json['autores'])->toBe('Autores Test');
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
    $request = new CreateLibroRequest(
        articuloId: 1,
        isbn: '9780132350884',
        exportMarc: 'MARC Test',
        autor: 'Robert Martin',
        autores: 'Robert Martin, Uncle Bob',
        colaboradores: 'Editor Test',
        tituloInformativo: 'Título informativo test',
        cdu: 123
    );

    $libro = Libro::create(1, '9780132350884', 'MARC Test', 'Robert Martin', 'Robert Martin, Uncle Bob', 'Editor Test', 'Título informativo test', 123);

    $this->repositoryMock
        ->shouldReceive('existsByIsbn')
        ->with('9780132350884')
        ->once()
        ->andReturn(false);

    $this->repositoryMock
        ->shouldReceive('insertLibro')
        ->once()
        ->andReturn($libro);

    $result = $this->service->create($request);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    
    $json = $result->jsonSerialize();
    expect($json['id'])->toBe(1);
    expect($json['isbn'])->toBe('9780132350884');
    expect($json['autor'])->toBe('Robert Martin');
    expect($json['autores'])->toBe('Robert Martin, Uncle Bob');
    expect($json['colaboradores'])->toBe('Editor Test');
    expect($json['titulo_informativo'])->toBe('Título informativo test');
    expect($json['cdu'])->toBe(123);
});

test('lanza excepcion si isbn ya existe al crear libro', function () {
    $request = new CreateLibroRequest(
        articuloId: 1,
        isbn: '9780132350884',
        exportMarc: 'MARC'
    );

    $this->repositoryMock
        ->shouldReceive('existsByIsbn')
        ->with('9780132350884')
        ->once()
        ->andReturn(true);

    expect(fn () => $this->service->create($request))
        ->toThrow(LibroAlreadyExistsException::class);
});

test('actualiza libro exitosamente', function () {
    $request = new CreateLibroRequest(
        articuloId: 7,
        isbn: '9780132350884', // Este valor será ignorado (inmutable)
        exportMarc: 'MARC-UPDATED', // Este valor será ignorado (inmutable)
        autor: 'Autor Actualizado',
        autores: 'Autores Actualizados',
        colaboradores: 'Colaboradores Actualizados',
        tituloInformativo: 'Título informativo actualizado',
        cdu: 456
    );

    $existing = Libro::create(7, '9780132350883', 'MARC-OLD');
    $updated = Libro::create(7, '9780132350883', 'MARC-OLD', 'Autor Actualizado', 'Autores Actualizados', 'Colaboradores Actualizados', 'Título informativo actualizado', 456);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(7)
        ->once()
        ->andReturn($existing);

    // No se valida ISBN porque es inmutable

    $this->repositoryMock
        ->shouldReceive('updateLibro')
        ->with(7, Mockery::type(Libro::class))
        ->once()
        ->andReturn($updated);

    $result = $this->service->updateLibro(7, $request);

    expect($result)->toBeInstanceOf(LibroResponse::class);
    
    $json = $result->jsonSerialize();
    expect($json['id'])->toBe(7);
    expect($json['isbn'])->toBe('9780132350883'); // ISBN original preservado
    expect($json['export_marc'])->toBe('MARC-OLD'); // Export_marc original preservado
    expect($json['autor'])->toBe('Autor Actualizado'); // Campo editable actualizado
    expect($json['colaboradores'])->toBe('Colaboradores Actualizados');
    expect($json['titulo_informativo'])->toBe('Título informativo actualizado');
    expect($json['cdu'])->toBe(456);
});

test('lanza excepcion al actualizar libro inexistente', function () {
    $request = new CreateLibroRequest(
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

test('actualiza solo campos editables mientras preserva inmutables', function () {
    // Este test reemplaza al de "isbn duplicado" porque ISBN ya no puede cambiar
    $request = new CreateLibroRequest(
        articuloId: 7,
        isbn: '9999999999999', // Valor ignorado porque ISBN es inmutable
        exportMarc: 'MARC-IGNORADO', // Valor ignorado porque export_marc es inmutable
        autor: 'Nuevo Autor',
        cdu: 999
    );

    $existing = Libro::create(7, '9780132350883', 'MARC-ORIGINAL', 'Autor Original');
    $updated = Libro::create(7, '9780132350883', 'MARC-ORIGINAL', 'Nuevo Autor', null, null, null, 999);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(7)
        ->once()
        ->andReturn($existing);

    $this->repositoryMock
        ->shouldReceive('updateLibro')
        ->with(7, Mockery::type(Libro::class))
        ->once()
        ->andReturn($updated);

    $result = $this->service->updateLibro(7, $request);
    $json = $result->jsonSerialize();
    
    // Verificar que campos inmutables se preservaron
    expect($json['isbn'])->toBe('9780132350883'); // Original preservado
    expect($json['export_marc'])->toBe('MARC-ORIGINAL'); // Original preservado
    
    // Verificar que campos editables se actualizaron
    expect($json['autor'])->toBe('Nuevo Autor'); // Actualizado
    expect($json['cdu'])->toBe(999); // Actualizado
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
    
    $json0 = $result[0]->jsonSerialize();
    $json1 = $result[1]->jsonSerialize();
    expect($json0['isbn'])->toBe('9780132350884');
    expect($json1['isbn'])->toBe('9780137081073');
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
