<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Marc21;

use App\Catalogo\Libros\Validators\LibroRequestValidator;
use OpenApi\Attributes as OA;

readonly class Marc21ExportController
{
    public function __construct(
        private Marc21ExportService $exportService
    ) {
    }

    #[OA\Get(
        path: "/libros/{id}/marc21",
        description: "Exporta un libro en formato MARC21. Por defecto retorna XML; con `format=iso` retorna ISO 2709.",
        summary: "Exportar libro en MARC21",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del libro",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            ),
            new OA\Parameter(
                name: "format",
                in: "query",
                description: "Formato de exportación",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["xml", "iso"], default: "xml")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Archivo MARC21 descargado",
                content: [
                    new OA\MediaType(mediaType: "application/xml"),
                    new OA\MediaType(mediaType: "application/marc"),
                ]
            ),
            new OA\Response(response: 400, description: "ID inválido"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Libro no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function exportSingle(int $id): void
    {
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
    }

    #[OA\Get(
        path: "/libros/marc21",
        description: "Exporta todos los libros (o los que coincidan con los filtros) en formato MARC21."
            . " Por defecto XML; con `format=iso` retorna ISO 2709."
            . " Acepta los mismos filtros que el listado de libros.",
        summary: "Exportar libros en MARC21 (bulk)",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        parameters: [
            new OA\Parameter(
                name: "format",
                in: "query",
                description: "Formato de exportación",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["xml", "iso"], default: "xml")
            ),
            new OA\Parameter(
                name: "titulo",
                in: "query",
                description: "Filtrar por título",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "isbn",
                in: "query",
                description: "Filtrar por ISBN",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "editorial",
                in: "query",
                description: "Filtrar por editorial",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Archivo MARC21 descargado",
                content: [
                    new OA\MediaType(mediaType: "application/xml"),
                    new OA\MediaType(mediaType: "application/marc"),
                ]
            ),
            new OA\Response(response: 400, description: "Parámetros inválidos"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function exportBulk(): void
    {
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
    }
}
