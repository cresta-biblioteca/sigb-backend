<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Catalogo\Articulos\Validators\TemaRequestValidator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use OpenApi\Attributes as OA;

class ArticuloController
{
    public function __construct(private ArticuloService $service)
    {
    }

    #[OA\Get(
        path: "/articulos",
        description: "Listado de todos los artículos registrados en el sistema",
        summary: "Listar artículos",
        security: [["bearerAuth" => []]],
        tags: ["Articulos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/ArticuloResponse")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getAll(): void
    {
        $articulos = $this->service->getAll();
        JsonHelper::jsonResponse($articulos, 200);
    }

    #[OA\Get(
        path: "/articulos/{id}",
        description: "Obtener la información de un artículo por su ID",
        summary: "Obtener artículo por ID",
        security: [["bearerAuth" => []]],
        tags: ["Articulos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del artículo",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Artículo obtenido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ArticuloResponse")
            ),
            new OA\Response(response: 400, description: "ID inválido"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Artículo no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function getById($id): void
    {
        ArticuloRequestValidator::validateId($id);
        $articulo = $this->service->getById((int) $id);
        JsonHelper::jsonResponse($articulo, 200);
    }

    #[OA\Patch(
        path: "/articulos/{id}",
        description: "Actualiza parcialmente los campos de un artículo. Solo se actualizan los campos enviados.",
        summary: "Actualizar artículo",
        security: [["bearerAuth" => []]],
        tags: ["Articulos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del artículo",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "titulo", type: "string", nullable: true, example: "Algorithms"),
                    new OA\Property(property: "anio_publicacion", type: "integer", nullable: true, example: 2011),
                    new OA\Property(property: "tipo_documento_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "idioma", type: "string", nullable: true, example: "en"),
                    new OA\Property(property: "descripcion", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Artículo actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ArticuloResponse")
            ),
            new OA\Response(response: 400, description: "Datos de entrada inválidos"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Artículo no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function patchArticulo($id): void
    {
        ArticuloRequestValidator::validateId($id);
        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        ArticuloRequestValidator::validatePatch($input);
        $articulo = $this->service->patchArticulo((int) $id, $input);
        JsonHelper::jsonResponse($articulo, 200);
    }

    #[OA\Delete(
        path: "/articulos/{id}",
        description: "Elimina un artículo por su ID",
        summary: "Eliminar artículo",
        security: [["bearerAuth" => []]],
        tags: ["Articulos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del artículo",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Artículo eliminado exitosamente"),
            new OA\Response(response: 400, description: "ID inválido"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Artículo no encontrado"),
            new OA\Response(response: 500, description: "Error interno del servidor"),
        ]
    )]
    public function deleteArticulo($id): void
    {
        ArticuloRequestValidator::validateId($id);
        $this->service->deleteArticulo((int) $id);
        http_response_code(204);
    }


    #[OA\Post(
        path: '/articulos/{idArticulo}/temas/{idTema}',
        description: 'Asocia un tema existente a un artículo existente',
        summary: 'Agregar tema a artículo',
        security: [['bearerAuth' => []]],
        tags: ['Articulos'],
        parameters: [
            new OA\Parameter(
                name: 'idArticulo',
                in: 'path',
                required: true,
                description: 'ID del artículo',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'idTema',
                in: 'path',
                required: true,
                description: 'ID del tema',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tema agregado al artículo',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Datos de entrada no válidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Artículo o tema no encontrado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Tema ya asociado al artículo',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function addTemaToArticulo($idArticulo, $idTema): void
    {
        try {
            ArticuloRequestValidator::validateId($idArticulo);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            if (array_key_exists('id', $errors)) {
                $errors['idArticulo'] = $errors['id'];
                unset($errors['id']);
            }
            throw new ValidationException($errors);
        }

        TemaRequestValidator::validateId($idTema);

        $this->service->addTemaToArticulo((int) $idArticulo, (int) $idTema);

        JsonHelper::jsonResponse(['message' => 'El tema ha sido agregado al artículo'], 201);
    }

    #[OA\Get(
        path: '/articulos/{idArticulo}/temas',
        description: 'Obtiene los títulos de los temas asociados a un artículo',
        summary: 'Listar temas de artículo',
        security: [['bearerAuth' => []]],
        tags: ['Articulos'],
        parameters: [
            new OA\Parameter(
                name: 'idArticulo',
                in: 'path',
                required: true,
                description: 'ID del artículo',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de títulos de temas',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Datos de entrada no válidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Artículo no encontrado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function getTemaTitlesByArticulo($idArticulo): void
    {
        try {
            ArticuloRequestValidator::validateId($idArticulo);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            if (array_key_exists('id', $errors)) {
                $errors['idArticulo'] = $errors['id'];
                unset($errors['id']);
            }
            throw new ValidationException($errors);
        }

        $temas = $this->service->getTemaTitlesByArticuloId((int) $idArticulo);
        JsonHelper::jsonResponse($temas, 200);
    }

    #[OA\Delete(
        path: '/articulos/{idArticulo}/temas/{idTema}',
        description: 'Desasocia un tema de un artículo',
        summary: 'Eliminar tema de artículo',
        security: [['bearerAuth' => []]],
        tags: ['Articulos'],
        parameters: [
            new OA\Parameter(
                name: 'idArticulo',
                in: 'path',
                required: true,
                description: 'ID del artículo',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'idTema',
                in: 'path',
                required: true,
                description: 'ID del tema',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tema eliminado del artículo',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Datos de entrada no válidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Artículo o tema no encontrado',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'El tema no estaba asociado al artículo',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function deleteTemaFromArticulo($idArticulo, $idTema): void
    {
        try {
            ArticuloRequestValidator::validateId($idArticulo);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            if (array_key_exists('id', $errors)) {
                $errors['idArticulo'] = $errors['id'];
                unset($errors['id']);
            }
            throw new ValidationException($errors);
        }

        TemaRequestValidator::validateId($idTema);

        $this->service->deleteTemaFromArticulo((int) $idArticulo, (int) $idTema);

        JsonHelper::jsonResponse(['message' => 'El tema ha sido eliminado del artículo'], 200);
    }
}
