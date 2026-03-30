<?php

declare(strict_types=1);

namespace App\Lectores\Controllers;

use App\Lectores\Mappers\CarreraMapper;
use App\Lectores\Services\CarreraService;
use App\Lectores\Validators\CarreraRequestValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class CarreraController
{
    public function __construct(private CarreraService $service)
    {
    }

    private const SEARCH_PARAMS = ['cod', 'nombre', 'order'];

    #[OA\Get(
        path: "/carreras",
        description: "Listado de todas las carreras registradas",
        summary: "Lista de carreras",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "cod",
                in: "query",
                description: "Busqueda por codigo",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "nombre",
                in: "query",
                description: "Busqueda por nombre",
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
                    items: new OA\Items(ref: "#/components/schemas/CarreraResponse")
                )
            ),
            new OA\Response(
                response: 400,
                description: "Datos invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getAll(): void
    {
        $params = array_filter(
            array_intersect_key($_GET, array_flip(self::SEARCH_PARAMS)),
            fn($value) => $value !== ''
        );

        if (!empty($params)) {
            CarreraRequestValidator::validateParams($params);
            JsonHelper::jsonResponse($this->service->getByParams($params), 200);
            return;
        }

        JsonHelper::jsonResponse($this->service->getAll(), 200);
    }

    #[OA\Get(
        path: "/carreras/{id}",
        description: "Mostrar la informacion de una carrera especifica",
        summary: "Obtener una carrera",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la carrera a buscar",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Carrera obtenida exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CarreraResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 404,
                description: "Carrera no encontrada",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getById(string $id): void
    {
        CarreraRequestValidator::validateId($id);
        JsonHelper::jsonResponse($this->service->getById((int) $id), 200);
    }

    #[OA\Get(
        path: "/carreras/{id}/materias",
        description: "Obtener las materias asociadas a una carrera",
        summary: "Materias de una carrera",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la carrera",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Materias obtenidas exitosamente",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "carreraId", type: "string"),
                    new OA\Property(
                        property: "materias",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/MateriaResponse")
                    ),
                ])
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 404,
                description: "Carrera no encontrada",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getMateriasByCarrera($idCarrera): void
    {
        CarreraRequestValidator::validateId($idCarrera);
        $materias = $this->service->getMateriasByCarrera((int) $idCarrera);
        JsonHelper::jsonResponse(["carreraId" => $idCarrera, "materias" => $materias], 200);
    }

    #[OA\Post(
        path: "/carreras",
        description: "Crear una nueva carrera",
        summary: "Crear carrera",
        tags: ["Carreras"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateCarreraRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Carrera creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CarreraResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 409,
                description: "La carrera ya existe",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 422,
                description: "Error de validacion de negocio",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function createCarrera(): void
    {
        $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
        CarreraRequestValidator::validateInput($input);
        $carrera = $this->service->createCarrera(CarreraMapper::fromArrayToCreate($input));
        JsonHelper::jsonResponse($carrera, 201);
    }

    #[OA\Patch(
        path: "/carreras/{id}",
        description: "Actualizar la informacion de una carrera existente",
        summary: "Actualizar carrera",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la carrera a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateCarreraRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Carrera actualizada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CarreraResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 404,
                description: "Carrera no encontrada",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 409,
                description: "La carrera ya existe",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 422,
                description: "Error de validacion de negocio",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function updateCarrera(string $id): void
    {
        CarreraRequestValidator::validateId($id);
        $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR) ?? [];
        CarreraRequestValidator::validateUpdateInput($input);
        $carrera = $this->service->updateCarrera((int) $id, CarreraMapper::fromArrayToUpdate($input));
        JsonHelper::jsonResponse($carrera, 200);
    }

    #[OA\Delete(
        path: "/carreras/{id}",
        description: "Eliminar una carrera existente",
        summary: "Eliminar carrera",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id de la carrera a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: "Carrera eliminada exitosamente"),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 404,
                description: "Carrera no encontrada",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function deleteCarrera(string $id): void
    {
        CarreraRequestValidator::validateId($id);
        $this->service->deleteCarrera((int) $id);
        http_response_code(204);
    }

    #[OA\Post(
        path: "/carreras/{idCarrera}/materias/{idMateria}",
        description: "Agregar una materia a una carrera",
        summary: "Agregar materia a carrera",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "idCarrera",
                in: "path",
                description: "id de la carrera",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "idMateria",
                in: "path",
                description: "id de la materia a agregar",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Materia agregada exitosamente",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 404,
                description: "Carrera o materia no encontrada",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 409,
                description: "La materia ya esta asociada a la carrera",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function addMateriaToCarrera(string $idCarrera, string $idMateria): void
    {
        CarreraRequestValidator::validateId($idCarrera);
        CarreraRequestValidator::validateId($idMateria);
        $this->service->addMateriaToCarrera((int) $idCarrera, (int) $idMateria);
        JsonHelper::jsonResponse(["message" => "La materia ha sido agregada!"], 201);
    }

    #[OA\Delete(
        path: "/carreras/{idCarrera}/materias/{idMateria}",
        description: "Eliminar una materia de una carrera",
        summary: "Eliminar materia de carrera",
        tags: ["Carreras"],
        parameters: [
            new OA\Parameter(
                name: "idCarrera",
                in: "path",
                description: "id de la carrera",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "idMateria",
                in: "path",
                description: "id de la materia a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Materia eliminada de la carrera exitosamente",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada invalidos",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                    new OA\Property(property: "errors", type: "object"),
                ])
            ),
            new OA\Response(
                response: 404,
                description: "Carrera o materia no encontrada",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(
                response: 409,
                description: "La materia ya fue eliminada de la carrera",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "message", type: "string"),
                ])
            ),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function deleteMateriaFromCarrera(string $idCarrera, string $idMateria): void
    {
        CarreraRequestValidator::validateId($idCarrera);
        CarreraRequestValidator::validateId($idMateria);
        $this->service->deleteMateriaFromCarrera((int) $idCarrera, (int) $idMateria);
        JsonHelper::jsonResponse(["message" => "Materia eliminada correctamente!"], 200);
    }
}
