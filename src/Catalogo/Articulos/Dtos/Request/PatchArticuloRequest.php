<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

readonly class PatchArticuloRequest
{
    private function __construct(
        public ?string $titulo,
        public ?int $anioPublicacion,
        public ?string $idioma,
        public ?string $descripcion,
        public array $provided,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            titulo: isset($data['titulo']) ? trim((string)$data['titulo']) : null,
            anioPublicacion: array_key_exists('anio_publicacion', $data) ? (int)$data['anio_publicacion'] : null,
            idioma: isset($data['idioma']) ? strtolower((string)$data['idioma']) : null,
            descripcion: array_key_exists('descripcion', $data)
                ? ($data['descripcion'] !== null ? trim((string)$data['descripcion']) : null)
                : null,
            provided: array_keys($data),
        );
    }

    public function isProvided(string $field): bool
    {
        return in_array($field, $this->provided, true);
    }
}
