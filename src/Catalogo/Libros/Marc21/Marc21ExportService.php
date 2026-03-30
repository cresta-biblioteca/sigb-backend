<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Marc21;

use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Repositories\LibroRepository;

readonly class Marc21ExportService
{
    private const CHUNK_SIZE = 200;

    public function __construct(
        private LibroRepository $repository
    ) {
    }

    /**
     * Exporta un único libro como MARCXML.
     */
    public function exportSingleXml(int $id): string
    {
        $libro = $this->repository->findById($id);

        if ($libro === null) {
            throw new LibroNotFoundException();
        }

        return Marc21Builder::toMarcXml($libro);
    }

    /**
     * Exporta un único libro en formato ISO 2709 (.mrc).
     */
    public function exportSingleIso2709(int $id): string
    {
        $libro = $this->repository->findById($id);

        if ($libro === null) {
            throw new LibroNotFoundException();
        }

        return Marc21Builder::toIso2709($libro);
    }

    /**
     * Exporta todos los libros que coincidan con los filtros como MARCXML.
     * Procesa los registros en chunks para evitar problemas de memoria.
     *
     * @param array<string, mixed> $filters
     */
    public function exportBulkXml(array $filters): void
    {
        $previous = error_reporting(error_reporting() & ~E_DEPRECATED);

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<collection xmlns=\"http://www.loc.gov/MARC21/slim\">\n";

        $page = 1;
        do {
            $libros = $this->repository->searchPaginated($filters, $page, self::CHUNK_SIZE);

            foreach ($libros as $libro) {
                $record = Marc21Builder::build($libro);
                // toXML devuelve un documento completo; extraemos solo el elemento <record>
                $xml = new \SimpleXMLElement($record->toXML());
                echo '  ' . $xml->record->asXML() . "\n";
            }

            $page++;
        } while (count($libros) === self::CHUNK_SIZE);

        echo "</collection>\n";

        error_reporting($previous);
    }

    /**
     * Exporta todos los libros que coincidan con los filtros en formato ISO 2709.
     * Procesa los registros en chunks para evitar problemas de memoria.
     *
     * @param array<string, mixed> $filters
     */
    public function exportBulkIso2709(array $filters): void
    {
        $previous = error_reporting(error_reporting() & ~E_DEPRECATED);

        $page = 1;
        do {
            $libros = $this->repository->searchPaginated($filters, $page, self::CHUNK_SIZE);

            foreach ($libros as $libro) {
                echo Marc21Builder::toIso2709($libro);
            }

            $page++;
        } while (count($libros) === self::CHUNK_SIZE);

        error_reporting($previous);
    }
}
