<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Mappers\LibroMapper;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;

class LibroController
{
    public function __construct(
        private LibroRepository $repository
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

        $libros = !empty($filters)
            ? $this->repository->search($filters)
            : $this->repository->findAll();

        $response = array_map(
            fn(Libro $libro) => LibroMapper::toResponse($libro)->toArray(),
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
            $libro = $this->repository->findByIdOrFail($id);

            $this->json(
                LibroMapper::toResponse($libro)->toArray()
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

            $libro = LibroMapper::requestToEntity($requestDto);

            $this->repository->save($libro);

            $this->json(
                LibroMapper::toResponse($libro)->toArray(),
                201
            );

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
            $this->repository->findByIdOrFail($id);

            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $requestDto = LibroRequest::fromArray($data);

            $libro = LibroMapper::requestToEntity($requestDto);


            $this->repository->update($libro);

            $this->json(
                LibroMapper::toResponse($libro)->toArray()
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
            $this->repository->findByIdOrFail($id);

            $this->repository->delete($id);

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