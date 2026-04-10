<?php

declare(strict_types=1);

namespace App\Circulacion\Controllers;

use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Models\EstadoReserva;
use App\Circulacion\Services\ReservaService;
use App\Circulacion\Validators\CreateReservaValidator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

readonly class ReservaController
{
    public function __construct(private ReservaService $reservaService)
    {
    }

    #[OA\Get(
        path: "/reservas",
        description: "Retorna todas las reservas con filtros opcionales. Solo accesible por administradores.",
        summary: "Listar todas las reservas (admin)",
        tags: ["Reservas"],
        parameters: [
            new OA\Parameter(
                name: "estado",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["PENDIENTE", "COMPLETADA", "CANCELADA", "VENCIDA"])
            ),
            new OA\Parameter(
                name: "lector_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "articulo_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 5)
            ),
            new OA\Parameter(
                name: "ejemplar_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 3)
            ),
            new OA\Parameter(
                name: "fecha_desde",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2026-01-01")
            ),
            new OA\Parameter(
                name: "fecha_hasta",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2026-12-31")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1, example: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10, maximum: 100, example: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado paginado de reservas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/ReservaResponse")
                        ),
                        new OA\Property(
                            property: "pagination",
                            properties: [
                                new OA\Property(property: "page", type: "integer"),
                                new OA\Property(property: "per_page", type: "integer"),
                                new OA\Property(property: "total", type: "integer"),
                                new OA\Property(property: "total_pages", type: "integer"),
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Parametros de filtro invalidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Error interno del servidor")
        ]
    )]
    public function getReservas(): void
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
        $filters = $this->parseAdminFilters($_GET);
        $result = $this->reservaService->getReservas($filters, $page, $perPage);
        JsonHelper::jsonResponse(['data' => $result['items'], 'pagination' => $result['pagination']]);
    }

    #[OA\Get(
        path: "/lectores/me/reservas",
        description: "Retorna las reservas del usuario autenticado. El ID del lector se obtiene del token JWT.",
        summary: "Listar mis reservas",
        tags: ["Reservas"],
        parameters: [
            new OA\Parameter(
                name: "estado",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["PENDIENTE", "COMPLETADA", "CANCELADA", "VENCIDA"])
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1, example: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10, maximum: 100, example: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado paginado de reservas del usuario autenticado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/ReservaResponse")
                        ),
                        new OA\Property(
                            property: "pagination",
                            properties: [
                                new OA\Property(property: "page", type: "integer"),
                                new OA\Property(property: "per_page", type: "integer"),
                                new OA\Property(property: "total", type: "integer"),
                                new OA\Property(property: "total_pages", type: "integer"),
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Parametro de filtro invalido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Error interno del servidor")
        ]
    )]
    public function getMisReservas(): void
    {
        $lectorId = (int) $_SERVER['USER_ID'];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
        $estado = $this->parseEstado($_GET['estado'] ?? null);
        $result = $this->reservaService->getMisReservas($lectorId, $estado, $page, $perPage);
        JsonHelper::jsonResponse(['data' => $result['items'], 'pagination' => $result['pagination']]);
    }

    #[OA\Post(
        path: "/reservas",
        description: "Crear una nueva reserva para un articulo. Si hay ejemplares disponibles se asigna uno" .
        " de inmediato con fecha de vencimiento; si no, queda en cola de espera.",
        summary: "Crear reserva",
        tags: ["Reservas"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateReservaRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Reserva creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ReservaResponse")
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
                description: "Articulo no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "El lector ya tiene una reserva pendiente o un prestamo activo para este articulo",
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
    public function addReserva(): void
    {
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) ?? [];

        CreateReservaValidator::validate($data);

        $reservaRequest = CreateReservaRequest::fromArray($data);

        $response = $this->reservaService->addReserva($reservaRequest);

        JsonHelper::jsonResponse($response, 201);
    }

    #[OA\Get(
        path: "/reservas/{id}",
        summary: "Obtener reserva por ID",
        tags: ["Reservas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reserva encontrada",
                content: new OA\JsonContent(ref: "#/components/schemas/ReservaResponse")
            ),
            new OA\Response(
                response: 400,
                description: "ID invalido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Reserva no encontrada",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string")]
                )
            ),
            new OA\Response(response: 500, description: "Error interno del servidor")
        ]
    )]
    public function getReservaById(int $id): void
    {
        if ($id < 1) {
            throw ValidationException::forField('id', 'El ID debe ser un entero positivo mayor que 0');
        }

        $response = $this->reservaService->getReservaById($id);
        JsonHelper::jsonResponse($response);
    }

    #[OA\Patch(
        path: "/reservas/{id}/cancelar",
        summary: "Cancelar una reserva pendiente",
        tags: ["Reservas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reserva cancelada exitosamente",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string")]
                )
            ),
            new OA\Response(
                response: 400,
                description: "ID invalido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Reserva no encontrada",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string")]
                )
            ),
            new OA\Response(
                response: 422,
                description: "La reserva no puede ser cancelada (no esta pendiente o ya vencio)",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string")]
                )
            ),
            new OA\Response(response: 500, description: "Error interno del servidor")
        ]
    )]
    public function cancelarReserva(int $idReserva): void
    {
        if ($idReserva < 1) {
            throw ValidationException::forField('id', 'El ID debe ser un entero positivo mayor que 0');
        }

        $this->reservaService->cancelarReserva($idReserva);
        JsonHelper::jsonResponse(["message" => "Reserva cancelada exitosamente"], 200);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function parseAdminFilters(array $query): array
    {
        $filters = [];

        if (isset($query['estado']) && $query['estado'] !== '') {
            $filters['estado'] = $this->parseEstado($query['estado'])->value;
        }

        foreach (['lector_id', 'articulo_id', 'ejemplar_id'] as $field) {
            if (isset($query[$field]) && $query[$field] !== '') {
                $value = (int)$query[$field];
                if ($value < 1) {
                    throw ValidationException::forField($field, "El campo $field debe ser un entero positivo");
                }
                $filters[$field] = $value;
            }
        }

        foreach (['fecha_desde', 'fecha_hasta'] as $field) {
            if (isset($query[$field]) && $query[$field] !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $query[$field])) {
                    throw ValidationException::forField($field, "El campo $field debe tener formato YYYY-MM-DD");
                }
                $filters[$field] = $query[$field];
            }
        }

        if (
            isset($filters['fecha_desde'], $filters['fecha_hasta'])
            && $filters['fecha_desde'] > $filters['fecha_hasta']
        ) {
            throw ValidationException::forField('fecha_desde', 'fecha_desde no puede ser posterior a fecha_hasta');
        }

        return $filters;
    }

    private function parseEstado(?string $value): ?EstadoReserva
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Intenta generar un enum basado en el value sino existe devuelve null
        $estado = EstadoReserva::tryFrom(strtoupper($value));
        if ($estado === null) {
            throw ValidationException::forField(
                'estado',
                'El valor debe ser uno de: PENDIENTE, COMPLETADA, CANCELADA, VENCIDA'
            );
        }

        return $estado;
    }
}
