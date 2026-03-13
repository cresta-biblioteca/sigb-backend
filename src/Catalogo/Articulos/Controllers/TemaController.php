<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\TemaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Mappers\TemaMapper;
use App\Catalogo\Articulos\Services\TemaService;
use App\Catalogo\Articulos\Validators\TemaRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use OpenApi\Annotations\JsonContent;
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
        $params = array_intersect_key($_GET, array_flip(self::ALLOWED_PARAMS));
        $params = array_filter($params, fn($value) => $value !== '');

        if (!empty($params)) {
            $this->getByParams($params);
            return;
        }

        try {
            $temas = $this->service->getAll();
            JsonHelper::jsonResponse($temas, 200);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getAll");
        }
    }

    #[OA\Get(
        path: "temas/{id}",
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
        try {
            TemaRequestValidator::validateId($id);

            $tema = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($tema, 200);
        } catch (TemaNotFoundException $e) {
            $this->temaNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getById");
        }
    }

    private function getByParams(array $params): void
    {
        try {
            TemaRequestValidator::validateParams($params);

            $temas = $this->service->getByParams($params);
            JsonHelper::jsonResponse($temas, 200);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getByParams");
        }
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
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            TemaRequestValidator::validateInput($input);

            $request = TemaMapper::fromArray($input);

            $tema = $this->service->createTema($request);
            JsonHelper::jsonResponse($tema, 201);
        } catch (TemaAlreadyExistsException $e) {
            $this->temaExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "createTema");
        }
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
        try {
            TemaRequestValidator::validateId($id);
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            TemaRequestValidator::validateInput($input);

            $request = TemaMapper::fromArray($input);

            $temaActualizado = $this->service->updateTema((int) $id, $request);
            JsonHelper::jsonResponse($temaActualizado, 200);
        } catch (TemaNotFoundException $e) {
            $this->temaNotFoundResponse($e);
        } catch (TemaAlreadyExistsException $e) {
            $this->temaExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "updateTema");
        }
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
        try {
            TemaRequestValidator::validateId($id);

            $this->service->deleteTema((int) $id);
            http_response_code(204);
        } catch (TemaNotFoundException $e) {
            $this->temaNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteTema");
        }
    }

    private function temaNotFoundResponse(TemaNotFoundException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 404);
        return;
    }

    private function temaExistsResponse(TemaAlreadyExistsException $e): void
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
            "message" => $e->getMessage(),
            "field" => $e->getField()
        ], 422);
    }

    private function exceptionResponse(\Exception $e, string $method): void
    {
        JsonHelper::jsonResponse([
            "message" => "Error interno del servidor"
        ], 500);
        error_log("[TemaController::{$method}] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
    }
}
