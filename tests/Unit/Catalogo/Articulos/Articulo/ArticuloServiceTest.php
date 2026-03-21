<?php

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyInArticuloException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Shared\Exceptions\BusinessRuleException;

beforeEach(function () {
    $this->repositoryMock = $this->createMock(ArticuloRepository::class);
    $this->service = new ArticuloService($this->repositoryMock);
});

test('crea un articulo exitosamente', function () {
    $request = new ArticuloRequest(
        titulo: 'Articulo de prueba',
        anioPublicacion: 2024,
        tipoDocumentoId: 1,
        idioma: 'es'
    );

    $articuloCreado = Articulo::create('Articulo de prueba', 2024, 1, 'es');
    $articuloCreado->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('insertArticulo')
        ->willReturn($articuloCreado);

    $result = $this->service->create($request);

    $data = $result->jsonSerialize();

    expect($result)->toBeInstanceOf(ArticuloResponse::class);
    expect($result->getId())->toBe(10);
    expect($data['titulo'])->toBe('Articulo de prueba');
    expect($data['tipo_documento_id'])->toBe(1);
});

test('obtiene articulo por id exitosamente', function () {
    $articulo = Articulo::create('Articulo por id', 2020, 2, 'es');
    $articulo->setId(55);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(55)
        ->willReturn($articulo);

    $result = $this->service->getById(55);

    expect($result)->toBeInstanceOf(ArticuloResponse::class);
    expect($result->getId())->toBe(55);
});

test('lanza excepcion al obtener articulo inexistente', function () {
    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    expect(fn () => $this->service->getById(999))
        ->toThrow(ArticuloNotFoundException::class);
});

test('actualiza articulo parcialmente exitosamente', function () {
    $existing = Articulo::create('Titulo viejo', 2019, 1, 'es');
    $existing->setId(12);

    $updated = Articulo::create('Titulo nuevo', 2019, 1, 'es');
    $updated->setId(12);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(12)
        ->willReturn($existing);

    $this->repositoryMock
        ->expects($this->once())
        ->method('updateArticulo')
        ->with(12, $this->isInstanceOf(Articulo::class))
        ->willReturn($updated);

    $result = $this->service->patchArticulo(12, [
        'titulo' => 'Titulo nuevo',
    ]);

    $data = $result->jsonSerialize();

    expect($result->getId())->toBe(12);
    expect($data['titulo'])->toBe('Titulo nuevo');
});

test('lanza excepcion cuando intenta cambiar tipo_documento_id y esta vinculado a libro', function () {
    $existing = Articulo::create('Titulo viejo', 2019, 1, 'es');
    $existing->setId(12);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(12)
        ->willReturn($existing);

    $this->repositoryMock
        ->expects($this->once())
        ->method('isLinkedToLibro')
        ->with(12)
        ->willReturn(true);

    expect(fn () => $this->service->patchArticulo(12, [
        'tipo_documento_id' => 2,
    ]))->toThrow(BusinessRuleException::class);
});

test('elimina articulo exitosamente', function () {
    $articulo = Articulo::create('Articulo a borrar', 2022, 1, 'es');
    $articulo->setId(30);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(30)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('delete')
        ->with(30);

    $this->service->deleteArticulo(30);
});

test('obtiene lista completa de articulos', function () {
    $articulo1 = Articulo::create('A', 2020, 1, 'es');
    $articulo1->setId(1);

    $articulo2 = Articulo::create('B', 2021, 1, 'es');
    $articulo2->setId(2);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findAll')
        ->willReturn([$articulo1, $articulo2]);

    $result = $this->service->getAll();

    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(ArticuloResponse::class);
    expect($result[1])->toBeInstanceOf(ArticuloResponse::class);
});

test('agrega tema a articulo exitosamente', function () {
    $articulo = Articulo::create('Articulo con tema', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('temaExists')
        ->with(5)
        ->willReturn(true);

    $this->repositoryMock
        ->expects($this->once())
        ->method('isTemaAdded')
        ->with(10, 5)
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('addTemaToArticulo')
        ->with(10, 5);

    $this->service->addTemaToArticulo(10, 5);

});

test('lanza excepcion al agregar tema a articulo inexistente', function () {
    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    expect(fn() => $this->service->addTemaToArticulo(999, 5))
        ->toThrow(ArticuloNotFoundException::class);
});

test('lanza excepcion al agregar tema inexistente', function () {
    $articulo = Articulo::create('Articulo con tema', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('temaExists')
        ->with(404)
        ->willReturn(false);

    expect(fn() => $this->service->addTemaToArticulo(10, 404))
        ->toThrow(TemaNotFoundException::class);
});

test('lanza excepcion cuando tema ya esta agregado al articulo', function () {
    $articulo = Articulo::create('Articulo con tema', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('temaExists')
        ->with(5)
        ->willReturn(true);

    $this->repositoryMock
        ->expects($this->once())
        ->method('isTemaAdded')
        ->with(10, 5)
        ->willReturn(true);

    expect(fn() => $this->service->addTemaToArticulo(10, 5))
        ->toThrow(TemaAlreadyInArticuloException::class);
});

test('elimina tema de articulo exitosamente', function () {
    $articulo = Articulo::create('Articulo con tema', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('temaExists')
        ->with(5)
        ->willReturn(true);

    $this->repositoryMock
        ->expects($this->once())
        ->method('isTemaAdded')
        ->with(10, 5)
        ->willReturn(true);

    $this->repositoryMock
        ->expects($this->once())
        ->method('deleteTemaFromArticulo')
        ->with(10, 5);

    $this->service->deleteTemaFromArticulo(10, 5);

});

test('lanza excepcion al eliminar tema de articulo inexistente', function () {
    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    expect(fn() => $this->service->deleteTemaFromArticulo(999, 5))
        ->toThrow(ArticuloNotFoundException::class);
});

test('lanza excepcion al eliminar tema inexistente', function () {
    $articulo = Articulo::create('Articulo con tema', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('temaExists')
        ->with(404)
        ->willReturn(false);

    expect(fn() => $this->service->deleteTemaFromArticulo(10, 404))
        ->toThrow(TemaNotFoundException::class);
});

test('lanza excepcion cuando tema ya no pertenece al articulo', function () {
    $articulo = Articulo::create('Articulo con tema', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('temaExists')
        ->with(5)
        ->willReturn(true);

    $this->repositoryMock
        ->expects($this->once())
        ->method('isTemaAdded')
        ->with(10, 5)
        ->willReturn(false);

    expect(fn() => $this->service->deleteTemaFromArticulo(10, 5))
        ->toThrow(TemaAlreadyEliminatedException::class);
});

test('obtiene titulos de temas por articulo id', function () {
    $articulo = Articulo::create('Articulo con temas', 2024, 1, 'es');
    $articulo->setId(10);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(10)
        ->willReturn($articulo);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findTemaTitlesByArticuloId')
        ->with(10)
        ->willReturn(['Arquitectura', 'Programacion']);

    $result = $this->service->getTemaTitlesByArticuloId(10);

    expect($result)->toBe(['Arquitectura', 'Programacion']);
});

test('lanza excepcion al obtener temas de articulo inexistente', function () {
    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    expect(fn() => $this->service->getTemaTitlesByArticuloId(999))
        ->toThrow(ArticuloNotFoundException::class);
});
