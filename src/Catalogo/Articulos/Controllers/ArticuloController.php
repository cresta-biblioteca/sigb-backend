<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyInArticuloException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Catalogo\Articulos\Validators\TemaRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use Exception;
use JsonException;
use OpenApi\Attributes as OA;

class ArticuloController
{
    public function __construct(private ArticuloService $service)
    {
    }

    /**
     * GET /articulos
     */
    public function getAll(): void
    {
        try {
            $articulos = $this->service->getAll();
            JsonHelper::jsonResponse($articulos, 200);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::getAll] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * GET /articulos/{id}
     */
    public function getById($id): void
    {
        try {
            ArticuloRequestValidator::validateId((int) $id);

            $articulo = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($articulo, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::getById] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * PATCH /articulos/{id}
     */
    public function patchArticulo($id): void
    {
        try {
            ArticuloRequestValidator::validateId((int) $id);

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            ArticuloRequestValidator::validatePatch($input);

            $articulo = $this->service->patchArticulo((int) $id, $input);
            JsonHelper::jsonResponse($articulo, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (BusinessValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
                'field' => $e->getField()
            ], 422);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (JsonException) {
            JsonHelper::jsonResponse([
                'message' => 'JSON inválido',
            ], 400);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::patchArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * DELETE /articulos/{id}
     */
    public function deleteArticulo($id): void
    {
        try {
            ArticuloRequestValidator::validateId((int) $id);

            $this->service->deleteArticulo((int) $id);

            http_response_code(204);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (BusinessValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
                'field' => $e->getField()
            ], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::deleteArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * POST /articulos/{idArticulo}/temas/{idTema}
     */
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
            ArticuloRequestValidator::validateId((int) $idArticulo, 'idArticulo');
            TemaRequestValidator::validateId((string) $idTema);

            $this->service->addTemaToArticulo((int) $idArticulo, (int) $idTema);

            JsonHelper::jsonResponse([
                'message' => 'El tema ha sido agregado al artículo'
            ], 201);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            if (array_key_exists('id', $errors) && !array_key_exists('idArticulo', $errors)) {
                $errors['idTema'] = $errors['id'];
                unset($errors['id']);
            }

            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $errors
            ], 400);
        } catch (ArticuloNotFoundException | TemaNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (TemaAlreadyInArticuloException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::addTemaToArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * GET /articulos/{idArticulo}/temas
     */
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
            ArticuloRequestValidator::validateId((int) $idArticulo, 'idArticulo');

            $temas = $this->service->getTemaTitlesByArticuloId((int) $idArticulo);

            JsonHelper::jsonResponse($temas, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::getTemaTitlesByArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * DELETE /articulos/{idArticulo}/temas/{idTema}
     */
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
            ArticuloRequestValidator::validateId((int) $idArticulo, 'idArticulo');
            TemaRequestValidator::validateId((string) $idTema);

            $this->service->deleteTemaFromArticulo((int) $idArticulo, (int) $idTema);

            JsonHelper::jsonResponse([
                'message' => 'El tema ha sido eliminado del artículo'
            ], 200);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            if (array_key_exists('id', $errors) && !array_key_exists('idArticulo', $errors)) {
                $errors['idTema'] = $errors['id'];
                unset($errors['id']);
            }

            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $errors
            ], 400);
        } catch (ArticuloNotFoundException | TemaNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (TemaAlreadyEliminatedException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::deleteTemaFromArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }
}
