<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Catalogo\Articulos\Mappers\MateriaMapper;
use App\Catalogo\Articulos\Services\MateriaService;
use App\Catalogo\Articulos\Validators\MateriaRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use Exception;

class MateriaController
{
    public function __construct(private MateriaService $service)
    {
    }

    public function getAll(): void
    {
        try {
            $response = $this->service->getAll();
            http_response_code(200);
            echo json_encode([
                "error" => false,
                "data" => $response
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => true,
                "message" => $e->getMessage()
            ]);
        }
    }

    public function createMateria(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];

            MateriaRequestValidator::validate($input);

            $materiaRequest = MateriaMapper::fromArray($input);

            $materia = $this->service->createMateria($materiaRequest);

            http_response_code(201);
            echo json_encode([
                'error' => false,
                'data' => $materia
            ]);
        } catch (ValidationException $e) {
            http_response_code(422);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (BusinessValidationException $e) {
            http_response_code(400);
            echo json_encode([
                "message" => $e->getMessage(),
                "field" => $e->getField()
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

    public function updateMateria($id): void
    {
        try {
            if ((int)$id < 1) {
                http_response_code(422);
                echo json_encode([
                    "error" => true,
                    "message" => "ID inválido. El ID debe ser un entero positivo mayor que 0."
                ]);
                return;
            }

            $input = json_decode(file_get_contents("php://input"), true) ?? [];

            MateriaRequestValidator::validate($input);

            $request = MateriaMapper::fromArray($input);

            $materia = $this->service->updateMateria((int)$id, $request);

            http_response_code(200);
            echo json_encode([
                "error" => false,
                "data" => $materia
            ]);
        } catch (ValidationException $e) {
            http_response_code(422);
            echo json_encode([
                "error" => true,
                "message" => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
        } catch (MateriaAlreadyExistsException $e) {
            http_response_code(409);
            echo json_encode([
                "error" => true,
                "message" => $e->getMessage()
            ]);
        } catch (MateriaNotFoundException $e) {
            http_response_code(404);
            echo json_encode([
                "error" => true,
                "message" => $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => true,
                "message" => "Error interno del servidor"
            ]);
            error_log($e->getMessage());
        }
    }
}
