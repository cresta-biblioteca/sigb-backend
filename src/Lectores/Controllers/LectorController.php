<?php

declare(strict_types=1);

namespace App\Lectores\Controllers;

use App\Lectores\Services\LectorService;
use App\Lectores\Validators\LectorRequestValidator;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class LectorController
{
    public function __construct(private readonly LectorService $lectorService)
    {
    }

    #[OA\Get(
        path: "/lectores/mi-perfil",
        description: "Devuelve la información del perfil del lector autenticado",
        summary: "Mi perfil",
        security: [["bearerAuth" => []]],
        tags: ["Lectores"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Perfil obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/LectorPerfilResponse")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Lector no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getMiPerfil(): void
    {
        $userId = (int) $_SERVER['USER_ID'];
        $dni = (string) $_SERVER['USER_DNI'];

        $perfil = $this->lectorService->getPerfil($userId, $dni);

        JsonHelper::jsonResponse(['data' => $perfil], 200);
    }

    #[OA\Post(
        path: "/lectores/{lectorId}/carreras/{carreraId}",
        description: "Asigna una carrera a un lector",
        summary: "Asignar carrera a lector",
        security: [["bearerAuth" => []]],
        tags: ["Lectores"],
        parameters: [
            new OA\Parameter(
                name: "lectorId",
                in: "path",
                description: "ID del lector",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "carreraId",
                in: "path",
                description: "ID de la carrera",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 201, description: "Carrera asignada exitosamente"),
            new OA\Response(response: 400, description: "Datos inválidos"),
            new OA\Response(response: 404, description: "Lector o carrera no encontrados"),
            new OA\Response(response: 409, description: "La carrera ya está asignada"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function assignCarrera(string $lectorId, string $carreraId): void
    {
        LectorRequestValidator::validateId($lectorId, 'lectorId');
        LectorRequestValidator::validateId($carreraId, 'carreraId');

        $this->lectorService->assignCarrera((int) $lectorId, (int) $carreraId);
        http_response_code(201);
    }

    #[OA\Delete(
        path: "/lectores/{lectorId}/carreras/{carreraId}",
        description: "Quita una carrera asignada a un lector",
        summary: "Quitar carrera de lector",
        security: [["bearerAuth" => []]],
        tags: ["Lectores"],
        parameters: [
            new OA\Parameter(
                name: "lectorId",
                in: "path",
                description: "ID del lector",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "carreraId",
                in: "path",
                description: "ID de la carrera",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Carrera quitada exitosamente"),
            new OA\Response(response: 400, description: "Datos inválidos"),
            new OA\Response(response: 404, description: "Lector o carrera no encontrados"),
            new OA\Response(response: 409, description: "La carrera no está asignada al lector"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function removeCarrera(string $lectorId, string $carreraId): void
    {
        LectorRequestValidator::validateId($lectorId, 'lectorId');
        LectorRequestValidator::validateId($carreraId, 'carreraId');

        $this->lectorService->removeCarrera((int) $lectorId, (int) $carreraId);
        JsonHelper::jsonResponse(['message' => 'La carrera ha sido quitada del lector'], 200);
    }
}
