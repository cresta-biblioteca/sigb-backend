<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Services\LibroService;

class LibroController
{
    public function __construct(
        private LibroService $service
    ) {
    }

    /**
     * GET /libros
     * Soporta filtros opcionales por query params
     */
    public function listAll(): void
    {
        $filters = [];

        if (!empty($_GET['autor'])) {
            $filters['autor'] = $_GET['autor'];
        }

        if (!empty($_GET['isbn'])) {
            $filters['isbn'] = $_GET['isbn'];
        }

        if (!empty($_GET['cdu'])) {
            $filters['cdu'] = (int) $_GET['cdu'];
        }

        $libros = $this->service->listAll($filters);

        $response = array_map(
            fn($libroDto) => $libroDto->toArray(),
            $libros
        );

        $this->json($response);
    }

    /**
     * GET /libros/{id}
     */
    public function showById(int $id): void
    {
        try {
            $libro = $this->service->getById($id);

            $this->json(
                $libro->toArray()
            );
        } catch (LibroNotFoundException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * POST /libros
     * Crea un nuevo libro
     */
    public function create(): void
    {
        try {
            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $requestDto = LibroRequest::fromArray($data);

            $libro = $this->service->create($requestDto);

            $this->json(
                $libro->toArray(),
                201
            );
        } catch (LibroAlreadyExistsException $e) {
            $this->json(['error' => $e->getMessage()], 409);
        } catch (\JsonException) {
            $this->json(['error' => 'JSON inválido'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Error interno'], 500);
        }
    }

    /**
     * PUT /libros/{id}
     * Actualiza un libro existente
     */
    public function update(int $id): void
    {
        try {
            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $requestDto = LibroRequest::fromArray($data);

            $libro = $this->service->update($id, $requestDto);

            $this->json(
                $libro->toArray()
            );

        } catch (LibroNotFoundException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        } catch (\JsonException) {
            $this->json(['error' => 'JSON inválido'], 400);
        } catch (\Throwable) {
            $this->json(['error' => 'Error interno'], 500);
        }
    }

    /**
     * DELETE /libros/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->delete($id);

            $this->json(['message' => 'Libro eliminado']);
        } catch (LibroNotFoundException $e) {
            $this->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Respuesta JSON centralizada
     */
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }
}