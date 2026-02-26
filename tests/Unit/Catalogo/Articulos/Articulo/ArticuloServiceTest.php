<?php

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Articulos\Services\ArticuloService;

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

    expect($result)->toBeInstanceOf(ArticuloResponse::class);
    expect($result->id)->toBe(10);
    expect($result->titulo)->toBe('Articulo de prueba');
    expect($result->tipoDocumentoId)->toBe(1);
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
    expect($result->id)->toBe(55);
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

test('actualiza articulo exitosamente', function () {
    $existing = Articulo::create('Titulo viejo', 2019, 1, 'es');
    $existing->setId(12);

    $updated = Articulo::create('Titulo nuevo', 2024, 1, 'es');
    $updated->setId(12);

    $request = new ArticuloRequest(
        titulo: 'Titulo nuevo',
        anioPublicacion: 2024,
        tipoDocumentoId: 1,
        idioma: 'es'
    );

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

    $result = $this->service->update(12, $request);

    expect($result->id)->toBe(12);
    expect($result->titulo)->toBe('Titulo nuevo');
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

    $this->service->delete(30);

    expect(true)->toBeTrue();
});

test('lista articulos paginados con metadatos', function () {
    $articulo1 = Articulo::create('A', 2020, 1, 'es');
    $articulo1->setId(1);

    $articulo2 = Articulo::create('B', 2021, 1, 'es');
    $articulo2->setId(2);

    $filters = ['titulo' => 'a'];

    $this->repositoryMock
        ->expects($this->once())
        ->method('countSearch')
        ->with($filters)
        ->willReturn(2);

    $this->repositoryMock
        ->expects($this->once())
        ->method('searchPaginated')
        ->with($filters, 1, 10)
        ->willReturn([$articulo1, $articulo2]);

    $result = $this->service->listPaginated($filters, 1, 10);

    expect($result['items'])->toHaveCount(2);
    expect($result['pagination']['total'])->toBe(2);
    expect($result['pagination']['page'])->toBe(1);
});
