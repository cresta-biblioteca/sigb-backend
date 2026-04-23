<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Models;

use App\Shared\Entity;
use App\Shared\Enums\TipoArticulo;

class Articulo extends Entity
{
    private const IDIOMAS_VALIDOS = ['es', 'en', 'pt', 'fr', 'de', 'it'];
    private const MIN_ANIO = 1000;

    private string $titulo;
    private int $anioPublicacion;
    private string $tipo;
    private string $idioma;
    private ?string $descripcion = null;

    /** @var Tema[] */
    private array $temas = [];

    private function __construct()
    {
    }

    public static function create(
        string $titulo,
        int $anioPublicacion,
        string $tipo,
        string $idioma = 'es',
        ?string $descripcion = null
    ): self {
        $articulo = new self();
        $articulo->setTitulo($titulo);
        $articulo->setAnioPublicacion($anioPublicacion);
        $articulo->setTipo($tipo);
        $articulo->setIdioma($idioma);
        $articulo->setDescripcion($descripcion);

        return $articulo;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $articulo = new self();
        $articulo->id = (int) $row['id'];
        $articulo->titulo = $row['titulo'];
        $articulo->anioPublicacion = (int) $row['anio_publicacion'];
        $articulo->tipo = $row['tipo'];
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

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void
    {
        $tiposValidos = array_column(TipoArticulo::cases(), 'value');
        $this->assertInArray($tipo, $tiposValidos, 'tipo');
        $this->tipo = $tipo;
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'anio_publicacion' => $this->anioPublicacion,
            'tipo' => $this->tipo,
            'idioma' => $this->idioma,
            'descripcion' => $this->descripcion,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if (!empty($this->temas)) {
            $data['temas'] = array_map(fn(Tema $t) => $t->toArray(), $this->temas);
        }

        return $data;
    }
}
