<?php

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Dtos\Response\EjemplarResponse;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Ejemplares\Services\EjemplarService;
use App\Shared\Exceptions\EntityAlreadyExistsException;
use App\Shared\Exceptions\EntityNotFoundException;

beforeEach(function () {
    $this->repositoryMock = $this->createMock(EjemplarRepository::class);
    $this->service = new EjemplarService($this->repositoryMock);
});

test('crea un ejemplar exitosamente', function () {
    $request = new EjemplarRequest(
        articuloId: 1,
        codigoBarras: '1234567890123',
        habilitado: true
    );

    $ejemplar = Ejemplar::create(1, '1234567890123', true);
    $ejemplar->setId(1);

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsEjemplarByCodigoBarras')
        ->with('1234567890123')
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('insertEjemplar')
        ->with($this->isInstanceOf(Ejemplar::class))
        ->willReturn($ejemplar);

    $result = $this->service->createEjemplar($request);

    expect($result)->toBeInstanceOf(EjemplarResponse::class);
    expect($result->id)->toBe(1);
    expect($result->articuloId)->toBe(1);
});

test('lanza excepcion si codigo de barras ya existe', function () {
    $request = new EjemplarRequest(
        articuloId: 2,
        codigoBarras: '1234567890123',
        habilitado: true
    );

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsEjemplarByCodigoBarras')
        ->with('1234567890123')
        ->willReturn(true);

    expect(fn () => $this->service->createEjemplar($request))
        ->toThrow(EntityAlreadyExistsException::class);
});

test('obtiene ejemplar por id exitosamente', function () {
    $ejemplar = Ejemplar::create(5, '12345', true);
    $ejemplar->setId(44);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(44)
        ->willReturn($ejemplar);

    $result = $this->service->getById(44);

    expect($result)->toBeInstanceOf(EjemplarResponse::class);
    expect($result->id)->toBe(44);
});

test('lanza excepcion al obtener ejemplar inexistente', function () {
    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    expect(fn () => $this->service->getById(999))
        ->toThrow(EntityNotFoundException::class);
});

test('actualiza ejemplar exitosamente', function () {
    $existing = Ejemplar::create(3, '11111', true);
    $existing->setId(8);

    $updated = Ejemplar::create(3, '22222', false);
    $updated->setId(8);

    $request = new EjemplarRequest(
        articuloId: 3,
        codigoBarras: '22222',
        habilitado: false
    );

    $this->repositoryMock
        ->expects($this->exactly(2))
        ->method('findById')
        ->with(8)
        ->willReturnOnConsecutiveCalls($existing, $updated);

    $this->repositoryMock
        ->expects($this->once())
        ->method('existsEjemplarByCodigoBarras')
        ->with('22222', 8)
        ->willReturn(false);

    $this->repositoryMock
        ->expects($this->once())
        ->method('updateEjemplar')
        ->with($this->isInstanceOf(Ejemplar::class))
        ->willReturn(true);

    $result = $this->service->updateEjemplar(8, $request);

    expect($result->codigoBarras)->toBe('22222');
    expect($result->habilitado)->toBeFalse();
});

test('deshabilita ejemplar exitosamente', function () {
    $ejemplar = Ejemplar::create(9, '77777', true);
    $ejemplar->setId(9);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findById')
        ->with(9)
        ->willReturn($ejemplar);

    $this->repositoryMock
        ->expects($this->once())
        ->method('updateEjemplar')
        ->with($this->isInstanceOf(Ejemplar::class))
        ->willReturn(true);

    $result = $this->service->deshabilitarEjemplar(9);

    expect($result->habilitado)->toBeFalse();
});

test('lista ejemplares por articulo', function () {
    $ejemplar1 = Ejemplar::create(20, '10001', true);
    $ejemplar1->setId(1);

    $ejemplar2 = Ejemplar::create(20, '10002', false);
    $ejemplar2->setId(2);

    $this->repositoryMock
        ->expects($this->once())
        ->method('findEjemplaresByArticuloId')
        ->with(20)
        ->willReturn([$ejemplar1, $ejemplar2]);

    $result = $this->service->getByArticuloId(20);

    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(EjemplarResponse::class);
});
