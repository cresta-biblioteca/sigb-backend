<?php

declare(strict_types=1);

namespace App\Circulacion\Services;

use App\Auth\Exceptions\UserNotFoundException;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Circulacion\Dtos\Request\CreatePrestamoRequest;
use App\Circulacion\Dtos\Response\PrestamoResponse;
use App\Circulacion\Exceptions\EjemplarNoDisponibleException;
use App\Circulacion\Exceptions\EjemplarNotFoundException;
use App\Circulacion\Exceptions\LimitePrestamosSuperadoException;
use App\Circulacion\Exceptions\PrestamoNotFoundException;
use App\Circulacion\Exceptions\PrestamoYaDevueltoException;
use App\Circulacion\Exceptions\RenovacionNoPermitidaException;
use App\Circulacion\Exceptions\ReservaNoCompletableException;
use App\Circulacion\Exceptions\ReservaNotFoundException;
use App\Circulacion\Exceptions\TipoPrestamoDeshabilitadoException;
use App\Circulacion\Exceptions\TipoPrestamoNotFoundException;
use App\Circulacion\Mappers\PrestamoMapper;
use App\Circulacion\Models\EstadoPrestamo;
use App\Circulacion\Models\EstadoReserva;
use App\Circulacion\Models\Prestamo;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Models\TipoPrestamo;
use App\Circulacion\Repositories\PrestamoRepository;
use App\Circulacion\Repositories\ReservaRepository;
use App\Circulacion\Repositories\TipoPrestamoRepository;
use App\Lectores\Repositories\LectorRepository;
use DateTimeImmutable;
use PDO;

class PrestamoService
{
    public function __construct(
        private PDO $pdo,
        private PrestamoRepository $prestamoRepo,
        private ReservaRepository $reservaRepo,
        private TipoPrestamoRepository $tipoPrestamoRepo,
        private EjemplarRepository $ejemplarRepo,
        private LectorRepository $lectorRepo
    ) {
    }

    public function getAll(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $result = $this->prestamoRepo->findAllPaginated($page, $perPage, $filters);

        $responses = array_map(
            fn(Prestamo $p) => PrestamoMapper::toResponse($p),
            $result['data']
        );

        return [
            'data'     => $responses,
            'total'    => $result['total'],
            'page'     => $result['page'],
            'per_page' => $result['per_page'],
        ];
    }

    public function getById(int $id): PrestamoResponse
    {
        $prestamo = $this->prestamoRepo->findByIdWithRelations($id);
        if ($prestamo === null) {
            throw new PrestamoNotFoundException();
        }

        return PrestamoMapper::toResponse($prestamo);
    }

    public function getByLectorId(int $lectorId, ?string $estado = null): array
    {
        if (!$this->lectorRepo->exists($lectorId)) {
            throw new UserNotFoundException();
        }

        $estadoEnum = $estado !== null ? EstadoPrestamo::from(strtoupper($estado)) : null;
        $prestamos = $this->prestamoRepo->findByLectorId($lectorId, $estadoEnum);

        return array_map(
            fn(Prestamo $p) => PrestamoMapper::toResponse($p),
            $prestamos
        );
    }

    public function createPrestamo(CreatePrestamoRequest $request): PrestamoResponse
    {
        /** @var ?Reserva $reserva */
        $reserva = $this->reservaRepo->findById($request->reservaId);
        if ($reserva === null) {
            throw new ReservaNotFoundException();
        } elseif (!$reserva->isPendiente() || $reserva->isVencida()) {
            throw new ReservaNoCompletableException();
        }

        $ejemplarId = $reserva->getEjemplarId();
        if ($ejemplarId === null) {
            throw new EjemplarNoDisponibleException();
        }
        /** @var Ejemplar $ejemplar */
        $ejemplar = $this->ejemplarRepo->findById($ejemplarId);
        if (!$ejemplar->isHabilitado()) {
            throw new EjemplarNoDisponibleException();
        }

        /** @var ?TipoPrestamo $tipoPrestamo */
        $tipoPrestamo = $this->tipoPrestamoRepo->findById($request->tipoPrestamoId);
        if ($tipoPrestamo === null) {
            throw new TipoPrestamoNotFoundException();
        } elseif (!$tipoPrestamo->isHabilitado()) {
            throw new TipoPrestamoDeshabilitadoException();
        }

        $prestamosActivos = $this->prestamoRepo->countPrestamosActivosByLectorAndTipo(
            $reserva->getLectorId(),
            $tipoPrestamo->getId()
        );

        if ($prestamosActivos >= $tipoPrestamo->getMaxCantidadPrestamos()) {
            throw new LimitePrestamosSuperadoException($tipoPrestamo->getMaxCantidadPrestamos());
        }

        $ejemplar = $this->ejemplarRepo->findById($ejemplarId);
        if ($ejemplar === null) {
            throw new EjemplarNotFoundException();
        }

        $fechaPrestamo = new DateTimeImmutable();
        $fechaVencimiento = $fechaPrestamo->modify(
            "+{$tipoPrestamo->getDuracionPrestamo()} days"
        );

        $prestamo = Prestamo::create(
            fechaPrestamo: $fechaPrestamo,
            fechaVencimiento: $fechaVencimiento,
            tipoPrestamoId: $tipoPrestamo->getId(),
            ejemplarId: $ejemplarId,
            lectorId: $reserva->getLectorId()
        );
        $this->pdo->beginTransaction();

        try {
            $this->prestamoRepo->insertPrestamo($prestamo);

            $this->reservaRepo->completeReserva($reserva->getId(), EstadoReserva::COMPLETADA);

            // Cargar relaciones para la respuesta
            $prestamoConRelaciones = $this->prestamoRepo->findByIdWithRelations($prestamo->getId());

            $this->pdo->commit();

            return PrestamoMapper::toResponse($prestamoConRelaciones);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function devolver(int $id, bool $huboInconveniente = false): PrestamoResponse
    {
        /** @var ?Prestamo $prestamo */
        $prestamo = $this->prestamoRepo->findById($id);
        if ($prestamo === null) {
            throw new PrestamoNotFoundException();
        }

        if ($prestamo->isDevuelto()) {
            throw new PrestamoYaDevueltoException();
        }

        $prestamo->devolver($huboInconveniente);
        $this->prestamoRepo->updatePrestamo($prestamo);

        // Recargar con relaciones
        $prestamoConRelaciones = $this->prestamoRepo->findByIdWithRelations($id);

        return PrestamoMapper::toResponse($prestamoConRelaciones);
    }

    public function renovar(int $prestamoId, ?int $tipoPrestamoId = null): PrestamoResponse
    {
        /** @var ?Prestamo $prestamo */
        $prestamo = $this->prestamoRepo->findById($prestamoId);
        if ($prestamo === null) {
            throw new PrestamoNotFoundException();
        }

        if ($prestamo->isDevuelto()) {
            throw new RenovacionNoPermitidaException('el préstamo ya fue devuelto');
        }

        if ($prestamo->isVencido()) {
            throw new RenovacionNoPermitidaException('el préstamo está vencido');
        }

        if (!$prestamo->isActivo()) {
            throw new RenovacionNoPermitidaException('el préstamo no está en un estado renovable');
        }

        if ($tipoPrestamoId) {
            /** @var ?TipoPrestamo $tipoPrestamo */
            $tipoPrestamo = $this->tipoPrestamoRepo->findById($tipoPrestamoId);
            if ($tipoPrestamo === null) {
                throw new TipoPrestamoNotFoundException();
            } elseif (!$tipoPrestamo->isHabilitado()) {
                throw new TipoPrestamoDeshabilitadoException();
            }

            $prestamo->setTipoPrestamo($tipoPrestamo);
        } else {
            /** @var ?TipoPrestamo $tipoPrestamo */
            $tipoPrestamo = $this->tipoPrestamoRepo->findById($prestamo->getTipoPrestamoId());
            if ($tipoPrestamo === null) {
                throw new TipoPrestamoNotFoundException();
            }
            if ($tipoPrestamo->getRenovaciones() === 0) {
                throw new RenovacionNoPermitidaException(
                    'el tipo de préstamo no permite renovaciones'
                );
            }
        }

        // Validar período de renovación (cant_dias_renovar antes del vencimiento)
        $diasParaVencimiento = (int) (new DateTimeImmutable())
            ->diff($prestamo->getFechaVencimiento())
            ->format('%r%a');

        if ($diasParaVencimiento > $tipoPrestamo->getCantDiasRenovar()) {
            throw new RenovacionNoPermitidaException(
                "solo se puede renovar dentro de los {$tipoPrestamo->getCantDiasRenovar()} "
                . "días previos al vencimiento"
            );
        }

        // Validar que no haya reservas pendientes para el mismo artículo
        /** @var Ejemplar $ejemplar */
        $ejemplar = $this->ejemplarRepo->findById($prestamo->getEjemplarId());
        if ($ejemplar !== null) {
            $hayReservasPendientes = $this->reservaRepo
                ->existeReservaPendienteParaArticulo($ejemplar->getArticuloId());

            if ($hayReservasPendientes) {
                throw new RenovacionNoPermitidaException(
                    'hay lectores con reservas pendientes para este artículo'
                );
            }
        }

        // Calcular nueva fecha de vencimiento desde ahora
        $nuevaFechaVencimiento = (new DateTimeImmutable())->modify(
            "+{$tipoPrestamo->getDiasRenovacion()} days"
        );

        $prestamo->renovar($nuevaFechaVencimiento);
        $this->prestamoRepo->updatePrestamo($prestamo);

        // Recargar con relaciones
        $prestamoConRelaciones = $this->prestamoRepo->findByIdWithRelations($prestamoId);

        return PrestamoMapper::toResponse($prestamoConRelaciones);
    }
}
