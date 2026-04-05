<?php

declare(strict_types=1);

namespace App\Circulacion\Controllers;

use App\Circulacion\Exceptions\TipoPrestamoAlreadyExistsException;
use App\Circulacion\Exceptions\TipoPrestamoNotFoundException;
use App\Circulacion\Mappers\TipoPrestamoMapper;
use App\Circulacion\Services\TipoPrestamoService;
use App\Circulacion\Validators\TipoPrestamoRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class TipoPrestamoController
{
    public function __construct(private TipoPrestamoService $service)
    {
    }

    #[OA\Get(
        path: "/tipos-prestamos",
        description: "Listado de todos los tipos de prestamo registrados",
        summary: "Lista de tipos de prestamo",
        tags: ["Tipos de Prestamo"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/TipoPrestamoResponse")
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
        $tiposPrestamo = $this->service->getAll();
        JsonHelper::jsonResponse($tiposPrestamo, 200);
    }

    #[OA\Get(
        path: "/tipos-prestamos/{id}",
        description: "Mostrar la informacion de un tipo de prestamo especifico",
        summary: "Obtener un tipo de prestamo",
        tags: ["Tipos de Prestamo"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de prestamo a buscar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de prestamo obtenido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TipoPrestamoResponse")
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
                description: "Tipo de prestamo no encontrado",
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
        TipoPrestamoRequestValidator::validateId($id);

        $tipoPrestamo = $this->service->getById((int) $id);
        JsonHelper::jsonResponse($tipoPrestamo, 200);
    }

    #[OA\Post(
        path: "/tipos-prestamos",
        description: "Crear un nuevo tipo de prestamo",
        summary: "Crear tipo de prestamo",
        tags: ["Tipos de Prestamo"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateTipoPrestamoRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tipo de prestamo creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TipoPrestamoResponse")
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
                description: "El tipo de prestamo ya existe",
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
    public function createTipoPrestamo(): void
    {
        $input = json_decode(file_get_contents("php://input"), true) ?? [];
        TipoPrestamoRequestValidator::validateInput($input);

        $request = TipoPrestamoMapper::fromArrayToCreate($input);

        $tipoPrestamo = $this->service->createTipoPrestamo($request);

        JsonHelper::jsonResponse($tipoPrestamo, 201);
    }

    #[OA\Patch(
        path: "/tipos-prestamos/{id}",
        description: "Actualizar la informacion de un tipo de prestamo existente",
        summary: "Actualizar tipo de prestamo",
        tags: ["Tipos de Prestamo"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de prestamo a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateTipoPrestamoRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de prestamo actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/TipoPrestamoResponse")
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
                description: "Tipo de prestamo no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "El tipo de prestamo ya existe",
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
    public function updateTipoPrestamo(string $id): void
    {
        TipoPrestamoRequestValidator::validateId($id);

        $input = json_decode(file_get_contents("php://input"), true) ?? [];
        TipoPrestamoRequestValidator::validateUpdateInput($input);

        $request = TipoPrestamoMapper::fromArrayToUpdate($input);

        $tipoPrestamoActualizado = $this->service->updateTipoPrestamo((int) $id, $request);
        JsonHelper::jsonResponse($tipoPrestamoActualizado, 200);
    }

    #[OA\Patch(
        path: "/tipos-prestamos/{id}/deshabilitar",
        description: "Deshabilitar un tipo de prestamo existente",
        summary: "Deshabilitar tipo de prestamo",
        tags: ["Tipos de Prestamo"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de prestamo a deshabilitar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Tipo de prestamo deshabilitado exitosamente"
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
                description: "Tipo de prestamo no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Tipo de prestamo ya deshabilitado",
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
    public function disableTipoPrestamo(string $id): void
    {
        TipoPrestamoRequestValidator::validateId($id);

        $this->service->disableTipoPrestamo((int) $id);
        http_response_code(204);
    }

    #[OA\Patch(
        path: "/tipos-prestamos/{id}/habilitar",
        description: "Habilitar un tipo de prestamo que se encuentra deshabilitado",
        summary: "Habilitar tipo de prestamo",
        tags: ["Tipos de Prestamo"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "id del tipo de prestamo a habilitar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Tipo de prestamo habilitado exitosamente"
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
                description: "Tipo de prestamo no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "El tipo de prestamo ya se encuentra habilitado",
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
    public function enableTipoPrestamo(string $id): void
    {
        TipoPrestamoRequestValidator::validateId($id);

        $this->service->enableTipoPrestamo((int) $id);
        http_response_code(204);
    }
}
