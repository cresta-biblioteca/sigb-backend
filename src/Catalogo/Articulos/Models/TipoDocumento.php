<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Models;

use App\Shared\Entity;

class TipoDocumento extends Entity
{
    private string $codigo;
    private string $descripcion;
    private bool $renovable;
    private ?string $detalle;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo TipoDocumento (valida datos)
     */
    public static function create(
        string $codigo,
        string $descripcion,
        bool $renovable = true,
        ?string $detalle = null
    ): self {
        $tipo = new self();
        $tipo->setCodigo($codigo);
        $tipo->setDescripcion($descripcion);
        $tipo->renovable = $renovable;
        $tipo->setDetalle($detalle);

        return $tipo;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $tipo = new self();
        $tipo->id = (int) $row['id'];
        $tipo->codigo = $row['codigo'];
        $tipo->descripcion = $row['descripcion'];
        $tipo->renovable = (bool) $row['renovable'];
        $tipo->detalle = $row['detalle'];

        return $tipo;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): void
    {
        $this->assertNotEmpty($codigo, 'codigo');
        $this->assertMaxLength($codigo, 3, 'codigo');
        $this->codigo = strtoupper($codigo);
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->assertNotEmpty($descripcion, 'descripcion');
        $this->assertMaxLength($descripcion, 100, 'descripcion');
        $this->descripcion = $descripcion;
    }

    public function isRenovable(): bool
    {
        return $this->renovable;
    }

    public function setRenovable(bool $renovable): void
    {
        $this->renovable = $renovable;
    }

    public function getDetalle(): ?string
    {
        return $this->detalle;
    }

    public function setDetalle(?string $detalle): void
    {
        if ($detalle !== null) {
            $this->assertMaxLength($detalle, 100, 'detalle');
        }
        $this->detalle = $detalle;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'renovable' => $this->renovable,
            'detalle' => $this->detalle,
        ];
    }
}
