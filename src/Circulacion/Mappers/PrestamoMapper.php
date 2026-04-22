<?php

declare(strict_types=1);

namespace App\Circulacion\Mappers;

use App\Circulacion\Dtos\Request\CreatePrestamoRequest;
use App\Circulacion\Dtos\Response\PrestamoResponse;
use App\Circulacion\Models\Prestamo;

class PrestamoMapper
{
    public static function toCreateRequest(array $data): CreatePrestamoRequest
    {
        return new CreatePrestamoRequest(
            reservaId: (int) $data["reserva_id"],
            tipoPrestamoId: (int) $data["tipo_prestamo_id"]
        );
    }

    public static function toResponse(Prestamo $prestamo): PrestamoResponse
    {
        return new PrestamoResponse(
            id: $prestamo->getId(),
            fechaPrestamo: $prestamo->getFechaPrestamo()->format('Y-m-d H:i:s'),
            fechaVencimiento: $prestamo->getFechaVencimiento()->format('Y-m-d H:i:s'),
            fechaDevolucion: $prestamo->getFechaDevolucion()?->format('Y-m-d H:i:s'),
            estado: $prestamo->getEstado()->value,
            tipoPrestamoId:   $prestamo->getTipoPrestamoId(),
            ejemplarId:       $prestamo->getEjemplarId(),
            lectorId:         $prestamo->getLectorId(),
            cantRenovaciones: $prestamo->getCantRenovaciones(),
            maxRenovaciones:  $prestamo->getMaxRenovaciones(),
            tipoPrestamo:     $prestamo->getTipoPrestamo()?->toArray(),
            ejemplar: $prestamo->getEjemplar()?->toArray(),
            lector: $prestamo->getLector()?->toArray()
        );
    }
}
