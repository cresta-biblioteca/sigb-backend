<?php

use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Libros\Repositories\LibroRepository;
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
        'id'                => 1,
        'fecha_reserva'     => '2026-04-07 10:00:00',
        'fecha_vencimiento' => '2026-04-8 10:00:00',
        'estado'            => EstadoReserva::PENDIENTE->value,
        'lector_id'         => 1,
        'articulo_id'       => 1,
        'ejemplar_id'       => 1,
        'created_at'        => '2026-04-07 10:00:00',
        'updated_at'        => null,
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
        $this->articu
    );
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
   $reserva = null;

   $this->reservaRepositoryMock
       ->shouldReceive('findById')
       ->once()
       ->with(1)
       ->andReturn($reserva);

   expect(fn() => $this->reservaService->getReservaById(1))
       ->toThrow(ReservaNotFoundException::class);
});


test("addReserva lanza excepcion not found por no encontrar el articulo de la request", function () {
    $this->libroRepositoryMock
});