<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Models;

use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Entity;

class Libro extends Entity
{
    private const ISBN_PATTERN = '/^\d{10}(\d{3})?$/';

    private int $articuloId;
    private string $isbn;
    private ?string $autor;
    private ?string $autores;
    private ?string $colaboradores;
    private ?string $tituloInformativo;
    private ?int $cdu;
    private string $exportMarc;

    private ?Articulo $articulo = null;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Libro (valida datos)
     */
    public static function create(
        int $articuloId,
        string $isbn,
        string $exportMarc,
        ?string $autor = null,
        ?string $autores = null,
        ?string $colaboradores = null,
        ?string $tituloInformativo = null,
        ?int $cdu = null
    ): self {
        $libro = new self();
        $libro->setArticuloId($articuloId);
        $libro->setIsbn($isbn);
        $libro->setExportMarc($exportMarc);
        $libro->setAutor($autor);
        $libro->setAutores($autores);
        $libro->setColaboradores($colaboradores);
        $libro->setTituloInformativo($tituloInformativo);
        $libro->setCdu($cdu);

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
        $libro->articuloId = (int) $row['articulo_id'];
        $libro->id = $libro->articuloId;
        $libro->isbn = $row['isbn'];
        $libro->autor = $row['autor'];
        $libro->autores = $row['autores'];
        $libro->colaboradores = $row['colaboradores'];
        $libro->tituloInformativo = $row['titulo_informativo'];
        $libro->cdu = $row['cdu'] !== null ? (int) $row['cdu'] : null;
        $libro->exportMarc = $row['export_marc'];
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

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): void
    {
        $isbnClean = str_replace(['-', ' '], '', $isbn);
        $this->assertNotEmpty($isbnClean, 'isbn');
        $this->assertMatchesPattern(
            $isbnClean,
            self::ISBN_PATTERN,
            'isbn',
            'El ISBN debe tener 10 o 13 digitos'
        );
        $this->isbn = $isbnClean;
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
            'autor' => $this->autor,
            'autores' => $this->autores,
            'colaboradores' => $this->colaboradores,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'export_marc' => $this->exportMarc,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->articulo !== null) {
            $data['articulo'] = $this->articulo->toArray();
        }

        return $data;
    }
}
