<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Mappers\TemaMapper;
use App\Catalogo\Articulos\Services\TemaService;
use App\Catalogo\Articulos\Validators\TemaRequestValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class TemaController
{
    private const ALLOWED_PARAMS = ["titulo", "order"];
    public function __construct(private TemaService $service)
    {
    }

    #[OA\Get(
        path: "/temas",
        description: "Listado de todos los temas registrados",
        summary: "Lista de temas",
        tags: ["Temas"],
        parameters: [
            new OA\Parameter(
                name: "titulo",
                in: "query",
                description: "Busqueda por titulo",
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
                    items: new OA\Items(ref: "#/components/schemas/TemaResponse")
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
            TemaRequestValidator::validateParams($params);
            JsonHelper::jsonResponse($this->service->getByParams($params), 200);
            return;
        }

        JsonHelper::jsonResponse($this->service->getAll(), 200);
    }

    #[OA\Get(
        path: "/temas/{id}",
        description: "Mostrar la informacion de un tema especifico",
        summary: "Obtener un tema",
        tags: ["Temas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tema a buscar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tema obtenido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TemaResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tema no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
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
        TemaRequestValidator::validateId($id);
        JsonHelper::jsonResponse($this->service->getById((int) $id), 200);
    }

    #[OA\Post(
        path: "/temas",
        description: "Crear un nuevo tema",
        summary: "Crear tema",
        tags: ["Temas"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/TemaRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tema creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TemaResponse")
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
                description: "El tema ya existe",
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
    public function createTema(): void
    {
        $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
        TemaRequestValidator::validateInput($input);
        JsonHelper::jsonResponse($this->service->createTema(TemaMapper::fromArray($input)), 201);
    }

    #[OA\Put(
        path: "/temas/{id}",
        description: "Actualizar la informacion de un tema existente",
        summary: "Actualizar tema",
        tags: ["Temas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tema a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/TemaRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tema actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TemaResponse")
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
                description: "Tema no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "El tema ya existe",
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
    public function updateTema(string $id): void
    {
        TemaRequestValidator::validateId($id);
        $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
        TemaRequestValidator::validateInput($input);
        JsonHelper::jsonResponse($this->service->updateTema((int) $id, TemaMapper::fromArray($input)), 200);
    }

    #[OA\Delete(
        path: "/temas/{id}",
        description: "Eliminar un tema existente",
        summary: "Eliminar tema",
        tags: ["Temas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tema a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Tema eliminado exitosamente"
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
                description: "Tema no encontrado",
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
    public function deleteTema(string $id): void
    {
        TemaRequestValidator::validateId($id);
        $this->service->deleteTema((int) $id);
        http_response_code(204);
    }
}
