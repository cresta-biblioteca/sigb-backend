<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "PatchLibroRequest")]
class PatchLibroRequest
{
    public function __construct(
        #[OA\Property(type: "string", nullable: true, example: "9780321573513")]
        public readonly ?string $isbn = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $issn = null,
        #[OA\Property(type: "integer", nullable: true, example: 955)]
        public readonly ?int $paginas = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $tituloInformativo = null,
        #[OA\Property(type: "integer", nullable: true)]
        public readonly ?int $cdu = null,
        #[OA\Property(type: "string", nullable: true, example: "Addison-Wesley")]
        public readonly ?string $editorial = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $lugarDePublicacion = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $edicion = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $dimensiones = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $ilustraciones = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $serie = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $numeroSerie = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $notas = null,
        #[OA\Property(type: "string", nullable: true)]
        public readonly ?string $paisPublicacion = null,
        /** @var array<int, array{nombre: string, apellido: string, rol: string}>|null */
        #[OA\Property(
            type: "array",
            nullable: true,
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "nombre", type: "string"),
                    new OA\Property(property: "apellido", type: "string"),
                    new OA\Property(property: "rol", type: "string", example: "autor"),
                    new OA\Property(property: "orden", type: "integer", example: 0),
                ]
            )
        )]
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
