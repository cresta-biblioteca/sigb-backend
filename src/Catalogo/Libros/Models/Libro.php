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
    private ?string $autor;
    private ?string $autores;
    private ?string $colaboradores;
    private ?string $tituloInformativo;
    private ?int $cdu;
    private string $exportMarc;
    private ?string $editorial = null;
    private ?string $lugarDePublicacion = null;

    private ?Articulo $articulo = null;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Libro (valida datos)
     */
    public static function create(
        int $articuloId,
        ?string $isbn = null,
        ?string $issn = null,
        ?int $paginas = null,
        ?string $autor = null,
        ?string $autores = null,
        ?string $colaboradores = null,
        ?string $tituloInformativo = null,
        ?int $cdu = null,
        ?string $editorial = null,
        ?string $lugarDePublicacion = null
    ): self {
        $libro = new self();
        $libro->setArticuloId($articuloId);
        $libro->setIsbn($isbn);
        $libro->setIssn($issn);
        $libro->setPaginas($paginas);
        $libro->setAutor($autor);
        $libro->setAutores($autores);
        $libro->setColaboradores($colaboradores);
        $libro->setTituloInformativo($tituloInformativo);
        $libro->setCdu($cdu);
        $libro->setEditorial($editorial);
        $libro->setLugarDePublicacion($lugarDePublicacion);

        return $libro;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
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
        $libro->autor = $row['autor'];
        $libro->autores = $row['autores'];
        $libro->colaboradores = $row['colaboradores'];
        $libro->tituloInformativo = $row['titulo_informativo'];
        $libro->cdu = $row['cdu'] !== null ? (int)$row['cdu'] : null;
        $libro->exportMarc = $row['export_marc'] ?? '';
        $libro->editorial = $row['editorial'] ?? null;
        $libro->lugarDePublicacion = $row['lugar_de_publicacion'] ?? null;
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

    public function getAutor(): ?string
    {
        return $this->autor;
    }

    public function setAutor(?string $autor): void
    {
        if ($autor !== null) {
            $this->assertMaxLength($autor, 255, 'autor');
        }
        $this->autor = $autor;
    }

    public function getAutores(): ?string
    {
        return $this->autores;
    }

    public function setAutores(?string $autores): void
    {
        if ($autores !== null) {
            $this->assertMaxLength($autores, 255, 'autores');
        }
        $this->autores = $autores;
    }

    public function getColaboradores(): ?string
    {
        return $this->colaboradores;
    }

    public function setColaboradores(?string $colaboradores): void
    {
        if ($colaboradores !== null) {
            $this->assertMaxLength($colaboradores, 255, 'colaboradores');
        }
        $this->colaboradores = $colaboradores;
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

    public function getExportMarc(): string
    {
        return $this->exportMarc;
    }

    public function setExportMarc(string $exportMarc): void
    {
        $this->assertNotEmpty($exportMarc, 'export_marc');
        $this->exportMarc = $exportMarc;
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'articulo_id' => $this->articuloId,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'paginas' => $this->paginas,
            'autor' => $this->autor,
            'autores' => $this->autores,
            'colaboradores' => $this->colaboradores,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'export_marc' => $this->exportMarc,
            'editorial' => $this->editorial,
            'lugar_de_publicacion' => $this->lugarDePublicacion,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->articulo !== null) {
            $data['articulo'] = $this->articulo->toArray();
        }

        return $data;
    }
}
