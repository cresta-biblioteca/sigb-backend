<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Controllers;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Services\EjemplarService;
use App\Catalogo\Ejemplares\Validators\EjemplarRequestValidator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class EjemplarController
{
    public function __construct(private EjemplarService $ejemplarService)
    {
    }

    #[OA\Get(
        path: "/ejemplares",
        description: "Listado de ejemplares con filtros opcionales. Si se provee `codigo_barras` retorna el ejemplar"
            . " exacto. Si se provee `articulo_id` retorna los ejemplares de ese artículo"
            . " (con `habilitado=true` solo los habilitados). Si se provee `habilitado` filtra por estado.",
        summary: "Listar ejemplares",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "codigo_barras",
                in: "query",
                description: "Código de barras exacto",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "articulo_id",
                in: "query",
                description: "ID del artículo",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "habilitado",
                in: "query",
                description: "Filtrar por estado habilitado",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/EjemplarResponse")
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Parámetros inválidos"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getAll(): void
    {
        if (isset($_GET['codigo_barras']) && $_GET['codigo_barras'] !== '') {
            $codigoBarras = trim((string) $_GET['codigo_barras']);

            if (!EjemplarRequestValidator::validateCodigoBarras($codigoBarras)) {
                throw ValidationException::forField(
                    'codigo_barras',
                    'El campo codigo_barras debe contener solo dígitos (máximo 13)'
                );
            }

            $ejemplar = $this->ejemplarService->getByCodigoBarras($codigoBarras);
            JsonHelper::jsonResponse(['data' => $ejemplar === null ? [] : [$ejemplar]]);
            return;
        }

        if (isset($_GET['articulo_id']) && $_GET['articulo_id'] !== '') {
            $articuloId = (int) $_GET['articulo_id'];
            EjemplarRequestValidator::validateId($articuloId, 'articulo_id');

            if (isset($_GET['habilitado']) && filter_var($_GET['habilitado'], FILTER_VALIDATE_BOOLEAN)) {
                $this->getHabilitadosByArticuloId($articuloId);
                return;
            }

            $this->getByArticuloId($articuloId);
            return;
        }

        if (isset($_GET['habilitado']) && $_GET['habilitado'] !== '') {
            $habilitado = filter_var($_GET['habilitado'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_bool($habilitado)) {
                throw ValidationException::forField('habilitado', 'El campo habilitado debe ser booleano');
            }

            JsonHelper::jsonResponse(['data' => $this->ejemplarService->getByHabilitado($habilitado)]);
            return;
        }

        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getAll()]);
    }

    #[OA\Get(
        path: "/ejemplares/{id}",
        description: "Obtener la información de un ejemplar por su ID",
        summary: "Obtener ejemplar por ID",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del ejemplar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Ejemplar obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "data", ref: "#/components/schemas/EjemplarResponse")]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Ejemplar no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getById(string $id): void
    {
        EjemplarRequestValidator::validateId($id);
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getById((int) $id)]);
    }

    #[OA\Post(
        path: "/ejemplares",
        description: "Crear un nuevo ejemplar asociado a un artículo",
        summary: "Crear ejemplar",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/EjemplarRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Ejemplar creado exitosamente",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "data", ref: "#/components/schemas/EjemplarResponse")]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Datos de entrada inválidos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 409, description: "El código de barras ya existe"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function createEjemplar(): void
    {
        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        EjemplarRequestValidator::validate($input);

        $request = new EjemplarRequest(
            (int) $input['articulo_id'],
            trim((string) $input['codigo_barras']),
            (bool) $input['habilitado'],
            isset($input['signatura_topografica']) ? trim((string) $input['signatura_topografica']) : null
        );

        JsonHelper::jsonResponse(['data' => $this->ejemplarService->createEjemplar($request)], 201);
    }

    #[OA\Put(
        path: "/ejemplares/{id}",
        description: "Reemplaza completamente los datos de un ejemplar existente",
        summary: "Actualizar ejemplar",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del ejemplar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/EjemplarRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Ejemplar actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "data", ref: "#/components/schemas/EjemplarResponse")]
                )
            ),
            new OA\Response(response: 400, description: "Datos de entrada inválidos"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Ejemplar no encontrado"),
            new OA\Response(response: 409, description: "El código de barras ya está en uso"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function updateEjemplar(string $id): void
    {
        EjemplarRequestValidator::validateId($id);

        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        EjemplarRequestValidator::validate($input);

        $request = new EjemplarRequest(
            (int) $input['articulo_id'],
            trim((string) $input['codigo_barras']),
            (bool) $input['habilitado'],
            isset($input['signatura_topografica']) ? trim((string) $input['signatura_topografica']) : null
        );

        JsonHelper::jsonResponse(['data' => $this->ejemplarService->updateEjemplar((int) $id, $request)]);
    }

    #[OA\Delete(
        path: "/ejemplares/{id}",
        description: "Elimina un ejemplar por su ID",
        summary: "Eliminar ejemplar",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del ejemplar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Ejemplar eliminado",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "message", type: "string")]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Ejemplar no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function deleteEjemplar(string $id): void
    {
        EjemplarRequestValidator::validateId($id);
        $this->ejemplarService->deleteEjemplar((int) $id);
        JsonHelper::jsonResponse(['message' => 'Ejemplar eliminado']);
    }

    #[OA\Get(
        path: "/articulos/{articuloId}/ejemplares",
        description: "Obtener todos los ejemplares de un artículo",
        summary: "Ejemplares de un artículo",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "articuloId",
                in: "path",
                description: "ID del artículo",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/EjemplarResponse")
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Artículo no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getByArticuloId(string $articuloId): void
    {
        EjemplarRequestValidator::validateId($articuloId, 'articulo_id');
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getByArticuloId((int) $articuloId)]);
    }

    #[OA\Get(
        path: "/articulos/{articuloId}/ejemplares/habilitados",
        description: "Obtener solo los ejemplares habilitados de un artículo",
        summary: "Ejemplares habilitados de un artículo",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "articuloId",
                in: "path",
                description: "ID del artículo",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/EjemplarResponse")
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getHabilitadosByArticuloId(string $articuloId): void
    {
        EjemplarRequestValidator::validateId($articuloId, 'articulo_id');
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getHabilitadosByArticuloId((int) $articuloId)]);
    }

    #[OA\Patch(
        path: "/ejemplares/{id}/habilitar",
        description: "Habilita un ejemplar deshabilitado",
        summary: "Habilitar ejemplar",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del ejemplar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Ejemplar habilitado",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "data", ref: "#/components/schemas/EjemplarResponse")]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Ejemplar no encontrado"),
            new OA\Response(response: 422, description: "El ejemplar ya está habilitado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function habilitar(string $id): void
    {
        EjemplarRequestValidator::validateId($id);
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->habilitarEjemplar((int) $id)]);
    }

    #[OA\Patch(
        path: "/ejemplares/{id}/deshabilitar",
        description: "Deshabilita un ejemplar habilitado",
        summary: "Deshabilitar ejemplar",
        security: [["bearerAuth" => []]],
        tags: ["Ejemplares"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del ejemplar",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Ejemplar deshabilitado",
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: "data", ref: "#/components/schemas/EjemplarResponse")]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Ejemplar no encontrado"),
            new OA\Response(response: 422, description: "El ejemplar ya está deshabilitado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function deshabilitar(string $id): void
    {
        EjemplarRequestValidator::validateId($id);
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->deshabilitarEjemplar((int) $id)]);
    }
}
