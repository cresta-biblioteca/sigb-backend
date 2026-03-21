<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Mappers\MateriaMapper;
use App\Catalogo\Articulos\Services\MateriaService;
use App\Catalogo\Articulos\Validators\MateriaRequestValidator;
use App\Shared\Http\ExceptionHandler;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;
use Throwable;

class MateriaController
{
    private const ALLOWED_PARAMS = ["titulo", "order"];
    public function __construct(private MateriaService $service)
    {
    }

    #[OA\Get(
        path: "/materias",
        description: "Listado de todas las materias registradas",
        summary: "Lista de materias",
        tags: ["Materias"],
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
                    items: new OA\Items(ref: "#/components/schemas/MateriaResponse")
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
        try {
            $params = array_filter(
                array_intersect_key($_GET, array_flip(self::ALLOWED_PARAMS)),
                fn($value) => $value !== ''
            );

            if (!empty($params)) {
                MateriaRequestValidator::validateParams($params);
                JsonHelper::jsonResponse($this->service->getByParams($params), 200);
                return;
            }

            JsonHelper::jsonResponse($this->service->getAll(), 200);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'MateriaController::getAll');
        }
    }

    #[OA\Get(
        path: "/materias/{id}",
        description: "Mostrar la informacion de una materia especifica",
        summary: "Obtener una materia",
        tags: ["Materias"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la materia a buscar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Materia obtenida exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/MateriaResponse")
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
                description: "Materia no encontrada",
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
    public function getById($id): void
    {
        try {
            MateriaRequestValidator::validateId($id);
            JsonHelper::jsonResponse($this->service->getById((int) $id), 200);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'MateriaController::getById');
        }
    }

    #[OA\Post(
        path: "/materias",
        description: "Crear una nueva materia",
        summary: "Crear materia",
        tags: ["Materias"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/MateriaRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Materia creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/MateriaResponse")
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
                description: "La materia ya existe",
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
    public function createMateria(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
            MateriaRequestValidator::validate($input);
            JsonHelper::jsonResponse($this->service->createMateria(MateriaMapper::fromArray($input)), 201);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'MateriaController::createMateria');
        }
    }

    #[OA\Put(
        path: "/materias/{id}",
        description: "Actualizar la informacion de una materia existente",
        summary: "Actualizar materia",
        tags: ["Materias"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la materia a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/MateriaRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Materia actualizada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/MateriaResponse")
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
                description: "Materia no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "La materia ya existe",
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
    public function updateMateria($id): void
    {
        try {
            MateriaRequestValidator::validateId($id);
            $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
            MateriaRequestValidator::validate($input);
            JsonHelper::jsonResponse($this->service->updateMateria((int) $id, MateriaMapper::fromArray($input)), 200);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'MateriaController::updateMateria');
        }
    }

    #[OA\Delete(
        path: "/materias/{id}",
        description: "Eliminar una materia existente",
        summary: "Eliminar materia",
        tags: ["Materias"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la materia a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Materia eliminada exitosamente"
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
                description: "Materia no encontrada",
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
    public function deleteMateria($id): void
    {
        try {
            MateriaRequestValidator::validateId($id);
            $this->service->deleteMateria((int) $id);
            http_response_code(204);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'MateriaController::deleteMateria');
        }
    }
}
