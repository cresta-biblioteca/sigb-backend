<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Marc21;

use App\Catalogo\Libros\Validators\LibroRequestValidator;
use App\Shared\Http\ExceptionHandler;
use Throwable;

readonly class Marc21ExportController
{
    public function __construct(
        private Marc21ExportService $exportService
    ) {
    }

    /**
     * GET /libros/{id}/marc21
     * Query param: format=xml (default) | format=iso
     */
    public function exportSingle(int $id): void
    {
        try {
            LibroRequestValidator::validateId($id);

            $format = $_GET['format'] ?? 'xml';

            if ($format === 'iso') {
                header('Content-Type: application/marc');
                header("Content-Disposition: attachment; filename=\"libro-{$id}.mrc\"");
                echo $this->exportService->exportSingleIso2709($id);
            } else {
                header('Content-Type: application/xml; charset=UTF-8');
                header("Content-Disposition: attachment; filename=\"libro-{$id}.xml\"");
                echo $this->exportService->exportSingleXml($id);
            }
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'Marc21ExportController::exportSingle');
        }
    }

    /**
     * GET /libros/marc21
     * Query params: format=xml (default) | format=iso + filtros de búsqueda estándar
     */
    public function exportBulk(): void
    {
        try {
            $format = $_GET['format'] ?? 'xml';
            $filters = array_filter(
                $_GET,
                fn($key) => !in_array($key, ['format'], true),
                ARRAY_FILTER_USE_KEY
            );

            LibroRequestValidator::validateSearchParams($filters);

            if ($format === 'iso') {
                header('Content-Type: application/marc');
                header('Content-Disposition: attachment; filename="libros.mrc"');
                $this->exportService->exportBulkIso2709($filters);
            } else {
                header('Content-Type: application/xml; charset=UTF-8');
                header('Content-Disposition: attachment; filename="libros.xml"');
                $this->exportService->exportBulkXml($filters);
            }
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'Marc21ExportController::exportBulk');
        }
    }
}
