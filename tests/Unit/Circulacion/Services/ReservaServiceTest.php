<?php

use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Exceptions\LectorYaTieneReservaOPrestamoException;
use App\Circulacion\Exceptions\ReservaCannotBeCancelledException;
use App\Circulacion\Exceptions\ReservaNotFoundException;
use App\Circulacion\Models\EstadoReserva;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Services\ReservaService;
use App\Circulacion\Services\TipoPrestamoService;
use Mockery\MockInterface;

/** @var MockInterface */
$repositoryMock = null;
/** @var TipoPrestamoService */
$service = null;

function makeReserva(array $override = []): Reserva
{
    return Reserva::fromDatabase(array_merge([
        'id' => 1,
        'fecha_reserva' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        'fecha_vencimiento' => (new DateTimeImmutable())->modify('+3 days')->format('Y-m-d H:i:s'),
        'estado' => EstadoReserva::PENDIENTE->value,
        'lector_id' => 1,
        'articulo_id' => 1,
        'ejemplar_id' => 1,
        'created_at' => '2026-04-07 10:00:00',
        'updated_at' => null,
    ], $override));
}

function makeEjemplar(array $override = []): Ejemplar
{
    return Ejemplar::fromDatabase(array_merge([
        'id' => 1,
        'codigo_barras' => '1234567890123',
        'deleted_at' => null,
        'articulo_id' => 1,
        'signatura_topografica' => 'A-123',
        'created_at' => '2026-04-07 10:00:00',
        'updated_at' => null,
    ], $override));
}

beforeEach(function () {
    $this->reservaRepositoryMock = Mockery::mock(ReservaRepository::class);
    $this->prestamoRepositoryMock = Mockery::mock(PrestamoRepository::class);
    $this->ejemplarRepositoryMock = Mockery::mock(EjemplarRepository::class);
    $this->articuloRepositoryMock = Mockery::mock(ArticuloRepository::class);
    $this->reservaService = new ReservaService(
        $this->reservaRepositoryMock,
        $this->prestamoRepositoryMock,
        $this->ejemplarRepositoryMock,
        $this->articuloRepositoryMock
    );
    $_SERVER['USER_ROLE'] = 'admin';
});

afterEach(function () {
    Mockery::close();
});

test("findById devuelve correctamente una reserva", function () {
    $reserva = makeReserva();

    $this->reservaRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);

    $response = $this->reservaService->getReservaById(1);

    expect($response->id)->toBe(1);
});

test("findById lanza una excepcion al no encontrar resultado", function () {
    $this->reservaRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn(null);

    expect(fn() => $this->reservaService->getReservaById(1))
        ->toThrow(ReservaNotFoundException::class);
});

test("addReserva lanza excepcion not found por no encontrar el articulo de la request", function () {
    $this->articuloRepositoryMock
        ->shouldReceive('exists')
        ->once()
        ->with(1)
        ->andReturn(false);

    expect(fn() => $this->reservaService->addReserva(new CreateReservaRequest(1, 1)))
        ->toThrow(ArticuloNotFoundException::class);
});

test("addReserva lanza una excepcion porque el lector ya tiene una reserva pendiente para el mismo articulo", function () {
    $this->articuloRepositoryMock
        ->shouldReceive('exists')
        ->once()
        ->with(1)
        ->andReturn(true);

    $this->reservaRepositoryMock
        ->shouldReceive('lectorTieneReservaPendienteParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(true);

    $this->prestamoRepositoryMock
        ->shouldReceive('lectorTienePrestamoActivoParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(false);

    expect(fn() => $this->reservaService->addReserva(new CreateReservaRequest(1, 1)))
        ->toThrow(LectorYaTieneReservaOPrestamoException::class);
});

test("addReserva lanza una excepcion porque el lector ya tiene un prestamo pendiente para el mismo articulo", function () {
    $this->articuloRepositoryMock
        ->shouldReceive('exists')
        ->once()
        ->with(1)
        ->andReturn(true);

    $this->reservaRepositoryMock
        ->shouldReceive('lectorTieneReservaPendienteParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(false);

    $this->prestamoRepositoryMock
        ->shouldReceive('lectorTienePrestamoActivoParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(true);

    expect(fn() => $this->reservaService->addReserva(new CreateReservaRequest(1, 1)))
        ->toThrow(LectorYaTieneReservaOPrestamoException::class);
});

test("addReserva crea una reserva con exito y con fecha de vencimiento ya que hay ejemplares disponibles", function () {
    $ejemplar = makeEjemplar();
    $reservaRequest = new CreateReservaRequest(1, 1);

    $this->articuloRepositoryMock
        ->shouldReceive('exists')
        ->once()
        ->with(1)
        ->andReturn(true);

    $this->reservaRepositoryMock
        ->shouldReceive('lectorTieneReservaPendienteParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(false);

    $this->prestamoRepositoryMock
        ->shouldReceive('lectorTienePrestamoActivoParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(false);

    $this->ejemplarRepositoryMock
        ->shouldReceive('getEjemplarDisponibleByArticuloId')
        ->once()
        ->with(1)
        ->andReturn($ejemplar);

    $this->reservaRepositoryMock
        ->shouldReceive('save')
        ->once()
        ->andReturnUsing(function (Reserva $reserva) {
            $reflection = new ReflectionProperty($reserva, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($reserva, 1);
        });

    $response = $this->reservaService->addReserva($reservaRequest);

    expect($response->lectorId)->toBe(1)
        ->and($response->articuloId)->toBe(1)
        ->and($response->fechaVencimiento)->not->toBeNull();
});

test("addReserva crea una reserva con exito sin fecha de vencimiento ya que no hay ejemplares disponibles", function () {
    $reservaRequest = new CreateReservaRequest(1, 1);

    $this->articuloRepositoryMock
        ->shouldReceive('exists')
        ->once()
        ->with(1)
        ->andReturn(true);

    $this->reservaRepositoryMock
        ->shouldReceive('lectorTieneReservaPendienteParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(false);

    $this->prestamoRepositoryMock
        ->shouldReceive('lectorTienePrestamoActivoParaArticulo')
        ->once()
        ->with(1, 1)
        ->andReturn(false);

    $this->ejemplarRepositoryMock
        ->shouldReceive('getEjemplarDisponibleByArticuloId')
        ->once()
        ->with(1)
        ->andReturn(null);

    $this->reservaRepositoryMock
        ->shouldReceive('save')
        ->once()
        // con reflexion seteo el id de la reserva
        ->andReturnUsing(function (Reserva $reserva) {
            $reflection = new ReflectionProperty($reserva, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($reserva, 1);
        });

    $response = $this->reservaService->addReserva($reservaRequest);

    expect($response->lectorId)->toBe(1)
        ->and($response->articuloId)->toBe(1)
        ->and($response->fechaVencimiento)->toBeNull();
});

test("cancelarReserva lanza una excepcion por no encontrar la reserva", function () {
    $this->reservaRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn(null);

    expect(fn() => $this->reservaService->cancelarReserva(1))
        ->toThrow(ReservaNotFoundException::class);
});

test("cancelarReserva lanza una excepcion porque solo reservas en estado PENDIENTE pueden ser canceladas", function () {
    $reserva = makeReserva(['estado' => EstadoReserva::CANCELADA->value]);

    $this->reservaRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);

    expect(fn() => $this->reservaService->cancelarReserva(1))
        ->toThrow(ReservaCannotBeCancelledException::class);
});

test("cancelarReserva lanza excepcion porque la reserva vencio su plazo limite", function () {
    $reserva = makeReserva([
        'estado' => EstadoReserva::PENDIENTE->value,
        'fecha_vencimiento' => (new DateTimeImmutable())->modify('-1 day')->format('Y-m-d H:i:s'),
    ]);

    $this->reservaRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);

    // Siempre va a comprar la fecha de vencimiento con la fecha actual
    expect(fn() => $this->reservaService->cancelarReserva(1))
        ->toThrow(ReservaCannotBeCancelledException::class);
});

test('cancelarReserva cancela con exito la reserva', function () {
    $reserva = makeReserva();

    $this->reservaRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($reserva);

    $this->reservaRepositoryMock
        ->shouldReceive('update')
        ->once();

    $this->reservaService->cancelarReserva(1);

    expect($reserva->getEstado())->toEqual(EstadoReserva::CANCELADA);
});