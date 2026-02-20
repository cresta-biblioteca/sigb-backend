<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use Exception;

class ArticuloController
{
    public function __construct(private ArticuloService $service)
    {
    }

    /**
     * GET /articulos
     * Soporta filtros opcionales por query params
     */
    public function getAll(): void
    {
        try {
            $filters = [];

            if (!empty($_GET['titulo'])) {
                $filters['titulo'] = (string) $_GET['titulo'];
            }

            if (!empty($_GET['tipo_documento_id'])) {
                $filters['tipo_documento_id'] = (int) $_GET['tipo_documento_id'];
            }

            if (!empty($_GET['idioma'])) {
                $filters['idioma'] = (string) $_GET['idioma'];
            }

            if (!empty($_GET['anio_publicacion'])) {
                $filters['anio_publicacion'] = (int) $_GET['anio_publicacion'];
            }

            $articulos = !empty($filters)
                ? $this->service->search($filters)
                : $this->service->getAll();

            $response = array_map(
                fn($articuloDto) => $articuloDto->toArray(),
                $articulos
            );

            http_response_code(200);
            echo json_encode([
                'error' => false,
                'data' => $response,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error interno del servidor',
            ]);
            error_log($e->getMessage());
        }
    }

    /**
     * GET /articulos/{id}
     */
    public function showById(int $id): void
    {
        try {
            $articulo = $this->service->getById($id);

            http_response_code(200);
            echo json_encode([
                'error' => false,
                'data' => $articulo->toArray(),
            ]);
        } catch (ArticuloNotFoundException $e) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error interno del servidor',
            ]);
            error_log($e->getMessage());
        }
    }

    /**
     * POST /articulos
     */
    public function create(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            $request = ArticuloMapper::fromArray($input);

            $articulo = $this->service->create($request);

            http_response_code(201);
            echo json_encode([
                'error' => false,
                'data' => $articulo->toArray(),
            ]);
        } catch (ValidationException $e) {
            http_response_code(422);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);
        } catch (BusinessValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'message' => $e->getMessage(),
                'field' => $e->getField(),
            ]);
        } catch (\JsonException) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'JSON inválido',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error interno del servidor',
            ]);
            error_log($e->getMessage());
        }
    }

    /**
     * PUT /articulos/{id}
     */
    public function update(int $id): void
    {
        try {
            if ($id < 1) {
                http_response_code(422);
                echo json_encode([
                    'error' => true,
                    'message' => 'ID inválido. El ID debe ser un entero positivo mayor que 0.',
                ]);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            $request = ArticuloMapper::fromArray($input);

            $articulo = $this->service->update($id, $request);

            http_response_code(200);
            echo json_encode([
                'error' => false,
                'data' => $articulo->toArray(),
            ]);
        } catch (ValidationException $e) {
            http_response_code(422);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);
        } catch (BusinessValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'message' => $e->getMessage(),
                'field' => $e->getField(),
            ]);
        } catch (ArticuloNotFoundException $e) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (\JsonException) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'JSON inválido',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error interno del servidor',
            ]);
            error_log($e->getMessage());
        }
    }

    /**
     * DELETE /articulos/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->delete($id);

            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'Articulo eliminado',
            ]);
        } catch (ArticuloNotFoundException $e) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error interno del servidor',
            ]);
            error_log($e->getMessage());
        }
    }
}
