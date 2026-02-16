<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Models;

use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Entity;

class Ejemplar extends Entity
{
    private const CODIGO_BARRAS_PATTERN = '/^\d{1,13}$/';

    private ?string $codigoBarras;
    private bool $habilitado;
    private int $articuloId;

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
        bool $habilitado = true
    ): self {
        $ejemplar = new self();
        $ejemplar->setArticuloId($articuloId);
        $ejemplar->setCodigoBarras($codigoBarras);
        $ejemplar->habilitado = $habilitado;

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
        $ejemplar->habilitado = (bool) $row['habilitado'];
        $ejemplar->articuloId = (int) $row['articulo_id'];
        $ejemplar->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
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

    public function isHabilitado(): bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): void
    {
        $this->habilitado = $habilitado;
    }

    public function habilitar(): void
    {
        $this->habilitado = true;
    }

    public function deshabilitar(): void
    {
        $this->habilitado = false;
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
            'habilitado' => $this->habilitado,
            'articulo_id' => $this->articuloId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->articulo !== null) {
            $data['articulo'] = $this->articulo->toArray();
        }

        return $data;
    }
}
