<?php

declare(strict_types=1);

namespace App\Circulacion\Services;

use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Dtos\Response\ReservaResponse;
use App\Circulacion\Exceptions\LectorYaTieneReservaOPrestamoException;
use App\Circulacion\Exceptions\ReservaCannotBeCanceledException;
use App\Circulacion\Exceptions\ReservaNotFoundException;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Shared\Database\Connection;
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
            throw new ReservaNotFoundException();
        }

        return ReservaResponse::fromReserva($reserva);
    }

    /**
     * @throws ArticuloNotFoundException
     */
    public function addReserva(CreateReservaRequest $request): ReservaResponse
    {
        if (!$this->libroRepository->exists($request->articuloId)) {
            throw new ArticuloNotFoundException();
        }

        // Para que el usuario no acapare el stock
        $tieneReserva = $this->reservaRepository
            ->lectorTieneReservaPendienteParaArticulo($request->lectorId, $request->articuloId);
        $tienePrestamo = $this->prestamoRepository
            ->lectorTienePrestamoActivoParaArticulo($request->lectorId, $request->articuloId);

        if ($tieneReserva || $tienePrestamo) {
            throw new LectorYaTieneReservaOPrestamoException($request->lectorId, $request->articuloId);
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
        if (!$reserva->isPendiente()) {
            throw new ReservaCannotBeCanceledException();
        }

        if ($this->isFechaDeCancelacionValida($reserva, new DateTimeImmutable())) {
            $reserva->cancelar();
            $this->reservaRepository->save($reserva);
        } else {
            throw new ReservaCannotBeCanceledException("La reserva no puede ser cancelada porque ya venció el plazo para hacerlo.");
        }

        // TODO: enviar mail al usuario cuando se implemente mail sender
    }

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
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    }

    private function isFechaDeCancelacionValida(Reserva $reserva, DateTimeImmutable $fechaActual): bool
    {
        // Si aun no la reserva sigue en cola de espera podra cancelarse sin problema
        if ($reserva->getFechaVencimiento() === null) {
            return true;
        }

        return $fechaActual <= $reserva->getFechaVencimiento();
    }
}
