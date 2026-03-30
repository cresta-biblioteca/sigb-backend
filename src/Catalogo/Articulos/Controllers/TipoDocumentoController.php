<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Mappers\TipoDocumentoMapper;
use App\Catalogo\Articulos\Services\TipoDocumentoService;
use App\Catalogo\Articulos\Validators\TipoDocumentoRequestValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class TipoDocumentoController
{
    private const ALLOWED_PARAMS = ["codigo", "descripcion", "detalle", "renovable", "order"];
    public function __construct(private TipoDocumentoService $service)
    {
    }

    #[OA\Get(
        path: "/documentos",
        description: "Listado de todos los tipos de documento registrados",
        summary: "Lista de tipos de documento",
        tags: ["Tipos de Documento"],
        parameters: [
            new OA\Parameter(
                name: "codigo",
                in: "query",
                description: "Busqueda por codigo",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "descripcion",
                in: "query",
                description: "Busqueda por descripcion",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "detalle",
                in: "query",
                description: "Busqueda por detalle",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "renovable",
                in: "query",
                description: "Filtrar por renovable",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                description: "Ordenamiento(ASC/DESC)",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/TipoDocumentoResponse")
                )
            ),
            new OA\Response(
                response: 400,
                description: "Datos invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function getAll(): void
    {
        $params = array_filter(
            array_intersect_key($_GET, array_flip(self::ALLOWED_PARAMS)),
            fn($value) => $value !== ''
        );

        if (!empty($params)) {
            TipoDocumentoRequestValidator::validateParams($params);
            JsonHelper::jsonResponse($this->service->getByParams($params), 200);
            return;
        }

        JsonHelper::jsonResponse($this->service->getAll(), 200);
    }

    #[OA\Get(
        path: "/documentos/{id}",
        description: "Mostrar la informacion de un tipo de documento especifico",
        summary: "Obtener un tipo de documento",
        tags: ["Tipos de Documento"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de documento a buscar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de documento obtenido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TipoDocumentoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de documento no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function getById(string $id): void
    {
        TipoDocumentoRequestValidator::validateId($id);
        JsonHelper::jsonResponse($this->service->getById((int) $id), 200);
    }

    #[OA\Post(
        path: "/documentos",
        description: "Crear un nuevo tipo de documento",
        summary: "Crear tipo de documento",
        tags: ["Tipos de Documento"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateTipoDocumentoRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tipo de documento creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TipoDocumentoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "El tipo de documento ya existe",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validacion de negocio",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "field", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function createTipoDocumento(): void
    {
        $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
        TipoDocumentoRequestValidator::validateInputCreate($input);
        $tipoDocumento = $this->service->createTipoDocumento(TipoDocumentoMapper::fromArrayToCreate($input));
        JsonHelper::jsonResponse($tipoDocumento, 201);
    }

    #[OA\Put(
        path: "/documentos/{id}",
        description: "Actualizar la informacion de un tipo de documento existente",
        summary: "Actualizar tipo de documento",
        tags: ["Tipos de Documento"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de documento a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateTipoDocumentoRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de documento actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TipoDocumentoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de documento no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "El tipo de documento ya existe",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validacion de negocio",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "field", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function updateTipoDocumento(string $id): void
    {
        TipoDocumentoRequestValidator::validateId($id);
        $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
        TipoDocumentoRequestValidator::validateInputUpdate($input);
        $tipoDocumento = $this->service->updateTipoDocumento(
            (int) $id,
            TipoDocumentoMapper::fromArrayToUpdate($input)
        );
        JsonHelper::jsonResponse($tipoDocumento, 200);
    }

    #[OA\Delete(
        path: "/documentos/{id}",
        description: "Eliminar un tipo de documento existente",
        summary: "Eliminar tipo de documento",
        tags: ["Tipos de Documento"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de documento a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Tipo de documento eliminado exitosamente"
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de documento no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function deleteTipoDocumento(string $id): void
    {
        TipoDocumentoRequestValidator::validateId($id);
        $this->service->deleteTipoDocumento((int) $id);
        http_response_code(204);
    }
}
