<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Exceptions\TipoDocumentoAlreadyExistsException;
use App\Catalogo\Articulos\Exceptions\TipoDocumentoNotFoundException;
use App\Catalogo\Articulos\Mappers\TipoDocumentoMapper;
use App\Catalogo\Articulos\Services\TipoDocumentoService;
use App\Catalogo\Articulos\Validators\TipoDocumentoRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;

class TipoDocumentoController
{
    private const ALLOWED_PARAMS = ["codigo", "descripcion", "detalle", "renovable", "order"];
    public function __construct(private TipoDocumentoService $service)
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
            $tipoDocs = $this->service->getAll();
            JsonHelper::jsonResponse($tipoDocs, 200);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getAll");
        }
    }

    public function getById(string $id): void
    {
        try {
            TipoDocumentoRequestValidator::validateId($id);

            $tipoDoc = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($tipoDoc, 200);
        } catch (TipoDocumentoNotFoundException $e) {
            $this->tipoDocumentoNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getById");
        }
    }

    private function getByParams(array $params): void {
        try {
            TipoDocumentoRequestValidator::validateParams($params);

            $tipoDocs = $this->service->getByParams($params);
            JsonHelper::jsonResponse($tipoDocs, 200);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "getByParams");
        }
    }
    
    public function createTipoDocumento(): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            TipoDocumentoRequestValidator::validateInputCreate($input);

            $request = TipoDocumentoMapper::fromArrayToCreate($input);

            $tipoDoc = $this->service->createTipoDocumento($request);
            JsonHelper::jsonResponse($tipoDoc, 201);
        } catch (TipoDocumentoAlreadyExistsException $e) {
            $this->tipoDocumentoExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "createTipoDocumento");
        }
    }

    public function updateTipoDocumento(string $id): void {
        try {
            TipoDocumentoRequestValidator::validateId($id);
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            TipoDocumentoRequestValidator::validateInputUpdate($input);

            $request = TipoDocumentoMapper::fromArrayToUpdate($input);
            $tipoDocActualizado = $this->service->updateTipoDocumento((int) $id, $request);
            JsonHelper::jsonResponse($tipoDocActualizado, 200);
        } catch (TipoDocumentoNotFoundException $e) {
            $this->tipoDocumentoNotFoundResponse($e);
        } catch (TipoDocumentoAlreadyExistsException $e) {
            $this->tipoDocumentoExistsResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (BusinessValidationException $e) {
            $this->businessValidationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "updateTipoDocumento");
        }
    }

    public function deleteTipoDocumento(string $id): void {
        try {
            TipoDocumentoRequestValidator::validateId($id);
            
            $this->service->deleteTipoDocumento((int) $id);
            http_response_code(204);
        } catch (TipoDocumentoNotFoundException $e) {
            $this->tipoDocumentoNotFoundResponse($e);
        } catch (ValidationException $e) {
            $this->validationResponse($e);
        } catch (\Exception $e) {
            $this->exceptionResponse($e, "deleteTipoDocumento");
        }
    }

    private function tipoDocumentoNotFoundResponse(TipoDocumentoNotFoundException $e): void
    {
        JsonHelper::jsonResponse([
            "message" => $e->getMessage()
        ], 404);
    }

    private function tipoDocumentoExistsResponse(TipoDocumentoAlreadyExistsException $e): void
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
            "message" => $e->getMessage(),
            "field" => $e->getField()
        ], 422);
    }

    private function exceptionResponse(\Exception $e, string $method): void
    {
        JsonHelper::jsonResponse([
            "message" => "Error interno del servidor"
        ], 500);
        error_log("[TipoDocumentoController::{$method}] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
    }
}