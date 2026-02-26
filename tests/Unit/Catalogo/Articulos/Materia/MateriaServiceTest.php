<?php

use App\Catalogo\Articulos\Dtos\Request\MateriaRequest;
use App\Catalogo\Articulos\Dtos\Response\MateriaResponse;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Catalogo\Articulos\Models\Materia;
use App\Catalogo\Articulos\Repository\MateriaRepository;
use App\Catalogo\Articulos\Services\MateriaService;
use App\Shared\Exceptions\ValidationException;
use Mockery\MockInterface;

// Variables de prueba (compartidas entre tests)

/** @var MockInterface */
$repositoryMock = null;
/** @var MateriaService */
$service = null;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(MateriaRepository::class);
    $this->service = new MateriaService($this->repositoryMock);
});

afterEach(function () {
    Mockery::close();
});

test('crea una materia exitosamente', function () {
    $request = new MateriaRequest(titulo: 'Matemática');

    $materiaCreada = Materia::create('Matemática');
    $materiaCreada->setId(1);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Matemática')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('insertMateria')
        ->once()
        ->andReturn($materiaCreada);

    $result = $this->service->createMateria($request);

    expect($result)->toBeInstanceOf(MateriaResponse::class);
    expect($result->id)->toBe(1);
    expect($result->titulo)->toBe('Matemática');
});

test('lanza MateriaAlreadyExistsException si ya existe', function () {
    $request = new MateriaRequest(titulo: 'Matemática');

    $materiaExistente = Materia::create('Matemática');
    $materiaExistente->setId(1);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Matemática')
        ->once()
        ->andReturn($materiaExistente);

    expect(fn() => $this->service->createMateria($request))
        ->toThrow(MateriaAlreadyExistsException::class);
});

test('lanza ValidationException si el título está vacío', function () {
    $request = new MateriaRequest(titulo: '');

    expect(fn() => $this->service->createMateria($request))
        ->toThrow(ValidationException::class);
});

test('lanza ValidationException si el título excede 100 caracteres', function () {
    $tituloLargo = str_repeat('A', 101);
    $request = new MateriaRequest(titulo: $tituloLargo);

    expect(fn() => $this->service->createMateria($request))
        ->toThrow(ValidationException::class);
});

test('convierte correctamente titulo con espacios', function () {
    $request = new MateriaRequest(titulo: 'Matemática I');

    $materiaCreada = Materia::create('Matemática I');
    $materiaCreada->setId(5);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Matemática I')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('insertMateria')
        ->once()
        ->andReturn($materiaCreada);

    $result = $this->service->createMateria($request);

    expect($result->titulo)->toBe('Matemática I');
});

test('retorna MateriaResponse con los datos correctos', function () {
    $request = new MateriaRequest(titulo: 'Química');

    $materiaCreada = Materia::create('Química');
    $materiaCreada->setId(42);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('insertMateria')
        ->once()
        ->andReturn($materiaCreada);

    $result = $this->service->createMateria($request);

    expect($result)
        ->toHaveProperties(['id', 'titulo']);
    expect($result->id)->toEqual(42);
    expect($result->titulo)->toEqual('Química');
});

test('elimina una materia exitosamente', function () {
    $materia = Materia::create('Historia');
    $materia->setId(1);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($materia);

    $this->repositoryMock
        ->shouldReceive('delete')
        ->with(1)
        ->once()
        ->andReturn(true);

    $this->service->deleteMateria(1);

    // Si llega aquí sin excepción, el test pasa
    expect(true)->toBeTrue();
});

test('lanza MateriaNotFoundException al eliminar materia inexistente', function () {
    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->deleteMateria(999))
        ->toThrow(MateriaNotFoundException::class);
});
