<?php

declare(strict_types=1);

namespace App\Circulacion\Controllers;

use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Services\ReservaService;
use App\Circulacion\Validators\CreateReservaValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

readonly class ReservaController
{
    public function __construct(private ReservaService $reservaService)
    {
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

    #[OA\Delete(
        path: "/reservas/{idReserva}",
        description: "Cancela una reserva activa. Libera el ejemplar asignado si"
            . " lo tiene y promueve la siguiente reserva en cola.",
        summary: "Cancelar reserva",
        security: [["bearerAuth" => []]],
        tags: ["Reservas"],
        parameters: [
            new OA\Parameter(
                name: "idReserva",
                in: "path",
                description: "ID de la reserva a cancelar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
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
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Reserva no encontrada"),
            new OA\Response(response: 422, description: "La reserva no puede ser cancelada"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function cancelarReserva($idReserva): void
    {
        $this->reservaService->cancelarReserva((int)$idReserva);

        JsonHelper::jsonResponse(["message" => "Reserva cancelada exitosamente"], 200);
    }
}
