<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Models;

use App\Shared\Entity;

class Articulo extends Entity
{
    private const IDIOMAS_VALIDOS = ['es', 'en', 'pt', 'fr', 'de', 'it'];
    private const MIN_ANIO = 1000;

    private string $titulo;
    private int $anioPublicacion;
    private int $tipoDocumentoId;
    private string $idioma;
    private ?string $descripcion = null;

    private ?TipoDocumento $tipoDocumento = null;

    /** @var Tema[] */
    private array $temas = [];

    /** @var Materia[] */
    private array $materias = [];

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Articulo (valida datos)
     */
    public static function create(
        string $titulo,
        int $anioPublicacion,
        int $tipoDocumentoId,
        string $idioma = 'es',
        ?string $descripcion = null
    ): self {
        $articulo = new self();
        $articulo->setTitulo($titulo);
        $articulo->setAnioPublicacion($anioPublicacion);
        $articulo->setTipoDocumentoId($tipoDocumentoId);
        $articulo->setIdioma($idioma);
        $articulo->setDescripcion($descripcion);

        return $articulo;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $articulo = new self();
        $articulo->id = (int) $row['id'];
        $articulo->titulo = $row['titulo'];
        $articulo->anioPublicacion = (int) $row['anio_publicacion'];
        $articulo->tipoDocumentoId = (int) $row['tipo_documento_id'];
        $articulo->idioma = $row['idioma'];
        $articulo->descripcion = $row['descripcion'] ?? null;
        $articulo->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        return $articulo;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): void
    {
        $this->assertNotEmpty($titulo, 'titulo');
        $this->assertMaxLength($titulo, 100, 'titulo');
        $this->titulo = $titulo;
    }

    public function getAnioPublicacion(): int
    {
        return $this->anioPublicacion;
    }

    public function setAnioPublicacion(int $anioPublicacion): void
    {
        $currentYear = (int) date('Y');
        if ($anioPublicacion < self::MIN_ANIO || $anioPublicacion > $currentYear) {
            $this->assertInArray(
                $anioPublicacion,
                range(self::MIN_ANIO, $currentYear),
                'anio_publicacion'
            );
        }
        $this->anioPublicacion = $anioPublicacion;
    }

    public function getTipoDocumentoId(): int
    {
        return $this->tipoDocumentoId;
    }

    public function setTipoDocumentoId(int $tipoDocumentoId): void
    {
        $this->assertPositive($tipoDocumentoId, 'tipo_documento_id');
        $this->tipoDocumentoId = $tipoDocumentoId;
    }

    public function getIdioma(): string
    {
        return $this->idioma;
    }

    public function setIdioma(string $idioma): void
    {
        $this->assertNotEmpty($idioma, 'idioma');
        $this->assertExactLength($idioma, 2, 'idioma');
        $this->assertInArray($idioma, self::IDIOMAS_VALIDOS, 'idioma');
        $this->idioma = strtolower($idioma);
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        if ($descripcion !== null) {
            $this->assertMaxLength($descripcion, 255, 'descripcion');
        }
        $this->descripcion = $descripcion;
    }

    public function getTipoDocumento(): ?TipoDocumento
    {
        return $this->tipoDocumento;
    }

    public function setTipoDocumento(TipoDocumento $tipoDocumento): void
    {
        $this->tipoDocumento = $tipoDocumento;
        $this->tipoDocumentoId = $tipoDocumento->getId();
    }

    /**
     * @return Tema[]
     */
    public function getTemas(): array
    {
        return $this->temas;
    }

    /**
     * @param Tema[] $temas
     */
    public function setTemas(array $temas): void
    {
        $this->temas = $temas;
    }

    public function addTema(Tema $tema): void
    {
        $this->temas[] = $tema;
    }

    /**
     * @return Materia[]
     */
    public function getMaterias(): array
    {
        return $this->materias;
    }

    /**
     * @param Materia[] $materias
     */
    public function setMaterias(array $materias): void
    {
        $this->materias = $materias;
    }

    public function addMateria(Materia $materia): void
    {
        $this->materias[] = $materia;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'anio_publicacion' => $this->anioPublicacion,
            'tipo_documento_id' => $this->tipoDocumentoId,
            'idioma' => $this->idioma,
            'descripcion' => $this->descripcion,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->tipoDocumento !== null) {
            $data['tipo_documento'] = $this->tipoDocumento->toArray();
        }

        if (!empty($this->temas)) {
            $data['temas'] = array_map(fn(Tema $t) => $t->toArray(), $this->temas);
        }

        if (!empty($this->materias)) {
            $data['materias'] = array_map(fn(Materia $m) => $m->toArray(), $this->materias);
        }

        return $data;
    }
}
