<?php

declare(strict_types=1);

namespace App\Circulacion\Services;

use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Dtos\Response\ReservaResponse;
use App\Circulacion\Exceptions\LectorYaTieneReservaOPrestamoException;
use App\Circulacion\Exceptions\ReservaCannotBeCancelledException;
use App\Circulacion\Exceptions\ReservaNotFoundException;
use App\Circulacion\Models\EstadoReserva;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Shared\Database\Connection;
use App\Shared\HorarioBiblioteca;
use App\Shared\Security\OwnershipGuard;
use DateMalformedStringException;
use DateTimeImmutable;
use Throwable;

readonly class ReservaService
{
    public function __construct(
        private ReservaRepository $reservaRepository,
        private PrestamoRepository $prestamoRepository,
        private EjemplarRepository $ejemplarRepository,
        private ArticuloRepository $articuloRepository
    ) {
    }

    /**
     * @param array{
     *     estado?: string,
     *     lector_id?: int,
     *     articulo_id?: int,
     *     ejemplar_id?: int,
     *     fecha_desde?: string,
     *     fecha_hasta?: string
     * } $filters
     * @return array{items: ReservaResponse[], pagination: array<string, int>}
     */
    public function getReservas(array $filters, int $page, int $perPage): array
    {
        return $this->paginate($filters, $page, $perPage);
    }

    /**
     * @return array{items: ReservaResponse[], pagination: array<string, int>}
     */
    public function getMisReservas(int $lectorId, ?EstadoReserva $estado, int $page, int $perPage): array
    {
        $filters = ['lector_id' => $lectorId];
        if ($estado !== null) {
            $filters['estado'] = $estado->value;
        }

        return $this->paginate($filters, $page, $perPage);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: ReservaResponse[], pagination: array<string, int>}
     */
    private function paginate(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $offset = ($page - 1) * $perPage;

        $total = $this->reservaRepository->countByFilters($filters);
        $items = array_map(
            fn(Reserva $r) => ReservaResponse::fromReserva($r),
            $this->reservaRepository->findByFilters($filters, $perPage, $offset)
        );

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int)ceil($total / $perPage) : 1,
            ],
        ];
    }

    public function getReservaById(int $reservaId): ReservaResponse
    {
        $reserva = $this->reservaRepository->findById($reservaId);
        if ($reserva === null) {
            throw new ReservaNotFoundException();
        }

        return ReservaResponse::fromReserva($reserva);
    }

    /**
     * @throws ArticuloNotFoundException
     */
    public function addReserva(CreateReservaRequest $request): ReservaResponse
    {
        if (!$this->articuloRepository->exists($request->articuloId)) {
            throw new ArticuloNotFoundException();
        }

        // Para que el usuario no acapare el stock
        $tieneReserva = $this->reservaRepository
            ->lectorTieneReservaPendienteParaArticulo($request->lectorId, $request->articuloId);
        $tienePrestamo = $this->prestamoRepository
            ->lectorTienePrestamoActivoParaArticulo($request->lectorId, $request->articuloId);

        // Las dos condiciones son exclusivas, nunca ambas podran dar true
        if ($tieneReserva || $tienePrestamo) {
            throw new LectorYaTieneReservaOPrestamoException();
        }

        $ejemplarDisponible = $this->ejemplarRepository->getEjemplarDisponibleByArticuloId($request->articuloId);

        if ($ejemplarDisponible !== null) {
            // Hay ejemplar disponible: se asigna de inmediato con fecha de vencimiento
            $fechaVencimiento = HorarioBiblioteca::calcularVencimientoReserva(new DateTimeImmutable());
            $reserva = Reserva::create(
                $request->lectorId,
                $request->articuloId,
                $ejemplarDisponible->getId(),
                $fechaVencimiento
            );
        } else {
            // Sin ejemplar disponible: reserva en cola, ejemplar y vencimiento se asignan luego
            $reserva = Reserva::create($request->lectorId, $request->articuloId);
        }

        $this->reservaRepository->save($reserva);

        return ReservaResponse::fromReserva($reserva);
    }

    public function cancelarReserva(int $idReserva): void
    {
        $reserva = $this->reservaRepository->findById($idReserva);
        if ($reserva === null) {
            throw new ReservaNotFoundException();
        }

        OwnershipGuard::assertLector(fn() => $reserva->getLectorId());

        if (!$reserva->isPendiente()) {
            throw new ReservaCannotBeCancelledException("Solo reservas en estado PENDIENTE pueden ser canceladas");
        }
        if ($reserva->isVencida()) {
            throw new ReservaCannotBeCancelledException(
                "La reserva no puede ser cancelada porque ya venció el plazo para hacerlo."
            );
        }

        $reserva->cancelar();
        $this->reservaRepository->update($reserva);

        // TODO: enviar mail al usuario cuando se implemente mail sender
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    public function expirarReservasVencidas(): void
    {
        $vencidas = $this->reservaRepository->getVencidasPendientes();
        $pdo = Connection::getInstance();

        foreach ($vencidas as $reserva) {
            $pdo->beginTransaction();
            try {
                $reserva->marcarVencida();
                $this->reservaRepository->update($reserva);

                $proximaEnCola = $this->reservaRepository->getProximaEnCola($reserva->getArticuloId());

                // Si hay un usuario en cola de espera le asignamos el ejemplar que dejamos libre
                if ($proximaEnCola !== null) {
                    $proximaEnCola->setEjemplarId($reserva->getEjemplarId());
                    $proximaEnCola->setFechaVencimiento(
                        HorarioBiblioteca::calcularVencimientoReserva(new DateTimeImmutable())
                    );
                    $this->reservaRepository->update($proximaEnCola);
                }

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    }
}
