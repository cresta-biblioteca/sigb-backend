<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Models;

use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Entity;

class Ejemplar extends Entity
{
    private const CODIGO_BARRAS_PATTERN = '/^\d{1,13}$/';

    private ?string $codigoBarras;
    private int $articuloId;
    private ?string $signaturaTopografica = null;

    private ?Articulo $articulo = null;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Ejemplar (valida datos)
     */
    public static function create(
        int $articuloId,
        ?string $codigoBarras = null,
        ?string $signaturaTopografica = null
    ): self {
        $ejemplar = new self();
        $ejemplar->setArticuloId($articuloId);
        $ejemplar->setCodigoBarras($codigoBarras);
        $ejemplar->setSignaturaTopografica($signaturaTopografica);

        return $ejemplar;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $ejemplar = new self();
        $ejemplar->id = (int) $row['id'];
        $ejemplar->codigoBarras = $row['codigo_barras'];
        $ejemplar->articuloId = (int) $row['articulo_id'];
        $ejemplar->signaturaTopografica = $row['signatura_topografica'] ?? null;
        $ejemplar->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            $row['deleted_at'] ?? null
        );

        return $ejemplar;
    }

    public function getCodigoBarras(): ?string
    {
        return $this->codigoBarras;
    }

    public function setCodigoBarras(?string $codigoBarras): void
    {
        if ($codigoBarras !== null) {
            $this->assertMatchesPattern(
                $codigoBarras,
                self::CODIGO_BARRAS_PATTERN,
                'codigo_barras',
                'El codigo de barras debe contener solo digitos (max 13)'
            );
        }
        $this->codigoBarras = $codigoBarras;
    }

    public function isActivo(): bool
    {
        return $this->deletedAt === null;
    }

    public function getArticuloId(): int
    {
        return $this->articuloId;
    }

    public function setArticuloId(int $articuloId): void
    {
        $this->assertPositive($articuloId, 'articulo_id');
        $this->articuloId = $articuloId;
    }

    public function getSignaturaTopografica(): ?string
    {
        return $this->signaturaTopografica;
    }

    public function setSignaturaTopografica(?string $signaturaTopografica): void
    {
        if ($signaturaTopografica !== null) {
            $this->assertMaxLength($signaturaTopografica, 200, 'signatura_topografica');
        }
        $this->signaturaTopografica = $signaturaTopografica;
    }

    public function getArticulo(): ?Articulo
    {
        return $this->articulo;
    }

    public function setArticulo(Articulo $articulo): void
    {
        $this->articulo = $articulo;
        $this->articuloId = $articulo->getId();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'codigo_barras' => $this->codigoBarras,
            'activo' => $this->isActivo(),
            'articulo_id' => $this->articuloId,
            'signatura_topografica' => $this->signaturaTopografica,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->articulo !== null) {
            $data['articulo'] = $this->articulo->toArray();
        }

        return $data;
    }
}
