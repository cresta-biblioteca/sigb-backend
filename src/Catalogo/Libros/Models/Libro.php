<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Models;

use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Entity;

class Libro extends Entity
{
    private const ISBN_PATTERN = '/^\d{10}(\d{3})?$/';
    private const ISSN_PATTERN = '/^\d{4}-?\d{3}[\dXx]$/';

    private int $articuloId;
    private ?string $isbn = null;
    private ?string $issn = null;
    private ?int $paginas = null;
    private ?string $tituloInformativo;
    private ?int $cdu;
    private ?string $editorial = null;
    private ?string $lugarDePublicacion = null;
    private ?string $edicion = null;
    private ?string $dimensiones = null;
    private ?string $ilustraciones = null;
    private ?string $serie = null;
    private ?string $numeroSerie = null;
    private ?string $notas = null;
    private ?string $paisPublicacion = null;

    private ?Articulo $articulo = null;

    /** @var LibroPersona[] */
    private array $personas = [];

    private function __construct()
    {
    }

    // La entidad ya nace con un ID otorgado por su padre -> Articulo
    public static function create(
        int $articuloId,
        ?string $isbn = null,
        ?string $issn = null,
        ?int $paginas = null,
        ?string $tituloInformativo = null,
        ?int $cdu = null,
        ?string $editorial = null,
        ?string $lugarDePublicacion = null,
        ?string $edicion = null,
        ?string $dimensiones = null,
        ?string $ilustraciones = null,
        ?string $serie = null,
        ?string $numeroSerie = null,
        ?string $notas = null,
        ?string $paisPublicacion = null
    ): self {
        $libro = new self();
        $libro->setArticuloId($articuloId);
        $libro->setIsbn($isbn);
        $libro->setIssn($issn);
        $libro->setPaginas($paginas);
        $libro->setTituloInformativo($tituloInformativo);
        $libro->setCdu($cdu);
        $libro->setEditorial($editorial);
        $libro->setLugarDePublicacion($lugarDePublicacion);
        $libro->setEdicion($edicion);
        $libro->setDimensiones($dimensiones);
        $libro->setIlustraciones($ilustraciones);
        $libro->setSerie($serie);
        $libro->setNumeroSerie($numeroSerie);
        $libro->setNotas($notas);
        $libro->setPaisPublicacion($paisPublicacion);

        return $libro;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $libro = new self();
        $libro->articuloId = (int)$row['articulo_id'];
        $libro->id = $libro->articuloId;
        $libro->isbn = $row['isbn'] ?? null;
        $libro->issn = $row['issn'] ?? null;
        $libro->paginas = isset($row['paginas']) ? (int)$row['paginas'] : null;
        $libro->tituloInformativo = $row['titulo_informativo'] ?? null;
        $libro->cdu = isset($row['cdu']) ? (int)$row['cdu'] : null;
        $libro->editorial = $row['editorial'] ?? null;
        $libro->lugarDePublicacion = $row['lugar_de_publicacion'] ?? null;
        $libro->edicion = $row['edicion'] ?? null;
        $libro->dimensiones = $row['dimensiones'] ?? null;
        $libro->ilustraciones = $row['ilustraciones'] ?? null;
        $libro->serie = $row['serie'] ?? null;
        $libro->numeroSerie = $row['numero_serie'] ?? null;
        $libro->notas = $row['notas'] ?? null;
        $libro->paisPublicacion = $row['pais_publicacion'] ?? null;
        $libro->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        return $libro;
    }

    public function getArticuloId(): int
    {
        return $this->articuloId;
    }

    public function setArticuloId(int $articuloId): void
    {
        $this->assertPositive($articuloId, 'articulo_id');
        $this->articuloId = $articuloId;
        $this->id = $articuloId;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): void
    {
        if ($isbn !== null) {
            $isbnClean = str_replace(['-', ' '], '', $isbn);
            $this->assertNotEmpty($isbnClean, 'isbn');
            $this->assertMatchesPattern(
                $isbnClean,
                self::ISBN_PATTERN,
                'isbn',
                'El ISBN debe tener 10 o 13 digitos'
            );
            $this->isbn = $isbnClean;
        } else {
            $this->isbn = null;
        }
    }

    public function getIssn(): ?string
    {
        return $this->issn;
    }

    public function setIssn(?string $issn): void
    {
        if ($issn !== null) {
            $issnClean = str_replace([' '], '', $issn);
            $this->assertNotEmpty($issnClean, 'issn');
            $this->assertMatchesPattern(
                $issnClean,
                self::ISSN_PATTERN,
                'issn',
                'El ISSN debe tener el formato XXXX-XXXX (8 caracteres)'
            );
            $this->issn = $issnClean;
        } else {
            $this->issn = null;
        }
    }

    public function getPaginas(): ?int
    {
        return $this->paginas;
    }

    public function setPaginas(?int $paginas): void
    {
        if ($paginas !== null) {
            $this->assertPositive($paginas, 'paginas');
        }
        $this->paginas = $paginas;
    }

    public function getTituloInformativo(): ?string
    {
        return $this->tituloInformativo;
    }

    public function setTituloInformativo(?string $tituloInformativo): void
    {
        if ($tituloInformativo !== null) {
            $this->assertMaxLength($tituloInformativo, 255, 'titulo_informativo');
        }
        $this->tituloInformativo = $tituloInformativo;
    }

    public function getCdu(): ?int
    {
        return $this->cdu;
    }

    public function setCdu(?int $cdu): void
    {
        if ($cdu !== null) {
            $this->assertNonNegative($cdu, 'cdu');
        }
        $this->cdu = $cdu;
    }

    public function getEditorial(): ?string
    {
        return $this->editorial;
    }

    public function setEditorial(?string $editorial): void
    {
        if ($editorial !== null) {
            $this->assertMaxLength($editorial, 200, 'editorial');
        }
        $this->editorial = $editorial;
    }

    public function getLugarDePublicacion(): ?string
    {
        return $this->lugarDePublicacion;
    }

    public function setLugarDePublicacion(?string $lugarDePublicacion): void
    {
        if ($lugarDePublicacion !== null) {
            $this->assertMaxLength($lugarDePublicacion, 200, 'lugar_de_publicacion');
        }
        $this->lugarDePublicacion = $lugarDePublicacion;
    }

    public function getEdicion(): ?string
    {
        return $this->edicion;
    }

    public function setEdicion(?string $edicion): void
    {
        if ($edicion !== null) {
            $this->assertMaxLength($edicion, 100, 'edicion');
        }
        $this->edicion = $edicion;
    }

    public function getDimensiones(): ?string
    {
        return $this->dimensiones;
    }

    public function setDimensiones(?string $dimensiones): void
    {
        if ($dimensiones !== null) {
            $this->assertMaxLength($dimensiones, 50, 'dimensiones');
        }
        $this->dimensiones = $dimensiones;
    }

    public function getIlustraciones(): ?string
    {
        return $this->ilustraciones;
    }

    public function setIlustraciones(?string $ilustraciones): void
    {
        if ($ilustraciones !== null) {
            $this->assertMaxLength($ilustraciones, 100, 'ilustraciones');
        }
        $this->ilustraciones = $ilustraciones;
    }

    public function getSerie(): ?string
    {
        return $this->serie;
    }

    public function setSerie(?string $serie): void
    {
        if ($serie !== null) {
            $this->assertMaxLength($serie, 255, 'serie');
        }
        $this->serie = $serie;
    }

    public function getNumeroSerie(): ?string
    {
        return $this->numeroSerie;
    }

    public function setNumeroSerie(?string $numeroSerie): void
    {
        if ($numeroSerie !== null) {
            $this->assertMaxLength($numeroSerie, 50, 'numero_serie');
        }
        $this->numeroSerie = $numeroSerie;
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): void
    {
        $this->notas = $notas;
    }

    public function getPaisPublicacion(): ?string
    {
        return $this->paisPublicacion;
    }

    public function setPaisPublicacion(?string $paisPublicacion): void
    {
        if ($paisPublicacion !== null) {
            $this->assertExactLength($paisPublicacion, 2, 'pais_publicacion');
        }
        $this->paisPublicacion = $paisPublicacion;
    }

    public function getArticulo(): ?Articulo
    {
        return $this->articulo;
    }

    public function setArticulo(Articulo $articulo): void
    {
        $this->articulo = $articulo;
        $this->articuloId = $articulo->getId();
        $this->id = $articulo->getId();
    }

    /**
     * @return LibroPersona[]
     */
    public function getPersonas(): array
    {
        return $this->personas;
    }

    /**
     * @param LibroPersona[] $personas
     */
    public function setPersonas(array $personas): void
    {
        $this->personas = $personas;
    }

    public function getAutorPrincipal(): ?Persona
    {
        foreach ($this->personas as $libroPersona) {
            if ($libroPersona->rol === 'autor' && $libroPersona->orden === 0) {
                return $libroPersona->persona;
            }
        }

        // Fallback: primer autor encontrado
        foreach ($this->personas as $libroPersona) {
            if ($libroPersona->rol === 'autor') {
                return $libroPersona->persona;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'articulo_id' => $this->articuloId,
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
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->articulo !== null) {
            $data['articulo'] = $this->articulo->toArray();
        }

        return $data;
    }
}
