<?php

declare(strict_types=1);

namespace App\Lectores\Controllers;

use App\Lectores\Services\LectorService;
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
}
