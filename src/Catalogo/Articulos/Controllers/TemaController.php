<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\TemaAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TemaNotFoundException;
use App\Catalogo\Articulos\Mappers\TemaMapper;
use App\Catalogo\Articulos\Services\TemaService;
use App\Catalogo\Articulos\Validators\TemaRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;

class TemaController
{
    private const ALLOWED_PARAMS = ["titulo", "order"];
    public function __construct(private TemaService $service)
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
            $temas = $this->service->getAll();
            JsonHelper::jsonResponse($temas, 200);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getAll");
        }
    }

    public function getById(string $id): void
    {
        try {
            TemaRequestValidator::validateId($id);

            $tema = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($tema, 200);
        } catch (TemaNotFoundException $e) {
            $this->temaNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getById");
        }
    }

    private function getByParams(array $params): void
    {
        try {
            TemaRequestValidator::validateParams($params);

            $temas = $this->service->getByParams($params);
            JsonHelper::jsonResponse($temas, 200);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getByParams");
        }
    }

    public function createTema(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            TemaRequestValidator::validateInput($input);

            $request = TemaMapper::fromArray($input);

            $tema = $this->service->createTema($request);
            JsonHelper::jsonResponse($tema, 201);
        } catch (TemaAlreadyExistsException $e) {
            $this->temaExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "createTema");
        }
    }

    public function updateTema(string $id): void
    {
        try {
            TemaRequestValidator::validateId($id);
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            TemaRequestValidator::validateInput($input);

            $request = TemaMapper::fromArray($input);

            $temaActualizado = $this->service->updateTema((int) $id, $request);
            JsonHelper::jsonResponse($temaActualizado, 200);
        } catch (TemaNotFoundException $e) {
            $this->temaNotFoundResponse($e);
        } catch (TemaAlreadyExistsException $e) {
            $this->temaExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "updateTema");
        }
    }

    public function deleteTema(string $id): void
    {
        try {
            TemaRequestValidator::validateId($id);

            $this->service->deleteTema((int) $id);
            JsonHelper::jsonResponse(null, 204);
        } catch (TemaNotFoundException $e) {
            $this->temaNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteTema");
        }
    }

    private function temaNotFoundResponse(TemaNotFoundException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 404);
        return;
    }

    private function temaExistsResponse(TemaAlreadyExistsException $e): void
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
        error_log("[TemaController::{$method}] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
    }
}
