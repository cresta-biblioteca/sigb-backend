<?php

declare(strict_types=1);

namespace App\Lectores\Controllers;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyInCarreraException;
use App\Catalogo\Articulos\Exceptions\MateriaNotFoundException;
use App\Lectores\Exceptions\CarreraAlreadyExistsException;
use App\Lectores\Exceptions\CarreraNotFoundException;
use App\Lectores\Mappers\CarreraMapper;
use App\Lectores\Services\CarreraService;
use App\Lectores\Validators\CarreraRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;

class CarreraController
{
    public function __construct(private CarreraService $service)
    {
    }

    private const SEARCH_PARAMS = ['cod', 'nombre'];

    public function getAll(): void
    {
        try {
            $params = array_intersect_key($_GET, array_flip(self::SEARCH_PARAMS));
            $params = array_filter($params, fn($value) => $value !== '');

            if (!empty($params)) {
                $this->getByParams($params);
                return;
            }
            $carreras = $this->service->getAll();
            JsonHelper::jsonResponse($carreras, 200);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getAll");
        }
    }

    private function getByParams(array $params): void
    {
        try {
            CarreraRequestValidator::validateParams($params);

            $carreras = $this->service->getByParams($params);
            JsonHelper::jsonResponse($carreras, 200);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getByParams");
        }
    }

    public function getById(string $id): void
    {
        try {
            CarreraRequestValidator::validateId($id);

            $carrera = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($carrera, 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getById");
        }
    }

    public function getMateriasByCarrera($idCarrera): void
    {
        try {
            CarreraRequestValidator::validateId($idCarrera);

            $materias = $this->service->getMateriasByCarrera((int) $idCarrera);

            JsonHelper::jsonResponse(["carreraId" => $idCarrera, "materias" => $materias], 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getMateriasByCarrera");
        }
    }

    public function createCarrera(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            CarreraRequestValidator::validateInput($input);

            $request = CarreraMapper::fromArrayToCreate($input);

            $carrera = $this->service->createCarrera($request);

            JsonHelper::jsonResponse($carrera, 201);
        } catch (CarreraAlreadyExistsException $e) {
            $this->carreraExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "createCarrera");
        }
    }

    public function updateCarrera(string $id): void
    {
        try {
            CarreraRequestValidator::validateId($id);

            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            CarreraRequestValidator::validateUpdateInput($input);

            $carrera = CarreraMapper::fromArrayToUpdate($input);

            $carreraActualizada = $this->service->updateCarrera((int) $id, $carrera);
            JsonHelper::jsonResponse($carreraActualizada, 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (CarreraAlreadyExistsException $e) {
            $this->carreraExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "updateCarrera");
        }
    }

    public function deleteCarrera(string $id): void
    {
        try {
            CarreraRequestValidator::validateId($id);

            $this->service->deleteCarrera((int) $id);
            http_response_code(204);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteCarrera");
        }
    }

    public function addMateriaToCarrera(string $idCarrera, string $idMateria): void
    {
        try {
            CarreraRequestValidator::validateId($idCarrera);
            CarreraRequestValidator::validateId($idMateria);

            $this->service->addMateriaToCarrera((int) $idCarrera, (int) $idMateria);
            JsonHelper::jsonResponse(["message" => "La materia ha sido agregada!"], 201);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse([
                "message" => $e->getMessage()
            ], 404);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (MateriaAlreadyInCarreraException $e) {
            $this->conflictsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "addMateria");
        }
    }

    public function deleteMateriaFromCarrera(string $idCarrera, string $idMateria): void
    {
        try {
            CarreraRequestValidator::validateId($idCarrera);
            CarreraRequestValidator::validateId($idMateria);

            $this->service->deleteMateriaFromCarrera((int) $idCarrera, (int) $idMateria);
            JsonHelper::jsonResponse([
                "message" => "Materia eliminada correctamente!"
            ], 200);
        } catch (CarreraNotFoundException $e) {
            $this->carreraNotFoundResponse($e);
        } catch (MateriaNotFoundException $e) {
            JsonHelper::jsonResponse([
                "message" => $e->getMessage()
            ], 404);
        } catch (MateriaAlreadyEliminatedException $e) {
            $this->conflictsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteMateriaFromCarrera");
        }
    }

    private function carreraNotFoundResponse(CarreraNotFoundException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 404);
        return;
    }

    private function carreraExistsResponse(CarreraAlreadyExistsException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 409);
    }

    private function conflictsResponse(BusinessValidationException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 409);
    }

    private function validationResponse(ValidationException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage(),
            "errors" => $e->getErrors()
        ], 400);
    }
    private function businessValidationResponse(BusinessValidationException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 422);
    }

    private function exceptionResponse(\Exception $e, string $method): void
    {
        JsonHelper::jsonResponse([
            "message" => "Error interno del servidor"
        ], 500);
        error_log("[CarreraController::{$method}] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
    }
}
