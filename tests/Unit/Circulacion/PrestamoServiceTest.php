<?php

declare(strict_types=1);

use App\Auth\Exceptions\UserNotFoundException;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Circulacion\Dtos\Request\CreatePrestamoRequest;
use App\Circulacion\Dtos\Response\PrestamoResponse;
use App\Circulacion\Exceptions\EjemplarNoDisponibleException;
use App\Circulacion\Exceptions\LimitePrestamosSuperadoException;
use App\Circulacion\Exceptions\PrestamoNotFoundException;
use App\Circulacion\Exceptions\PrestamoYaDevueltoException;
use App\Circulacion\Exceptions\RenovacionNoPermitidaException;
use App\Circulacion\Exceptions\ReservaNoCompletableException;
use App\Circulacion\Exceptions\ReservaNotFoundException;
use App\Circulacion\Exceptions\TipoPrestamoNotFoundException;
use App\Circulacion\Exceptions\TipoPrestamoDeshabilitadoException;
use App\Circulacion\Models\EstadoPrestamo;
use App\Circulacion\Models\EstadoReserva;
use App\Circulacion\Models\Prestamo;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Models\TipoPrestamo;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Repositories\TipoPrestamoRepository;
use App\Lectores\Repositories\LectorRepository;
use App\Circulacion\Services\PrestamoService;

beforeEach(function () {
    $this->prestamoRepo = Mockery::mock(PrestamoRepository::class);
    $this->reservaRepo = Mockery::mock(ReservaRepository::class);
    $this->tipoPrestamoRepo = Mockery::mock(TipoPrestamoRepository::class);
    $this->ejemplarRepo = Mockery::mock(EjemplarRepository::class);
    $this->lectorRepo = Mockery::mock(LectorRepository::class);
    $this->pdo = Mockery::mock(PDO::class);
    $this->service = new PrestamoService(
        $this->pdo,
        $this->prestamoRepo,
        $this->reservaRepo,
        $this->tipoPrestamoRepo,
        $this->ejemplarRepo,
        $this->lectorRepo
    );
    $_SERVER['USER_ROLE'] = 'admin';
});

afterEach(function () {
    Mockery::close();
});

test('getAll retorna prestamos paginados', function () {
    $prestamo1 = Prestamo::create(
        new DateTimeImmutable('2026-04-01 10:00:00'),
        new DateTimeImmutable('2026-04-15 10:00:00'),
        1,
        1,
        1
    );
    $prestamo2 = Prestamo::create(
        new DateTimeImmutable('2026-04-02 11:00:00'),
        new DateTimeImmutable('2026-04-16 11:00:00'),
        1,
        2,
        1
    );
    $prestamo1->setId(1);
    $prestamo2->setId(2);
    $this->prestamoRepo
        ->shouldReceive('findAllPaginated')
        ->once()
        ->with(1, 15, [])
        ->andReturn([
            'data' => [$prestamo1, $prestamo2],
            'total' => 2,
            'page' => 1,
            'per_page' => 15
        ]);

    $result = $this->service->getAll(1, 15, []);

    expect($result['data'])->toBeArray()
        ->and($result['total'])->toBe(2)
        ->and($result['page'])->toBe(1)
        ->and($result['per_page'])->toBe(15);
});

test('getById retorna un prestamoResponse si existe', function () {
    $prestamo = Prestamo::create(
        new DateTimeImmutable('2026-04-01 10:00:00'),
        new DateTimeImmutable('2026-04-15 10:00:00'),
        1,
        1,
        1
    );
    $prestamo->setId(1);
    $this->prestamoRepo
        ->shouldReceive('findByIdWithRelations')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $result = $this->service->getById(1);

    expect($result)
        ->toBeInstanceOf(PrestamoResponse::class);
});

test("getById lanza excepcion si el prestamo no existe", function () {
    $this->prestamoRepo
        ->shouldReceive('findByIdWithRelations')
        ->once()
        ->with(999)
        ->andReturnNull();

    $this->service->getById(999);
})->throws(PrestamoNotFoundException::class);

test('getByLectorId retorna los prestamos de un lector', function () {
    $this->lectorRepo
        ->shouldReceive('exists')
        ->once()
        ->with(1)
        ->andReturn(true);

    $prestamo1 = Prestamo::create(
        new DateTimeImmutable('2026-04-01 10:00:00'),
        new DateTimeImmutable('2026-04-15 10:00:00'),
        1,
        1,
        1
    );
    $prestamo2 = Prestamo::create(
        new DateTimeImmutable('2026-04-02 11:00:00'),
        new DateTimeImmutable('2026-04-16 11:00:00'),
        1,
        2,
        1
    );
    $prestamo1->setId(1);
    $prestamo2->setId(2); 

    $this->prestamoRepo
        ->shouldReceive('findByLectorId')
        ->once()
        ->with(1, null)
        ->andReturn([$prestamo1, $prestamo2]);

    $result = $this->service->getByLectorId(1);

    expect($result)
        ->toBeArray()
        ->toHaveCount(2);
    expect($result[0])
        ->toBeInstanceOf(PrestamoResponse::class);
    expect($result[1])
        ->toBeInstanceOf(PrestamoResponse::class);
});

test("getByLectorId lanza excepcion si el lector no existe", function () {
    $this->lectorRepo
        ->shouldReceive('exists')
        ->once()
        ->with(999)
        ->andReturn(false);

    $this->service->getByLectorId(999);
})->throws(UserNotFoundException::class);


test("createPrestamo falla si no existe la reserva", function () {
    $request = new CreatePrestamoRequest(reservaId: 999, tipoPrestamoId: 1);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturnNull();

    $this->service->createPrestamo($request);
})->throws(ReservaNotFoundException::class);

test("createPrestamo falla si la reserva no esta pendiente", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(false);

    $this->service->createPrestamo($request);
})->throws(ReservaNoCompletableException::class);

test("createPrestamo falla si la reserva esta vencida", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(true);

    $this->service->createPrestamo($request);
})->throws(ReservaNoCompletableException::class);

test("createPrestamo falla si no hay un ejemplar asignado", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(false);
    $reserva
        ->shouldReceive('getEjemplarId')
        ->once()
        ->andReturnNull();

    $this->service->createPrestamo($request);
})->throws(EjemplarNoDisponibleException::class);

test("createPrestamo falla si el ejemplar no esta habilitado", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(false);
    $reserva
        ->shouldReceive('getEjemplarId')
        ->once()
        ->andReturn(5);

    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();

    $this->ejemplarRepo
        ->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($ejemplar);

    $ejemplar->shouldReceive("isHabilitado")->once()->andReturn(false);

    $this->service->createPrestamo($request);
})->throws(EjemplarNoDisponibleException::class);

test("createPrestamo falla si no existe el tipo de prestamo", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 999);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(false);
    $reserva
        ->shouldReceive('getEjemplarId')
        ->once()
        ->andReturn(5);

    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();
    $this->ejemplarRepo
        ->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($ejemplar);

    $ejemplar
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(true);

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(999)
        ->andReturnNull();

    $this->service->createPrestamo($request);
})->throws(TipoPrestamoNotFoundException::class);

test("createPrestamo falla si el tipo de prestamo no esta habilitado", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(false);
    $reserva
        ->shouldReceive('getEjemplarId')
        ->once()
        ->andReturn(5);

    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();

    $this->ejemplarRepo
        ->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($ejemplar);

    $ejemplar
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(true);

    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial(); 

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($tipoPrestamo);

    $tipoPrestamo
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(false);

    $this->service->createPrestamo($request);
})->throws(TipoPrestamoDeshabilitadoException::class);

test("createPrestamo falla si el lector supera el limite de prestamos activos del tipo", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $reserva = Mockery::mock(Reserva::class);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(false);
    $reserva
        ->shouldReceive('getEjemplarId')
        ->once()
        ->andReturn(5);
    $reserva
        ->shouldReceive("getLectorId")
        ->once()
        ->andReturn(1);

    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();

    $this->ejemplarRepo
        ->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($ejemplar);

    $ejemplar
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(true);

    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial(); 
    $tipoPrestamo->setId(1);

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($tipoPrestamo);

    $tipoPrestamo
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(true);

    $tipoPrestamo
        ->shouldReceive('getMaxCantidadPrestamos')
        ->andReturn(2);

    $this->prestamoRepo
        ->shouldReceive('countPrestamosActivosByLectorAndTipo')
        ->once()
        ->with(1, 1)
        ->andReturn(2);

    $this->service->createPrestamo($request);
})->throws(LimitePrestamosSuperadoException::class);

test("createPrestamo crea un prestamo correctamente", function () {
    $request = new CreatePrestamoRequest(reservaId: 1, tipoPrestamoId: 1);
    $prestamo = Prestamo::create(
        new DateTimeImmutable('2026-04-01 10:00:00'),
        new DateTimeImmutable('2026-04-15 10:00:00'),
        $request->tipoPrestamoId,
        5,
        1
    );
    $prestamo->setId(1);
    $reserva = Mockery::mock(Reserva::class);
    $reserva->shouldReceive('getId')->andReturn(1);
    $this->reservaRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);
    $reserva
        ->shouldReceive('isPendiente')
        ->once()
        ->andReturn(true);
    $reserva
        ->shouldReceive('isVencida')
        ->once()
        ->andReturn(false);
    $reserva
        ->shouldReceive('getEjemplarId')
        ->once()
        ->andReturn(5);
    $reserva
        ->shouldReceive("getLectorId")
        ->twice()
        ->andReturn(1);

    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();

    $this->ejemplarRepo
        ->shouldReceive('findById')
        ->once()
        ->with(5)
        ->andReturn($ejemplar);

    $ejemplar
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(true);

    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial(); 
    $tipoPrestamo->setId(1);

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($tipoPrestamo);

    $tipoPrestamo
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(true);

    $tipoPrestamo
        ->shouldReceive('getMaxCantidadPrestamos')
        ->andReturn(2);
    $tipoPrestamo
        ->shouldReceive('getDuracionPrestamo')
        ->andReturn(14);
    $tipoPrestamo
        ->shouldReceive('getRenovaciones')
        ->andReturn(3);

    $this->prestamoRepo
        ->shouldReceive('countPrestamosActivosByLectorAndTipo')
        ->once()
        ->with(1, 1)
        ->andReturn(0);

    $this->pdo
        ->shouldReceive('beginTransaction')
        ->once();

    $this->prestamoRepo
        ->shouldReceive('insertPrestamo')
        ->withArgs(function (Prestamo $p) use ($prestamo) {
            $p->setId(1);
            return $p->getTipoPrestamoId() === $prestamo->getTipoPrestamoId()
                && $p->getEjemplarId() === $prestamo->getEjemplarId()
                && $p->getLectorId() === $prestamo->getLectorId()
                && $p->getMaxRenovaciones() === 3;
        })
        ->once();

    $this->reservaRepo
        ->shouldReceive('completeReserva')
        ->once()
        ->with(1, EstadoReserva::COMPLETADA);

    $this->prestamoRepo
        ->shouldReceive('findByIdWithRelations')
        ->once()
        ->with(1)
        ->andReturn($prestamo);
    
    $this->pdo
        ->shouldReceive('commit')
        ->once();

    $this->pdo
        ->shouldReceive('rollBack')
        ->never();

    $result = $this->service->createPrestamo($request);
    expect($result)
        ->toBeInstanceOf(PrestamoResponse::class)
        ->and($result->jsonSerialize()['id'])->toBe(1);
});

test('devolver lanza excepcion si prestamo no existe', function () {
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturnNull();

    $this->service->devolver(1);
})->throws(PrestamoNotFoundException::class);

test('devolver lanza excepcion si ya esta devuelto', function () {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo
        ->shouldReceive('isDevuelto')
        ->once()
        ->andReturn(true);

    $this->service->devolver(1);
})->throws(PrestamoYaDevueltoException::class);

test('devolver devuelve el prestamo correctamente', function () {
    $prestamo = Prestamo::create(
        new DateTimeImmutable('2026-04-01 10:00:00'),
        (new DateTimeImmutable())->modify('+15 days'),
        1,
        1,
        1
    );
    $prestamo->setId(1);

    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $this->prestamoRepo
        ->shouldReceive('updatePrestamo')
        ->once()
        ->with($prestamo);
    $this->prestamoRepo
        ->shouldReceive('findByIdWithRelations')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $result = $this->service->devolver(1);
    expect($result)
        ->toBeInstanceOf(PrestamoResponse::class);
    expect($result->jsonSerialize()['estado'])
        ->toBe(EstadoPrestamo::COMPLETADO_EXITO->value);
});

test('renovar falla si el prestamo no existe', function () {
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturnNull();

    $this->service->renovar(1);
})->throws(PrestamoNotFoundException::class);

test('renovar falla si el tipo inicial no permitia renovaciones', function () {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo->shouldReceive('isDevuelto')->once()->andReturn(false);
    $prestamo->shouldReceive('isVencido')->once()->andReturn(false);
    $prestamo->shouldReceive('getMaxRenovaciones')->andReturn(0);

    $this->service->renovar(1);
})->throws(RenovacionNoPermitidaException::class);

test("renovar falla si el prestamo esta vencido", function () {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo
        ->shouldReceive('isDevuelto')
        ->once()
        ->andReturn(false);
    $prestamo
        ->shouldReceive('isVencido')
        ->once()
        ->andReturn(true);

    $this->service->renovar(1);
})->throws(RenovacionNoPermitidaException::class);

test("renovar falla si el prestamo ya fue devuelto", function () {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo
        ->shouldReceive('isDevuelto')
        ->once()
        ->andReturn(true);

    $this->service->renovar(1);
})->throws(RenovacionNoPermitidaException::class);

test("renovar falla si el nuevo tipo de prestamo no existe", function() {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo->shouldReceive('isDevuelto')->once()->andReturn(false);
    $prestamo->shouldReceive('isVencido')->once()->andReturn(false);
    $prestamo->shouldReceive('getMaxRenovaciones')->andReturn(2);
    $prestamo->shouldReceive('getCantRenovaciones')->andReturn(0);

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(2)
        ->andReturnNull();

    $this->service->renovar(1, 2);
})->throws(TipoPrestamoNotFoundException::class);

test("renovar falla si el nuevo tipo de prestamo no esta habilitado", function() {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo->shouldReceive('isDevuelto')->once()->andReturn(false);
    $prestamo->shouldReceive('isVencido')->once()->andReturn(false);
    $prestamo->shouldReceive('getMaxRenovaciones')->andReturn(2);
    $prestamo->shouldReceive('getCantRenovaciones')->andReturn(0);

    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial();
    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(2)
        ->andReturn($tipoPrestamo);

    $tipoPrestamo
        ->shouldReceive('isHabilitado')
        ->once()
        ->andReturn(false);

    $this->service->renovar(1, 2);
})->throws(TipoPrestamoDeshabilitadoException::class);

test("renovar falla si se alcanzo el limite de renovaciones", function() {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo->shouldReceive('isDevuelto')->once()->andReturn(false);
    $prestamo->shouldReceive('isVencido')->once()->andReturn(false);
    $prestamo->shouldReceive('getMaxRenovaciones')->andReturn(3);
    $prestamo->shouldReceive('getCantRenovaciones')->andReturn(3);

    $this->service->renovar(1);
})->throws(RenovacionNoPermitidaException::class);

test("renovar falla si los dias previos a la renovacion ya fueron superados", function() {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $prestamo->shouldReceive('getTipoPrestamoId')->andReturn(2);
    $prestamo->shouldReceive('getFechaVencimiento')
        ->andReturn((new DateTimeImmutable())->add(new DateInterval('P5D')));
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo->shouldReceive('isDevuelto')->once()->andReturn(false);
    $prestamo->shouldReceive('isVencido')->once()->andReturn(false);
    $prestamo->shouldReceive('getMaxRenovaciones')->andReturn(2);
    $prestamo->shouldReceive('getCantRenovaciones')->andReturn(0);

    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial();

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(2)
        ->andReturn($tipoPrestamo);

    $tipoPrestamo->shouldReceive('getCantDiasRenovar')->andReturn(3);

    $this->service->renovar(1);
})->throws(RenovacionNoPermitidaException::class);

test("renovar falla si ya hay una reserva pendiente para el ejemplar", function() {
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $prestamo->shouldReceive('getTipoPrestamoId')->andReturn(2);
    $prestamo->shouldReceive('getFechaVencimiento')
        ->andReturn((new DateTimeImmutable())->add(new DateInterval('P2D')));
    $prestamo->shouldReceive('getEjemplarId')->andReturn(10);
    $this->prestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $prestamo->shouldReceive('isDevuelto')->once()->andReturn(false);
    $prestamo->shouldReceive('isVencido')->once()->andReturn(false);
    $prestamo->shouldReceive('getMaxRenovaciones')->andReturn(2);
    $prestamo->shouldReceive('getCantRenovaciones')->andReturn(0);

    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial();

    $this->tipoPrestamoRepo
        ->shouldReceive('findById')
        ->once()
        ->with(2)
        ->andReturn($tipoPrestamo);

    $tipoPrestamo->shouldReceive('getCantDiasRenovar')->andReturn(3);

    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();
    $this->ejemplarRepo
        ->shouldReceive('findById')
        ->once()
        ->with(10)
        ->andReturn($ejemplar);
    $ejemplar
        ->shouldReceive('getArticuloId')
        ->once()
        ->andReturn(1);
    
    $this->reservaRepo
        ->shouldReceive('existeReservaPendienteParaArticulo')
        ->once()
        ->with(1)
        ->andReturn(true);

    $this->service->renovar(1);
})->throws(RenovacionNoPermitidaException::class);

test("renovar renueva correctamente un prestamo", function () {
    // --- Prestamo ---
    $prestamo = Mockery::mock(Prestamo::class)->makePartial();
    $prestamo->setId(1);
    $prestamo->allows([
        'getTipoPrestamoId'    => 2,
        'getEjemplarId'        => 10,
        'getLectorId'          => 99,
        'getFechaPrestamo'     => new DateTimeImmutable(),
        'getFechaVencimiento'  => (new DateTimeImmutable())->add(new DateInterval('P2D')),
        'getFechaDevolucion'   => null,
        'getEstado'            => EstadoPrestamo::VIGENTE,
        'getMaxRenovaciones'   => 2,
        'getCantRenovaciones'  => 0,
    ]);
    $prestamo
        ->expects('isDevuelto')
        ->once()
        ->andReturn(false);
    $prestamo
        ->expects('isVencido')
        ->once()
        ->andReturn(false);
    $prestamo
        ->expects('renovar')
        ->once()
        ->withArgs(fn($d) => $d instanceof DateTimeImmutable);

    // --- TipoPrestamo ---
    $tipoPrestamo = Mockery::mock(TipoPrestamo::class)->makePartial();
    $tipoPrestamo->allows([
        'getCantDiasRenovar' => 3,
        'getDiasRenovacion'  => 7,
    ]);

    // --- Ejemplar ---
    $ejemplar = Mockery::mock(Ejemplar::class)->makePartial();
    $ejemplar
        ->expects('getArticuloId')
        ->once()
        ->andReturn(1);

    // --- Repositorios ---
    $this->prestamoRepo
        ->expects('findById')
        ->once()
        ->with(1)
        ->andReturn($prestamo);
    $this->prestamoRepo
        ->expects('updatePrestamo')
        ->once()
        ->with($prestamo);
    $this->prestamoRepo
        ->expects('findByIdWithRelations')
        ->once()
        ->with(1)
        ->andReturn($prestamo);

    $this->tipoPrestamoRepo
        ->expects('findById')
        ->once()
        ->with(2)
        ->andReturn($tipoPrestamo);

    $this->ejemplarRepo
        ->expects('findById')
        ->once()
        ->with(10)
        ->andReturn($ejemplar);

    $this->reservaRepo
        ->expects('existeReservaPendienteParaArticulo')
        ->once()
        ->with(1)
        ->andReturn(false);

    // --- Assert ---
    expect($this->service->renovar(1))
        ->toBeInstanceOf(PrestamoResponse::class);
});