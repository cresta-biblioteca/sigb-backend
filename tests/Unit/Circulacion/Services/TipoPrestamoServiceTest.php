<?php

use App\Circulacion\Dtos\Request\CreateTipoPrestamoRequest;
use App\Circulacion\Dtos\Response\TipoPrestamoResponse;
use App\Circulacion\Exceptions\TipoPrestamoNotFoundException;
use App\Circulacion\Models\TipoPrestamo;
use App\Circulacion\Repositories\TipoPrestamoRepository;
use App\Circulacion\Services\TipoPrestamoService;
use Mockery\MockInterface;

/** @var MockInterface */
$repositoryMock = null;
/** @var TipoPrestamoService */
$service = null;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(TipoPrestamoRepository::class);
    $this->service = new TipoPrestamoService($this->repositoryMock);
});

afterEach(function () {
    Mockery::close();
});

test("getAll devuelve un array con TipoPrestamoResponse", function () {
    $tipoPrestamo1 = TipoPrestamo::create(
        codigo: "TP1",
        descripcion: "Tipo de prestamo 1",
        maxCantidadPrestamos: 5,
        duracionPrestamo: 14,
        renovaciones: 2,
        diasRenovacion: 3,
        cantDiasRenovar: 7
    );
    $tipoPrestamo2 = TipoPrestamo::create(
        codigo: "TP2",
        descripcion: "Tipo de prestamo 2",
        maxCantidadPrestamos: 3,
        duracionPrestamo: 7,
        renovaciones: 1,
        diasRenovacion: 2,
        cantDiasRenovar: 5
    );
    $tipoPrestamo1->setId(1);
    $tipoPrestamo2->setId(2);

    $this->repositoryMock
        ->shouldReceive("findAll")
        ->once()
        ->andReturn([$tipoPrestamo1, $tipoPrestamo2]);

    $result = $this->service->getAll();

    expect($result)
        ->toBeArray()
        ->toHaveCount(2);
    expect($result[0])
        ->toBeInstanceOf(TipoPrestamoResponse::class);
    expect($result[1])
        ->toBeInstanceOf(TipoPrestamoResponse::class);
});

test("getAll devuelve un array vacio", function () {
    $this->repositoryMock
        ->shouldReceive("findAll")
        ->once()
        ->andReturn([]);

    $result = $this->service->getAll();

    expect($result)
        ->toBeArray()
        ->toBeEmpty();
});

test("getById devuelve un TipoPrestamoResponse", function () {
    $tipoPrestamo = TipoPrestamo::create(
        codigo: "TP1",
        descripcion: "Tipo de prestamo 1",
        maxCantidadPrestamos: 5,
        duracionPrestamo: 14,
        renovaciones: 2,
        diasRenovacion: 3,
        cantDiasRenovar: 7
    );
    $tipoPrestamo->setId(1);

    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturn($tipoPrestamo);

    $result = $this->service->getById(1);

    expect($result)
        ->toBeInstanceOf(TipoPrestamoResponse::class);
});

test("getById lanza TipoPrestamoNotFoundException", function () {
    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->getById(1))
        ->toThrow(TipoPrestamoNotFoundException::class);
});

test("createTipoPrestamo devuelve TipoPrestamoResponse cuando se crea exitosamente ", function() {
    $tipoPrestamoRequest = new CreateTipoPrestamoRequest(
        "P07",
        "Prestamo 7 dias",
        30,
        7,
        5,
        7,
        1
    );
    $tipoPrestamoCreated = TipoPrestamo::create(
        codigo: $tipoPrestamoRequest->codigo,
        descripcion: $tipoPrestamoRequest->descripcion,
        maxCantidadPrestamos: $tipoPrestamoRequest->maxCantidadPrestamos,
        duracionPrestamo: $tipoPrestamoRequest->duracionPrestamo,
        renovaciones: $tipoPrestamoRequest->renovaciones,
        diasRenovacion: $tipoPrestamoRequest->diasRenovacion,
        cantDiasRenovar: $tipoPrestamoRequest->cantDiasRenovar
    );
    $tipoPrestamoCreated->setId(2);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with($tipoPrestamoRequest->codigo, $tipoPrestamoRequest->descripcion)
        ->once()
        ->andReturnNull();
    
    $this->repositoryMock
        ->shouldReceive("insertTipoPrestamo")
        ->withArgs(function (TipoPrestamo $tipoPrestamo) use ($tipoPrestamoRequest) {
            return $tipoPrestamo->getCodigo() === $tipoPrestamoRequest->codigo &&
                $tipoPrestamo->getDescripcion() === $tipoPrestamoRequest->descripcion &&
                $tipoPrestamo->getMaxCantidadPrestamos() === $tipoPrestamoRequest->maxCantidadPrestamos &&
                $tipoPrestamo->getDuracionPrestamo() === $tipoPrestamoRequest->duracionPrestamo &&
                $tipoPrestamo->getRenovaciones() === $tipoPrestamoRequest->renovaciones &&
                $tipoPrestamo->getDiasRenovacion() === $tipoPrestamoRequest->diasRenovacion &&
                $tipoPrestamo->getCantDiasRenovar() === $tipoPrestamoRequest->cantDiasRenovar;
            })
        ->once()
        ->andReturn($tipoPrestamoCreated);
    
    $result = $this->service->createTipoPrestamo($tipoPrestamoRequest);

    expect($result)
        ->toBeInstanceOf(TipoPrestamoResponse::class);
    expect($result->jsonSerialize())
        ->toMatchArray([
            "id" => 2,
            "codigo" => $tipoPrestamoRequest->codigo,
            "descripcion" => $tipoPrestamoRequest->descripcion,
            "max_cant_prestamos" => $tipoPrestamoRequest->maxCantidadPrestamos,
            "duracion" => $tipoPrestamoRequest->duracionPrestamo,
            "renovaciones" => $tipoPrestamoRequest->renovaciones,
            "dias_renovacion" => $tipoPrestamoRequest->diasRenovacion,
            "cant_dias_renovar" => $tipoPrestamoRequest->cantDiasRenovar,
            "activo" => true
        ]);
});

test("createTipoPrestamo lanza TipoPrestamoAlreadyExistsException cuando el codigo ya existe", function () {
    $tipoPrestamoRequest = new CreateTipoPrestamoRequest(
        "P07",
        "Prestamo 7 dias",
        30,
        7,
        5,
        7,
        1
    );
    $existingTipoPrestamo = TipoPrestamo::create(
        codigo: $tipoPrestamoRequest->codigo,
        descripcion: "Otra descripcion",
        maxCantidadPrestamos: 5,
        duracionPrestamo: 14,
        renovaciones: 2,
        diasRenovacion: 3,
        cantDiasRenovar: 7
    );
    $existingTipoPrestamo->setId(1);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with($tipoPrestamoRequest->codigo, $tipoPrestamoRequest->descripcion)
        ->once()
        ->andReturn($existingTipoPrestamo);

    expect(fn() => $this->service->createTipoPrestamo($tipoPrestamoRequest))
        ->toThrow(\App\Circulacion\Exceptions\TipoPrestamoAlreadyExistsException::class);
});

test("createTipoPrestamo lanza TipoPrestamoAlreadyExistsException cuando la descripcion ya existe", function () {
    $tipoPrestamoRequest = new CreateTipoPrestamoRequest(
        "P07",
        "Prestamo 7 dias",
        30,
        7,
        5,
        7,
        1
    );
    $existingTipoPrestamo = TipoPrestamo::create(
        codigo: "P08",
        descripcion: $tipoPrestamoRequest->descripcion,
        maxCantidadPrestamos: 5,
        duracionPrestamo: 14,
        renovaciones: 2,
        diasRenovacion: 3,
        cantDiasRenovar: 7
    );
    $existingTipoPrestamo->setId(1);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with($tipoPrestamoRequest->codigo, $tipoPrestamoRequest->descripcion)
        ->once()
        ->andReturn($existingTipoPrestamo);

    expect(fn() => $this->service->createTipoPrestamo($tipoPrestamoRequest))
        ->toThrow(\App\Circulacion\Exceptions\TipoPrestamoAlreadyExistsException::class);
});

test("updateTipoPrestamo actualiza y devuelve un TipoPrestamoResponse", function () {
    $updateRequest = new \App\Circulacion\Dtos\Request\UpdateTipoPrestamoRequest(
        "P08",
        "Prestamo 8 dias",
        20,
        8,
        4,
        8,
        2
    );

    $existingTipoPrestamo = TipoPrestamo::create(
        codigo: "P07",
        descripcion: "Prestamo 7 dias",
        maxCantidadPrestamos: 30,
        duracionPrestamo: 7,
        renovaciones: 5,
        diasRenovacion: 7,
        cantDiasRenovar: 1
    );
    $existingTipoPrestamo->setId(1);

    $updatedTipoPrestamo = TipoPrestamo::create(
        codigo: $updateRequest->codigo,
        descripcion: $updateRequest->descripcion,
        maxCantidadPrestamos: $updateRequest->maxCantidadPrestamos,
        duracionPrestamo: $updateRequest->duracionPrestamo,
        renovaciones: $updateRequest->renovaciones,
        diasRenovacion: $updateRequest->diasRenovacion,
        cantDiasRenovar: $updateRequest->cantDiasRenovar
    );
    $updatedTipoPrestamo->setId(1);

    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturn($existingTipoPrestamo);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with($updateRequest->codigo, $updateRequest->descripcion)
        ->once()
        ->andReturnNull();

    $this->repositoryMock
        ->shouldReceive("updateTipoPrestamo")
        ->with(1, Mockery::on(function ($arg) use ($updateRequest) {
            return $arg instanceof TipoPrestamo &&
                   $arg->getCodigo() === $updateRequest->codigo &&
                   $arg->getDescripcion() === $updateRequest->descripcion;
        }))
        ->once()
        ->andReturn($updatedTipoPrestamo);

    $result = $this->service->updateTipoPrestamo(1, $updateRequest);

    expect($result)->toBeInstanceOf(TipoPrestamoResponse::class);
});

test("updateTipoPrestamo lanza TipoPrestamoNotFoundException", function () {
    $updateRequest = new \App\Circulacion\Dtos\Request\UpdateTipoPrestamoRequest(
        "P08",
        "Prestamo 8 dias",
        20,
        8,
        4,
        8,
        2
    );

    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->updateTipoPrestamo(1, $updateRequest))
        ->toThrow(TipoPrestamoNotFoundException::class);
});

test("updateTipoPrestamo lanza TipoPrestamoAlreadyExistsException por codigo", function () {
    $updateRequest = new \App\Circulacion\Dtos\Request\UpdateTipoPrestamoRequest(
        "P08",
        "Prestamo 8 dias",
        null, null, null, null, null
    );

    $existingTipoPrestamo = TipoPrestamo::create("P07", 1, 1, 1, 1, 1, "Prestamo 7 dias");
    $existingTipoPrestamo->setId(1);

    $coincidentTipoPrestamo = TipoPrestamo::create("P08",1, 1, 1, 1, 1, "Otro prestamo");
    $coincidentTipoPrestamo->setId(2);

    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturn($existingTipoPrestamo);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with($updateRequest->codigo, $updateRequest->descripcion)
        ->once()
        ->andReturn($coincidentTipoPrestamo);

    expect(fn() => $this->service->updateTipoPrestamo(1, $updateRequest))
        ->toThrow(\App\Circulacion\Exceptions\TipoPrestamoAlreadyExistsException::class);
});

test("updateTipoPrestamo lanza TipoPrestamoAlreadyExistsException por descripcion", function () {
    $updateRequest = new \App\Circulacion\Dtos\Request\UpdateTipoPrestamoRequest(
        "P09",
        "Prestamo 8 dias",
        null, null, null, null, null
    );

    $existingTipoPrestamo = TipoPrestamo::create("P07",1, 1, 1, 1, 1, "Prestamo 7 dias");
    $existingTipoPrestamo->setId(1);

    $coincidentTipoPrestamo = TipoPrestamo::create("P08", 1, 1, 1, 1, 1, "Prestamo 8 dias");
    $coincidentTipoPrestamo->setId(2);

    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturn($existingTipoPrestamo);

    $this->repositoryMock
        ->shouldReceive("findCoincidence")
        ->with($updateRequest->codigo, $updateRequest->descripcion)
        ->once()
        ->andReturn($coincidentTipoPrestamo);

    expect(fn() => $this->service->updateTipoPrestamo(1, $updateRequest))
        ->toThrow(\App\Circulacion\Exceptions\TipoPrestamoAlreadyExistsException::class);
});

test("deleteTipoPrestamo elimina (soft delete) un tipo de prestamo", function () {
    $tipoPrestamo = TipoPrestamo::create("P07", 1, 1, 1, 1, 1, "Prestamo 7 dias");
    $tipoPrestamo->setId(1);

    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturn($tipoPrestamo);

    $this->repositoryMock
        ->shouldReceive("softDelete")
        ->with(1)
        ->once();

    $this->service->deleteTipoPrestamo(1);

    expect(true)->toBeTrue();
});

test("deleteTipoPrestamo lanza TipoPrestamoNotFoundException", function () {
    $this->repositoryMock
        ->shouldReceive("findById")
        ->with(1)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->deleteTipoPrestamo(1))
        ->toThrow(TipoPrestamoNotFoundException::class);
});