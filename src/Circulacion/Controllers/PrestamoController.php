<?php

declare(strict_types=1);

namespace App\Circulacion\Controllers;

use App\Circulacion\Mappers\PrestamoMapper;
use App\Circulacion\Services\PrestamoService;
use App\Circulacion\Validators\PrestamoRequestValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class PrestamoController
{
    public function __construct(private PrestamoService $service)
    {
    }

    #[OA\Post(
        path: "/prestamos",
        description: "Crea un nuevo préstamo a partir de una reserva pendiente. Completa la reserva "
            . "y genera el préstamo con la duración definida por el tipo de préstamo.",
        summary: "Crear préstamo desde reserva",
        tags: ["Préstamos"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreatePrestamoRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Préstamo creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/PrestamoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Reserva o tipo de préstamo no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación de negocio",
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
    public function createPrestamo(): void
    {
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) ?? [];

        PrestamoRequestValidator::validateCreateFromReserva($data);

        $response = $this->service->createPrestamo(PrestamoMapper::toCreateRequest($data));

        JsonHelper::jsonResponse($response, 201);
    }

    #[OA\Get(
        path: "/prestamos",
        description: "Obtiene un listado paginado de todos los préstamos. "
            . "Se pueden aplicar filtros opcionales por estado, lector y tipo de préstamo.",
        summary: "Listar préstamos",
        tags: ["Préstamos"],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Número de página",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Cantidad de resultados por página",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado del préstamo",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: [
                        "VIGENTE",
                        "COMPLETADO_EXITO",
                        "COMPLETADO_VENCIDO",
                        "INCONVENIENTE"
                    ]
                )
            ),
            new OA\Parameter(
                name: "lector_id",
                in: "query",
                description: "Filtrar por ID del lector",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "tipo_prestamo_id",
                in: "query",
                description: "Filtrar por tipo de préstamo",
                required: false,
                schema: new OA\Schema(type: "integer")
            )
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
                            items: new OA\Items(ref: "#/components/schemas/PrestamoResponse")
                        ),
                        new OA\Property(property: "total", type: "integer"),
                        new OA\Property(property: "page", type: "integer"),
                        new OA\Property(property: "per_page", type: "integer")
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
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, min(100, (int) $_GET['per_page'])) : 15;

        $filters = [];
        if (!empty($_GET['estado'])) {
            $estado = strtoupper((string) $_GET['estado']);
            PrestamoRequestValidator::validateFiltroEstado($estado);
            $filters['estado'] = $_GET['estado'];
        }
        if (!empty($_GET['lector_id'])) {
            $filters['lector_id'] = (int) $_GET['lector_id'];
        }
        if (!empty($_GET['tipo_prestamo_id'])) {
            $filters['tipo_prestamo_id'] = (int) $_GET['tipo_prestamo_id'];
        }

        $result = $this->service->getAll($page, $perPage, $filters);

        JsonHelper::jsonResponse($result, 200);
    }

    #[OA\Get(
        path: "/prestamos/{id}",
        description: "Obtiene el detalle de un préstamo con sus relaciones (tipo de préstamo, ejemplar, lector)",
        summary: "Obtener préstamo por ID",
        tags: ["Préstamos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del préstamo",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Préstamo obtenido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/PrestamoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos"
            ),
            new OA\Response(
                response: 404,
                description: "Préstamo no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function getById(string $id): void
    {
        PrestamoRequestValidator::validateId($id);

        $response = $this->service->getById((int) $id);

        JsonHelper::jsonResponse($response, 200);
    }

    #[OA\Get(
        path: "/lector/{lectorId}/prestamos",
        description: "Obtiene todos los préstamos de un lector, opcionalmente filtrados por estado",
        summary: "Préstamos de un lector",
        tags: ["Préstamos"],
        parameters: [
            new OA\Parameter(
                name: "lectorId",
                in: "path",
                description: "ID del lector",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["VIGENTE", "COMPLETADO_EXITO", "COMPLETADO_VENCIDO", "INCONVENIENTE"]
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PrestamoResponse")
                )
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos"
            ),
            new OA\Response(
                response: 404,
                description: "Lector no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function getByLector(string $lectorId): void
    {
        PrestamoRequestValidator::validateLectorId($lectorId);

        $estado = $_GET['estado'] ?? null;
        PrestamoRequestValidator::validateFiltroEstado($estado);

        $response = $this->service->getByLectorId((int) $lectorId, $estado);

        JsonHelper::jsonResponse($response, 200);
    }

    #[OA\Patch(
        path: "/prestamos/{id}/devolver",
        description: "Registra la devolución de un préstamo activo",
        summary: "Devolver préstamo",
        tags: ["Préstamos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del préstamo a devolver",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Préstamo devuelto exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/PrestamoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos"
            ),
            new OA\Response(
                response: 404,
                description: "Préstamo no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "El préstamo ya fue devuelto"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function return(string $id): void
    {
        PrestamoRequestValidator::validateId($id);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        PrestamoRequestValidator::validateInputReturn($input);

        $response = $this->service->devolver((int) $id, $input['hubo_inconveniente'] ?? false);

        JsonHelper::jsonResponse($response, 200);
    }

    #[OA\Patch(
        path: "/prestamos/{id}/renovar",
        description: "Renueva un préstamo activo extendiendo su fecha de vencimiento "
            . "según las reglas del tipo de préstamo",
        summary: "Renovar préstamo",
        tags: ["Préstamos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del préstamo a renovar",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Préstamo renovado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/PrestamoResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos"
            ),
            new OA\Response(
                response: 404,
                description: "Préstamo no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "Renovación no permitida"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function renew(string $id): void
    {
        PrestamoRequestValidator::validateId($id);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        PrestamoRequestValidator::validateInputRenew($input);
        $response = $this->service->renovar((int) $id, $input['tipo_prestamo_id'] ?? null);

        JsonHelper::jsonResponse($response, 200);
    }
}
