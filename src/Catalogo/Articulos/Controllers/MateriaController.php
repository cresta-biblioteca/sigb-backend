<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyExistsException;
use App\Catalogo\Articulos\Services\MateriaService;
use App\Shared\Exceptions\ValidationException;
use Exception;

class MateriaController
{
    public function __construct(private MateriaService $service)
    {
    }

    public function getAll() {
        try {
            $response = $this->service->getAll();
            http_response_code(200);
            echo json_encode([
                "error" => "false",
                "data" => $response
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "true",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function createMateria(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true);

            if ($input === null) {
                http_response_code(400);
                echo json_encode([
                    'error' => true,
                    'message' => 'Formato de JSON invalido'
                ]);
                return;
            }

            $materia = $this->service->createMateria($input);

            http_response_code(201);
            echo json_encode([
                'error' => false,
                'data' => $materia->toArray()
            ]);
        } catch (ValidationException $e) {
            http_response_code(422);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (MateriaAlreadyExistsException $e) {
            http_response_code(409);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error interno del servidor'
            ]);
            // Log del error real para debugging
            error_log($e->getMessage());
        }
    }
}