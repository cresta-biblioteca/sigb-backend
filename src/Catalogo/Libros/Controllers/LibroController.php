<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Catalogo\Libros\Dtos\Request\CreateLibroRequest;
use App\Catalogo\Libros\Dtos\Request\PatchLibroRequest;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Libros\Validators\LibroRequestValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

readonly class LibroController
{
    public function __construct(
        private LibroService $libroService
    ) {
    }

    #[OA\Get(
        path: "/libros/{id}",
        description: "Obtener la información completa de un libro por su ID",
        summary: "Obtener libro por ID",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del libro",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Libro obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/LibroResponse")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "ID inválido"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Libro no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getById($id): void
    {
        LibroRequestValidator::validateId((int)$id);
        $libro = $this->libroService->getById((int)$id);
        JsonHelper::jsonResponse(['data' => $libro]);
    }

    #[OA\Post(
        path: "/libros",
        description: "Crea un libro completo con su artículo y personas en una sola transacción",
        summary: "Crear libro",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["articulo", "libro"],
                properties: [
                    new OA\Property(
                        property: "articulo",
                        required: ["titulo", "anio_publicacion", "tipo_documento_id"],
                        properties: [
                            new OA\Property(property: "titulo", type: "string", example: "Algorithms"),
                            new OA\Property(property: "anio_publicacion", type: "integer", example: 2011),
                            new OA\Property(property: "tipo_documento_id", type: "integer", example: 1),
                            new OA\Property(property: "idioma", type: "string", example: "en"),
                            new OA\Property(property: "descripcion", type: "string", nullable: true),
                        ],
                        type: "object"
                    ),
                    new OA\Property(
                        property: "libro",
                        properties: [
                            new OA\Property(property: "isbn", type: "string", nullable: true, example: "9780321573513"),
                            new OA\Property(property: "issn", type: "string", nullable: true),
                            new OA\Property(property: "paginas", type: "integer", nullable: true, example: 955),
                            new OA\Property(property: "editorial", type: "string", nullable: true, example: "Addison-Wesley"),
                            new OA\Property(property: "lugar_de_publicacion", type: "string", nullable: true),
                            new OA\Property(property: "edicion", type: "string", nullable: true),
                            new OA\Property(property: "cdu", type: "integer", nullable: true),
                            new OA\Property(property: "titulo_informativo", type: "string", nullable: true),
                            new OA\Property(property: "dimensiones", type: "string", nullable: true),
                            new OA\Property(property: "ilustraciones", type: "string", nullable: true),
                            new OA\Property(property: "serie", type: "string", nullable: true),
                            new OA\Property(property: "numero_serie", type: "string", nullable: true),
                            new OA\Property(property: "notas", type: "string", nullable: true),
                            new OA\Property(property: "pais_publicacion", type: "string", nullable: true),
                            new OA\Property(
                                property: "personas",
                                type: "array",
                                items: new OA\Items(
                                    required: ["nombre", "apellido", "rol", "orden"],
                                    properties: [
                                        new OA\Property(property: "nombre", type: "string", example: "Robert"),
                                        new OA\Property(property: "apellido", type: "string", example: "Sedgewick"),
                                        new OA\Property(property: "rol", type: "string", example: "autor"),
                                        new OA\Property(property: "orden", type: "integer", example: 0),
                                    ]
                                )
                            ),
                        ],
                        type: "object"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Libro creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/LibroResponse"),
                        new OA\Property(property: "message", type: "string"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function create(): void
    {
        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $articuloData = $input['articulo'] ?? [];
        $libroData = $input['libro'] ?? [];

        ArticuloRequestValidator::validate($articuloData);
        LibroRequestValidator::validate($libroData);

        $request = CreateLibroRequest::fromArray($articuloData, $libroData);

        $libro = $this->libroService->create($request);

        JsonHelper::jsonResponse(['data' => $libro, 'message' => 'Libro creado exitosamente'], 201);
    }

    #[OA\Patch(
        path: "/libros/{id}",
        description: "Actualiza parcialmente los campos del libro. Solo se actualizan los campos enviados.",
        summary: "Actualizar libro",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del libro a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/PatchLibroRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Libro actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/LibroResponse"),
                        new OA\Property(property: "message", type: "string"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Datos de entrada inválidos"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Libro no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function updateLibro($id): void
    {
        LibroRequestValidator::validateId((int)$id);

        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        LibroRequestValidator::validatePatch($data);

        $request = PatchLibroRequest::fromRequest($data);

        $response = $this->libroService->updateLibro((int)$id, $request);

        JsonHelper::jsonResponse(['data' => $response, 'message' => 'Libro actualizado exitosamente']);
    }

    #[OA\Delete(
        path: "/libros/{id}",
        description: "Elimina un libro y su artículo asociado",
        summary: "Eliminar libro",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del libro a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Libro eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string")]
                )
            ),
            new OA\Response(response: 400, description: "ID inválido"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Libro no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function deleteLibro($id): void
    {
        LibroRequestValidator::validateId((int)$id);
        $this->libroService->deleteLibro((int)$id);
        JsonHelper::jsonResponse(['message' => 'Libro eliminado exitosamente']);
    }

    #[OA\Get(
        path: "/libros",
        description: "Listado paginado de libros con filtros y ordenamiento",
        summary: "Buscar libros",
        security: [["bearerAuth" => []]],
        tags: ["Libros"],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Número de página", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", in: "query", description: "Resultados por página", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "sort_by", in: "query", description: "Campo de ordenamiento", required: false, schema: new OA\Schema(type: "string", default: "titulo")),
            new OA\Parameter(name: "sort_dir", in: "query", description: "Dirección del ordenamiento (asc/desc)", required: false, schema: new OA\Schema(type: "string", default: "asc")),
            new OA\Parameter(name: "titulo", in: "query", description: "Filtrar por título", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "isbn", in: "query", description: "Filtrar por ISBN", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "editorial", in: "query", description: "Filtrar por editorial", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "idioma", in: "query", description: "Filtrar por idioma", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/LibroResponse")
                        ),
                        new OA\Property(
                            property: "pagination",
                            properties: [
                                new OA\Property(property: "total", type: "integer"),
                                new OA\Property(property: "per_page", type: "integer"),
                                new OA\Property(property: "current_page", type: "integer"),
                                new OA\Property(property: "last_page", type: "integer"),
                            ],
                            type: "object"
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Parámetros de búsqueda inválidos"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function searchPaginated(): void
    {
        // Aplica valores por defecto ante la ausencia de paginacion y filtros de sorting
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $sortBy = $_GET['sort_by'] ?? 'titulo';
        $sortDir = $_GET['sort_dir'] ?? 'asc';
        $filters = array_filter(
            $_GET,
            fn($key) => !in_array($key, ['page', 'per_page', 'sort_by', 'sort_dir'], true),
            ARRAY_FILTER_USE_KEY
        );

        LibroRequestValidator::validateSearchParams($_GET);

        $result = $this->libroService->searchPaginated($filters, $page, $perPage, $sortBy, $sortDir);

        JsonHelper::jsonResponse([
            'data' => $result['items'],
            'pagination' => $result['pagination'],
        ]);
    }
}
