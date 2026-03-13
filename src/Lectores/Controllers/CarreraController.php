<?php

declare(strict_types=1);

namespace App\Lectores\Controllers;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyInCarreraException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Lectores\Exceptions\CarreraAlreadyExistsException;
use App\Lectores\Exceptions\CarreraNotFoundException;
use App\Lectores\Mappers\CarreraMapper;
use App\Lectores\Services\CarreraService;
use App\Lectores\Validators\CarreraRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
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
            $params = array_intersect_key($_GET, array_flip(self::SEARCH_PARAMS));
            $params = array_filter($params, fn($value) => $value !== '');

            if (!empty($params)) {
                $this->getByParams($params);
                return;
            }
            $carreras = $this->service->getAll();
            JsonHelper::jsonResponse($carreras, 200);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getAll");
        }
    }

    private function getByParams(array $params): void
    {
        try {
            CarreraRequestValidator::validateParams($params);

            $carreras = $this->service->getByParams($params);
            JsonHelper::jsonResponse($carreras, 200);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getByParams");
        }
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
            )
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Carrera no encontrada",
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
        try {
            CarreraRequestValidator::validateId($id);

            $carrera = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($carrera, 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getById");
        }
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Materias obtenidas exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "carreraId", type: "string"),
                        new OA\Property(
                            property: "materias",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/MateriaResponse")
                        )
                    ]
                )
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
                description: "Carrera no encontrada",
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
    public function getMateriasByCarrera($idCarrera): void
    {
        try {
            CarreraRequestValidator::validateId($idCarrera);

            $materias = $this->service->getMateriasByCarrera((int) $idCarrera);

            JsonHelper::jsonResponse(["carreraId" => $idCarrera, "materias" => $materias], 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getMateriasByCarrera");
        }
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "La carrera ya existe",
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
    public function createCarrera(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            CarreraRequestValidator::validateInput($input);

            $request = CarreraMapper::fromArrayToCreate($input);

            $carrera = $this->service->createCarrera($request);

            JsonHelper::jsonResponse($carrera, 201);
        } catch (CarreraAlreadyExistsException $e) {
            $this->carreraExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "createCarrera");
        }
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
            )
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
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Carrera no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "La carrera ya existe",
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
    public function updateCarrera(string $id): void
    {
        try {
            CarreraRequestValidator::validateId($id);

            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            CarreraRequestValidator::validateUpdateInput($input);

            $carrera = CarreraMapper::fromArrayToUpdate($input);

            $carreraActualizada = $this->service->updateCarrera((int) $id, $carrera);
            JsonHelper::jsonResponse($carreraActualizada, 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (CarreraAlreadyExistsException $e) {
            $this->carreraExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "updateCarrera");
        }
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
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Carrera eliminada exitosamente"
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
                description: "Carrera no encontrada",
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
    public function deleteCarrera(string $id): void
    {
        try {
            CarreraRequestValidator::validateId($id);

            $this->service->deleteCarrera((int) $id);
            http_response_code(204);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteCarrera");
        }
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
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Materia agregada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
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
                description: "Carrera o materia no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "La materia ya esta asociada a la carrera",
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
    public function addMateriaToCarrera(string $idCarrera, string $idMateria): void
    {
        try {
            CarreraRequestValidator::validateId($idCarrera);
            CarreraRequestValidator::validateId($idMateria);

            $this->service->addMateriaToCarrera((int) $idCarrera, (int) $idMateria);
            JsonHelper::jsonResponse(["message" => "La materia ha sido agregada!"], 201);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse([
                "message" => $e->getMessage()
            ], 404);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (MateriaAlreadyInCarreraException $e) {
            $this->conflictsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "addMateria");
        }
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Materia eliminada de la carrera exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
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
                description: "Carrera o materia no encontrada",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "La materia ya fue eliminada de la carrera",
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
    public function deleteMateriaFromCarrera(string $idCarrera, string $idMateria): void
    {
        try {
            CarreraRequestValidator::validateId($idCarrera);
            CarreraRequestValidator::validateId($idMateria);

            $this->service->deleteMateriaFromCarrera((int) $idCarrera, (int) $idMateria);
            JsonHelper::jsonResponse([
                "message" => "Materia eliminada correctamente!"
            ], 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse([
                "message" => $e->getMessage()
            ], 404);
        } catch (MateriaAlreadyEliminatedException $e) {
            $this->conflictsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteMateriaFromCarrera");
        }
    }

    private function carreraNotFoundResponse(CarreraNotFoundException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 404);
        return;
    }

    private function carreraExistsResponse(CarreraAlreadyExistsException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 409);
    }

    private function conflictsResponse(BusinessValidationException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 409);
    }

    private function validationResponse(ValidationException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage(),
            "errors" => $e->getErrors()
        ], 400);
    }
    private function businessValidationResponse(BusinessValidationException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 422);
    }

    private function exceptionResponse(\Exception $e, string $method): void
    {
        JsonHelper::jsonResponse([
            "message" => "Error interno del servidor"
        ], 500);
        error_log("[CarreraController::{$method}] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
    }
}
