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
use App\Shared\Http\JsonHelper;
use Exception;

class MateriaController
{
    private const ALLOWED_PARAMS = ["titulo", "order"];
    public function __construct(private MateriaService $service)
    {
    }

    public function getAll(): void
    {
        $params = array_intersect_key($_GET, array_flip(self::ALLOWED_PARAMS));
        $params = array_filter($params, fn($value) => $value !== '');
        
        if (!empty($params)) {
            $this->getByParams($params);
            return;
        }
        try {
            $response = $this->service->getAll();
            JsonHelper::jsonResponse($response, 200);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[MateriaController::getAll] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    public function getById($id): void
    {
        try {
            MateriaRequestValidator::validateId($id);

            $response = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($response, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                "message" => $e->getMessage(),
                "errors" => $e->getErrors()
            ], 400);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse(["message" => $e->getMessage()], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(["message" => "Error interno del servidor"], 500);
            error_log("[MateriaController::getById] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    public function getByParams(array $params): void
    {
        try {
            MateriaRequestValidator::validateParams($params);

            $response = $this->service->getByParams($params);
            JsonHelper::jsonResponse($response, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                "message" => $e->getMessage(),
                "errors" => $e->getErrors()
            ], 400);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(["message" => "Error interno del servidor"], 500);
            error_log("[MateriaController::getByTitulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    public function createMateria(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];

            MateriaRequestValidator::validate($input);

            $materiaRequest = MateriaMapper::fromArray($input);

            $materia = $this->service->createMateria($materiaRequest);

            JsonHelper::jsonResponse($materia, 201);
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
        } catch (MateriaAlreadyExistsException $e) {
            JsonHelper::jsonResponse(['message' => 'La materia ya existe'], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[MateriaController::createMateria] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    public function updateMateria($id): void
    {
        try {
            MateriaRequestValidator::validateId($id);

            $input = json_decode(file_get_contents("php://input"), true) ?? [];

            MateriaRequestValidator::validate($input);

            $request = MateriaMapper::fromArray($input);

            $materia = $this->service->updateMateria((int) $id, $request);

            JsonHelper::jsonResponse($materia, 200);
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
        } catch (MateriaAlreadyExistsException $e) {
            JsonHelper::jsonResponse(['message' => 'La materia ya existe'], 409);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse(["message" => $e->getMessage()], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[MateriaController::updateMateria] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    public function deleteMateria($id): void
    {
        try {
            MateriaRequestValidator::validateId($id);

            $this->service->deleteMateria((int) $id);

            http_response_code(204);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse(['message' => $e->getMessage()], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[MateriaController::deleteMateria] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }
}
