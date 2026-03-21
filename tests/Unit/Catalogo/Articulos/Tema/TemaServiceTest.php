<?php

use App\Catalogo\Articulos\Dtos\Request\TemaRequest;
use App\Catalogo\Articulos\Dtos\Response\TemaResponse;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Models\Tema;
use App\Catalogo\Articulos\Repository\TemaRepository;
use App\Catalogo\Articulos\Services\TemaService;
use App\Shared\Exceptions\BusinessRuleException;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(TemaRepository::class);
    $this->service = new TemaService($this->repositoryMock);
});

afterEach(function () {
    Mockery::close();
});

// ─── getAll ──────────────────────────────────────────────────────────

test('getAll retorna un array de TemaResponse', function () {
    $tema1 = Tema::create('Física');
    $tema1->setId(1);
    $tema2 = Tema::create('Química');
    $tema2->setId(2);

    $this->repositoryMock
        ->shouldReceive('findAll')
        ->once()
        ->andReturn([$tema1, $tema2]);

    $result = $this->service->getAll();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(TemaResponse::class);
    expect($result[1])->toBeInstanceOf(TemaResponse::class);
    expect($result[0]->jsonSerialize())->toMatchArray(['id' => 1, 'titulo' => 'Física']);
    expect($result[1]->jsonSerialize())->toMatchArray(['id' => 2, 'titulo' => 'Química']);
});

test('getAll retorna array vacío cuando no hay temas', function () {
    $this->repositoryMock
        ->shouldReceive('findAll')
        ->once()
        ->andReturn([]);

    $result = $this->service->getAll();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

// ─── getByParams ─────────────────────────────────────────────────────

test('getByParams retorna temas filtrados por parámetros', function () {
    $tema = Tema::create('Historia');
    $tema->setId(3);

    $params = ['titulo' => 'Historia'];

    $this->repositoryMock
        ->shouldReceive('findByParams')
        ->with($params)
        ->once()
        ->andReturn([$tema]);

    $result = $this->service->getByParams($params);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(TemaResponse::class);
    expect($result[0]->jsonSerialize())->toMatchArray(['id' => 3, 'titulo' => 'Historia']);
});

test('getByParams retorna array vacío cuando no hay coincidencias', function () {
    $params = ['titulo' => 'Inexistente'];

    $this->repositoryMock
        ->shouldReceive('findByParams')
        ->with($params)
        ->once()
        ->andReturn([]);

    $result = $this->service->getByParams($params);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getByParams sin parámetros retorna todos los temas', function () {
    $tema1 = Tema::create('Álgebra');
    $tema1->setId(1);
    $tema2 = Tema::create('Geometría');
    $tema2->setId(2);

    $this->repositoryMock
        ->shouldReceive('findByParams')
        ->with([])
        ->once()
        ->andReturn([$tema1, $tema2]);

    $result = $this->service->getByParams();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
});

// ─── getById ─────────────────────────────────────────────────────────

test('getById retorna TemaResponse cuando el tema existe', function () {
    $tema = Tema::create('Biología');
    $tema->setId(10);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(10)
        ->once()
        ->andReturn($tema);

    $result = $this->service->getById(10);

    expect($result)->toBeInstanceOf(TemaResponse::class);
    expect($result->jsonSerialize())->toMatchArray(['id' => 10, 'titulo' => 'Biología']);
});

test('getById lanza TemaNotFoundException cuando no existe', function () {
    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->getById(999))
        ->toThrow(TemaNotFoundException::class);
});

// ─── createTema ──────────────────────────────────────────────────────

test('crea un tema exitosamente', function () {
    $request = new TemaRequest(titulo: 'Matemática');

    $temaCreado = Tema::create('Matemática');
    $temaCreado->setId(1);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Matemática')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('insertTema')
        ->once()
        ->withArgs(fn(Tema $t) => $t->getTitulo() === 'Matemática')
        ->andReturn($temaCreado);

    $result = $this->service->createTema($request);

    expect($result)->toBeInstanceOf(TemaResponse::class);
    expect($result->jsonSerialize())->toMatchArray(['id' => 1, 'titulo' => 'Matemática']);
});

test('createTema lanza TemaAlreadyExistsException si ya existe', function () {
    $request = new TemaRequest(titulo: 'Matemática');

    $temaExistente = Tema::create('Matemática');
    $temaExistente->setId(1);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Matemática')
        ->once()
        ->andReturn($temaExistente);

    expect(fn() => $this->service->createTema($request))
        ->toThrow(TemaAlreadyExistsException::class);
});

test('createTema lanza BusinessRuleException si el título está vacío', function () {
    $request = new TemaRequest(titulo: '');

    $this->repositoryMock->shouldNotReceive('findCoincidence');
    $this->repositoryMock->shouldNotReceive('insertTema');

    expect(fn() => $this->service->createTema($request))
        ->toThrow(BusinessRuleException::class);
});

test('createTema lanza BusinessRuleException si el título excede 100 caracteres', function () {
    $tituloLargo = str_repeat('A', 101);
    $request = new TemaRequest(titulo: $tituloLargo);

    $this->repositoryMock->shouldNotReceive('findCoincidence');
    $this->repositoryMock->shouldNotReceive('insertTema');

    expect(fn() => $this->service->createTema($request))
        ->toThrow(BusinessRuleException::class);
});

test('createTema con título de espacios y caracteres válidos', function () {
    $request = new TemaRequest(titulo: 'Cálculo Integral II');

    $temaCreado = Tema::create('Cálculo Integral II');
    $temaCreado->setId(5);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Cálculo Integral II')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('insertTema')
        ->once()
        ->andReturn($temaCreado);

    $result = $this->service->createTema($request);

    expect($result->jsonSerialize()['titulo'])->toBe('Cálculo Integral II');
});

// ─── updateTema ──────────────────────────────────────────────────────

test('actualiza un tema exitosamente con título diferente', function () {
    $request = new TemaRequest(titulo: 'Física Moderna');

    $temaExistente = Tema::create('Física');
    $temaExistente->setId(1);

    $temaActualizado = Tema::create('Física Moderna');
    $temaActualizado->setId(1);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($temaExistente);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Física Moderna')
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive('updateTema')
        ->once()
        ->withArgs(fn(int $i, Tema $t) => $i === 1 && $t->getTitulo() === 'Física Moderna')
        ->andReturn($temaActualizado);

    $result = $this->service->updateTema(1, $request);

    expect($result)->toBeInstanceOf(TemaResponse::class);
    expect($result->jsonSerialize())->toMatchArray(['id' => 1, 'titulo' => 'Física Moderna']);
});

test('actualiza un tema exitosamente con el mismo título sin verificar duplicado', function () {
    $request = new TemaRequest(titulo: 'Química');

    $temaExistente = Tema::create('Química');
    $temaExistente->setId(2);

    $temaActualizado = Tema::create('Química');
    $temaActualizado->setId(2);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(2)
        ->once()
        ->andReturn($temaExistente);

    // No debería llamar a findCoincidence si el título no cambia
    $this->repositoryMock
        ->shouldNotReceive('findCoincidence');

    $this->repositoryMock
        ->shouldReceive('updateTema')
        ->once()
        ->andReturn($temaActualizado);

    $result = $this->service->updateTema(2, $request);

    expect($result)->toBeInstanceOf(TemaResponse::class);
    expect($result->jsonSerialize())->toMatchArray(['id' => 2, 'titulo' => 'Química']);
});

test('updateTema lanza TemaNotFoundException si el tema no existe', function () {
    $request = new TemaRequest(titulo: 'Cualquier cosa');

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->updateTema(999, $request))
        ->toThrow(TemaNotFoundException::class);
});

test('updateTema lanza TemaAlreadyExistsException si el nuevo título ya existe', function () {
    $request = new TemaRequest(titulo: 'Biología');

    $temaExistente = Tema::create('Física');
    $temaExistente->setId(1);

    $temaDuplicado = Tema::create('Biología');
    $temaDuplicado->setId(3);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($temaExistente);

    $this->repositoryMock
        ->shouldReceive('findCoincidence')
        ->with('Biología')
        ->once()
        ->andReturn($temaDuplicado);

    expect(fn() => $this->service->updateTema(1, $request))
        ->toThrow(TemaAlreadyExistsException::class);
});

test('updateTema lanza BusinessRuleException si el título está vacío', function () {
    $request = new TemaRequest(titulo: '');

    $this->repositoryMock->shouldNotReceive('findById');
    $this->repositoryMock->shouldNotReceive('findCoincidence');
    $this->repositoryMock->shouldNotReceive('updateTema');

    expect(fn() => $this->service->updateTema(1, $request))
        ->toThrow(BusinessRuleException::class);
});

test('updateTema lanza BusinessRuleException si el título excede 100 caracteres', function () {
    $tituloLargo = str_repeat('B', 101);
    $request = new TemaRequest(titulo: $tituloLargo);

    $this->repositoryMock->shouldNotReceive('findById');
    $this->repositoryMock->shouldNotReceive('findCoincidence');
    $this->repositoryMock->shouldNotReceive('updateTema');

    expect(fn() => $this->service->updateTema(1, $request))
        ->toThrow(BusinessRuleException::class);
});

// ─── deleteTema ──────────────────────────────────────────────────────

test('elimina un tema exitosamente', function () {
    $tema = Tema::create('Historia');
    $tema->setId(1);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(1)
        ->once()
        ->andReturn($tema);

    $this->repositoryMock
        ->shouldReceive('delete')
        ->with(1)
        ->once()
        ->andReturn(true);

    $this->service->deleteTema(1);

    expect(true)->toBeTrue();
});

test('deleteTema lanza TemaNotFoundException si el tema no existe', function () {
    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    $this->repositoryMock->shouldNotReceive('delete');

    expect(fn() => $this->service->deleteTema(999))
        ->toThrow(TemaNotFoundException::class);
});

test('deleteTema lanza TemaNotFoundException si delete retorna false', function () {
    $tema = Tema::create('Filosofía');
    $tema->setId(5);

    $this->repositoryMock
        ->shouldReceive('findById')
        ->with(5)
        ->once()
        ->andReturn($tema);

    $this->repositoryMock
        ->shouldReceive('delete')
        ->with(5)
        ->once()
        ->andReturn(false);

    expect(fn() => $this->service->deleteTema(5))
        ->toThrow(TemaNotFoundException::class);
});

