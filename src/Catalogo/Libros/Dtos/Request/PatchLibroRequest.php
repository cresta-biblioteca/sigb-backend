<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

class PatchLibroRequest
{
    public function __construct(
        public readonly ?string $isbn = null,
        public readonly ?string $issn = null,
        public readonly ?int $paginas = null,
        public readonly ?string $tituloInformativo = null,
        public readonly ?int $cdu = null,
        public readonly ?string $editorial = null,
        public readonly ?string $lugarDePublicacion = null,
        public readonly ?string $edicion = null,
        public readonly ?string $dimensiones = null,
        public readonly ?string $ilustraciones = null,
        public readonly ?string $serie = null,
        public readonly ?string $numeroSerie = null,
        public readonly ?string $notas = null,
        public readonly ?string $paisPublicacion = null,
        /** @var array<int, array{nombre: string, apellido: string, rol: string}>|null */
        public readonly ?array $personas = null,
        public array $provided = []
    ) {
        $this->provided = $provided;
    }

    public static function fromRequest(mixed $data): self
    {
        return new self(
            isbn: $data['isbn'] ?? null,
            issn: $data['issn'] ?? null,
            paginas: array_key_exists('paginas', $data) ? (int)$data['paginas'] : null,
            tituloInformativo: $data['titulo_informativo'] ?? null,
            cdu: array_key_exists('cdu', $data) ? (int)$data['cdu'] : null,
            editorial: $data['editorial'] ?? null,
            lugarDePublicacion: $data['lugar_de_publicacion'] ?? null,
            edicion: $data['edicion'] ?? null,
            dimensiones: $data['dimensiones'] ?? null,
            ilustraciones: $data['ilustraciones'] ?? null,
            serie: $data['serie'] ?? null,
            numeroSerie: $data['numero_serie'] ?? null,
            notas: $data['notas'] ?? null,
            paisPublicacion: $data['pais_publicacion'] ?? null,
            personas: $data['personas'] ?? null,
            provided: array_keys($data),
        );
    }

    public function isProvided(string $field): bool
    {
        return in_array($field, $this->provided);
    }
}
