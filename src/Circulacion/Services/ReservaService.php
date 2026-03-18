<?php
declare(strict_types=1);

namespace App\Circulacion\Services;

use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Dtos\Response\ReservaResponse;
use App\Circulacion\Exceptions\LectorYaTieneReservaOPrestamoException;
use App\Circulacion\Exceptions\ReservaNotFoundException;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Shared\HorarioBiblioteca;
use DateTimeImmutable;

readonly class ReservaService
{
    public function __construct(
        private ReservaRepository  $reservaRepository,
        private PrestamoRepository $prestamoRepository,
        private EjemplarRepository $ejemplarRepository,
        private LibroRepository    $libroRepository
    )
    {
    }

    public function getReservaById(int $reservaId): ReservaResponse
    {
        $reserva = $this->reservaRepository->findById($reservaId);
        if ($reserva === null) {
            throw new ReservaNotFoundException($reservaId);
        }

        return ReservaResponse::fromReserva($reserva);
    }

    /**
     * @throws ArticuloNotFoundException
     */
    public function addReserva(CreateReservaRequest $request): ReservaResponse
    {
        if (!$this->libroRepository->exists($request->articuloId)) {
            throw new ArticuloNotFoundException($request->articuloId);
        }

        // Para que el usuario no acapare el stock
        if (
            $this->reservaRepository->lectorTieneReservaPendienteParaArticulo($request->lectorId, $request->articuloId)
            || $this->prestamoRepository->lectorTienePrestamoActivoParaArticulo($request->lectorId, $request->articuloId)
        ) {
            throw new LectorYaTieneReservaOPrestamoException($request->lectorId, $request->articuloId);
        }

        $ejemplarDisponible = $this->ejemplarRepository->getEjemplarDisponibleByArticuloId($request->articuloId);

        if ($ejemplarDisponible !== null) {
            // Hay ejemplar disponible: se asigna de inmediato con fecha de vencimiento
            $fechaVencimiento = HorarioBiblioteca::calcularVencimientoReserva(new DateTimeImmutable());
            $reserva = Reserva::create($request->lectorId, $request->articuloId, $ejemplarDisponible->getId(), $fechaVencimiento);
        } else {
            // Sin ejemplar disponible: reserva en cola, ejemplar y vencimiento se asignan luego
            $reserva = Reserva::create($request->lectorId, $request->articuloId);
        }

        $this->reservaRepository->save($reserva);

        return ReservaResponse::fromReserva($reserva);
    }
}
