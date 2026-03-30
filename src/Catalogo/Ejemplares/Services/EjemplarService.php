<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Services;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Dtos\Response\EjemplarResponse;
use App\Catalogo\Ejemplares\Mappers\EjemplarMapper;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Shared\Exceptions\AlreadyExistsException;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Exceptions\NotFoundException;

class EjemplarService
{
    public function __construct(private readonly EjemplarRepository $ejemplarRepository)
    {
    }

    public function getAll(): array
    {
        $ejemplares = $this->ejemplarRepository->findAll();

        return array_map(
            static fn(Ejemplar $ejemplar): EjemplarResponse => EjemplarMapper::toResponse($ejemplar),
            $ejemplares
        );
    }

    public function getById(int $id): EjemplarResponse
    {
        return EjemplarMapper::toResponse($this->findOrFail($id));
    }

    public function createEjemplar(EjemplarRequest $request): EjemplarResponse
    {
        if ($this->ejemplarRepository->existsEjemplarByCodigoBarras($request->getCodigoBarras())) {
            throw new AlreadyExistsException('El código de barras ya está en uso');
        }

        $ejemplar = Ejemplar::create(
            $request->getArticuloId(),
            $request->getCodigoBarras(),
            $request->isHabilitado(),
            $request->getSignaturaTopografica()
        );

        $savedEjemplar = $this->ejemplarRepository->insertEjemplar($ejemplar);

        return EjemplarMapper::toResponse($savedEjemplar);
    }

    public function updateEjemplar(int $id, EjemplarRequest $request): EjemplarResponse
    {
        $ejemplar = $this->findOrFail($id);

        if ($request->getArticuloId() !== $ejemplar->getArticuloId()) {
            throw new BusinessRuleException('El articulo_id del ejemplar no puede ser modificado', 'articulo_id');
        }

        if ($this->ejemplarRepository->existsEjemplarByCodigoBarras($request->getCodigoBarras(), $id)) {
            throw new AlreadyExistsException('El código de barras ya está en uso');
        }

        $ejemplar->setCodigoBarras($request->getCodigoBarras());
        $ejemplar->setHabilitado($request->isHabilitado());
        $ejemplar->setSignaturaTopografica($request->getSignaturaTopografica());

        $this->ejemplarRepository->updateEjemplar($ejemplar);

        return EjemplarMapper::toResponse($ejemplar);
    }

    public function deleteEjemplar(int $id): void
    {
        $this->findOrFail($id);
        $this->ejemplarRepository->delete($id);
    }

    public function getByCodigoBarras(string $codigoBarras): ?EjemplarResponse
    {
        $ejemplar = $this->ejemplarRepository->findEjemplarByCodigoBarras($codigoBarras);

        return $ejemplar === null ? null : EjemplarMapper::toResponse($ejemplar);
    }

    public function getByHabilitado(bool $habilitado): array
    {
        $ejemplares = $this->ejemplarRepository->findEjemplaresByHabilitado($habilitado);
        $ejemplaresDto = array_map(fn($ejemplar) => EjemplarMapper::toResponse($ejemplar), $ejemplares);

        return $ejemplaresDto;
    }

    public function getByArticuloId(int $articuloId): array
    {
        $ejemplares = $this->ejemplarRepository->findEjemplaresByArticuloId($articuloId);
        $ejemplaresDto = array_map(fn($ejemplar) => EjemplarMapper::toResponse($ejemplar), $ejemplares);

        return $ejemplaresDto;
    }

    public function getHabilitadosByArticuloId(int $articuloId): array
    {
        $ejemplares = $this->ejemplarRepository->findEjemplaresHabilitadosByArticuloId($articuloId);
        $ejemplaresDto = array_map(fn($ejemplar) => EjemplarMapper::toResponse($ejemplar), $ejemplares);

        return $ejemplaresDto;
    }


    public function habilitarEjemplar(int $id): EjemplarResponse
    {
        $ejemplar = $this->findOrFail($id);
        $ejemplar->habilitar();
        $this->ejemplarRepository->updateEjemplar($ejemplar);

        return EjemplarMapper::toResponse($ejemplar);
    }


    public function deshabilitarEjemplar(int $id): EjemplarResponse
    {
        $ejemplar = $this->findOrFail($id);
        $ejemplar->deshabilitar();
        $this->ejemplarRepository->updateEjemplar($ejemplar);

        return EjemplarMapper::toResponse($ejemplar);
    }


    private function findOrFail(int $id): Ejemplar
    {
        /** @var ?Ejemplar $ejemplar */
        $ejemplar = $this->ejemplarRepository->findById($id);

        if ($ejemplar === null) {
            throw new NotFoundException('Ejemplar no encontrado');
        }

        return $ejemplar;
    }
}
