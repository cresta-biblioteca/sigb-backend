<?php

declare(strict_types=1);

namespace App\Circulacion\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PrestamoResponse",
    required: [
        "id",
        "fecha_prestamo",
        "fecha_vencimiento",
        "estado",
        "tipo_prestamo_id",
        "ejemplar_id",
        "lector_id",
        "cant_renovaciones",
        "max_renovaciones",
    ]
)]
readonly class PrestamoResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(property: "id", type: "integer", example: 1)]
        private int $id,
        #[OA\Property(property: "fecha_prestamo", type: "string", format: "date-time", example: "2026-04-12 14:00:00")]
        private string $fechaPrestamo,
        #[OA\Property(property: "fecha_vencimiento", type: "string", format: "date-time", example: "2026-04-27 14:00:00")]
        private string $fechaVencimiento,
        #[OA\Property(property: "fecha_devolucion", type: "string", format: "date-time", example: null, nullable: true)]
        private ?string $fechaDevolucion,
        #[OA\Property(property: "estado", type: "string", example: "VIGENTE")]
        private string $estado,
        #[OA\Property(property: "tipo_prestamo_id", type: "integer", example: 1)]
        private int $tipoPrestamoId,
        #[OA\Property(property: "ejemplar_id", type: "integer", example: 5)]
        private int $ejemplarId,
        #[OA\Property(property: "lector_id", type: "integer", example: 3)]
        private int $lectorId,
        #[OA\Property(property: "cant_renovaciones", type: "integer", example: 0)]
        private int $cantRenovaciones = 0,
        #[OA\Property(property: "max_renovaciones", type: "integer", example: 2)]
        private int $maxRenovaciones = 0,
        #[OA\Property(property: "tipo_prestamo", type: "object", nullable: true)]
        private ?array $tipoPrestamo = null,
        #[OA\Property(property: "ejemplar", type: "object", nullable: true)]
        private ?array $ejemplar = null,
        #[OA\Property(property: "lector", type: "object", nullable: true)]
        private ?array $lector = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'fecha_prestamo' => $this->fechaPrestamo,
            'fecha_vencimiento' => $this->fechaVencimiento,
            'fecha_devolucion' => $this->fechaDevolucion,
            'estado' => $this->estado,
            'tipo_prestamo_id'   => $this->tipoPrestamoId,
            'ejemplar_id'        => $this->ejemplarId,
            'lector_id'          => $this->lectorId,
            'cant_renovaciones'  => $this->cantRenovaciones,
            'max_renovaciones'   => $this->maxRenovaciones,
        ];

        if ($this->tipoPrestamo !== null) {
            $data['tipo_prestamo'] = $this->tipoPrestamo;
        }

        if ($this->ejemplar !== null) {
            $data['ejemplar'] = $this->ejemplar;
        }

        if ($this->lector !== null) {
            $data['lector'] = $this->lector;
        }

        return $data;
    }
}
