<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "LibroResponse")]
readonly class LibroResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        private int $id,
        #[OA\Property(type: "string", nullable: true, example: "9780321573513")]
        private ?string $isbn,
        #[OA\Property(type: "string", nullable: true, example: null)]
        private ?string $issn,
        #[OA\Property(type: "integer", nullable: true, example: 955)]
        private ?int $paginas,
        #[OA\Property(description: "Título informativo complementario", type: "string", nullable: true)]
        private ?string $tituloInformativo,
        #[OA\Property(description: "Clasificación Decimal Universal", type: "integer", nullable: true, example: 519)]
        private ?int $cdu,
        #[OA\Property(type: "string", nullable: true, example: "Addison-Wesley")]
        private ?string $editorial,
        #[OA\Property(type: "string", nullable: true, example: "Boston")]
        private ?string $lugarDePublicacion,
        #[OA\Property(type: "string", nullable: true, example: "4ta edición")]
        private ?string $edicion,
        #[OA\Property(type: "string", nullable: true)]
        private ?string $dimensiones,
        #[OA\Property(type: "string", nullable: true)]
        private ?string $ilustraciones,
        #[OA\Property(type: "string", nullable: true)]
        private ?string $serie,
        #[OA\Property(type: "string", nullable: true)]
        private ?string $numeroSerie,
        #[OA\Property(type: "string", nullable: true)]
        private ?string $notas,
        #[OA\Property(type: "string", nullable: true, example: "Estados Unidos")]
        private ?string $paisPublicacion,
        /** @var array<int, array{nombre: string, apellido: string, rol: string, orden: int}> */
        #[OA\Property(
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "nombre", type: "string"),
                    new OA\Property(property: "apellido", type: "string"),
                    new OA\Property(property: "rol", type: "string"),
                    new OA\Property(property: "orden", type: "integer"),
                ]
            )
        )]
        private array $personas = [],
        // Información del artículo asociado
        #[OA\Property(type: "string", nullable: true, example: "Algorithms")]
        private ?string $titulo = null,
        #[OA\Property(type: "integer", nullable: true, example: 2011)]
        private ?int $anioPublicacion = null,
        #[OA\Property(description: "Tipo de artículo según MARC21", type: "string", nullable: true, example: "libro")]
        private ?string $tipo = null,
        #[OA\Property(type: "string", nullable: true, example: "en")]
        private ?string $idioma = null,
        #[OA\Property(type: "string", nullable: true)]
        private ?string $descripcion = null,
        /** @var array<int, array{id: int, titulo: string}> */
        #[OA\Property(type: "array", items: new OA\Items(type: "object"))]
        private array $temas = [],
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'paginas' => $this->paginas,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'editorial' => $this->editorial,
            'lugar_de_publicacion' => $this->lugarDePublicacion,
            'edicion' => $this->edicion,
            'dimensiones' => $this->dimensiones,
            'ilustraciones' => $this->ilustraciones,
            'serie' => $this->serie,
            'numero_serie' => $this->numeroSerie,
            'notas' => $this->notas,
            'pais_publicacion' => $this->paisPublicacion,
            'personas' => $this->personas,
        ];

        // Agregar información del artículo si está disponible
        if ($this->titulo !== null) {
            $data['articulo'] = [
                'titulo' => $this->titulo,
                'anio_publicacion' => $this->anioPublicacion,
                'tipo' => $this->tipo,
                'idioma' => $this->idioma,
                'descripcion' => $this->descripcion,
                'temas' => $this->temas,
            ];
        }

        return $data;
    }
}
