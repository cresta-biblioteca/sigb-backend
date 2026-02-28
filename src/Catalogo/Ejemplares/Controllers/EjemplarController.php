<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Controllers;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Services\EjemplarService;
use App\Catalogo\Ejemplares\Validators\EjemplarRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\EntityAlreadyExistsException;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use Exception;
use JsonException;

class EjemplarController
{
	public function __construct(private EjemplarService $ejemplarService)
	{
	}

	/**
	 * GET /ejemplares
	 * Filtros opcionales: codigo_barras, articulo_id, habilitado
	 */
	public function getAll(): void
	{
		try {
			if (isset($_GET['codigo_barras']) && $_GET['codigo_barras'] !== '') {
				$codigoBarras = trim((string) $_GET['codigo_barras']);

				if (!EjemplarRequestValidator::validateCodigoBarras($codigoBarras)) {
					throw ValidationException::forField(
						'codigo_barras',
						'El campo codigo_barras debe contener solo dígitos (máximo 13)'
					);
				}

				$ejemplar = $this->ejemplarService->getByCodigoBarras($codigoBarras);
				$response = $ejemplar === null ? [] : [$ejemplar];

				JsonHelper::jsonResponse([
					'error' => false,
					'data' => $response,
				]);
				return;
			}

			if (isset($_GET['articulo_id']) && $_GET['articulo_id'] !== '') {
				$articuloId = (int) $_GET['articulo_id'];
				EjemplarRequestValidator::validateId($articuloId, 'articulo_id');

				if (isset($_GET['habilitado']) && filter_var($_GET['habilitado'], FILTER_VALIDATE_BOOLEAN)) {
					$this->getHabilitadosByArticuloId($articuloId);
					return;
				}

				$this->getByArticuloId($articuloId);
				return;
			}

			if (isset($_GET['habilitado']) && $_GET['habilitado'] !== '') {
				$habilitado = filter_var($_GET['habilitado'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

				if (!is_bool($habilitado)) {
					throw ValidationException::forField('habilitado', 'El campo habilitado debe ser booleano');
				}

				$response = $this->ejemplarService->getByHabilitado($habilitado);

				JsonHelper::jsonResponse([
					'error' => false,
					'data' => $response,
				]);
				return;
			}

			$response = $this->ejemplarService->getAll();

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $response,
			]);
		} catch (ValidationException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
				'errors' => $e->getErrors(),
			], 422);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::getAll] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * GET /ejemplares/{id}
	 */
	public function getById(int $id): void
	{
		try {
			$ejemplar = $this->ejemplarService->getById($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar,
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::getById] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * POST /ejemplares
	 */
	public function createEjemplar(): void
	{
		try {
			$input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
			EjemplarRequestValidator::validate($input);
			$request = new EjemplarRequest(
				(int) $input['articulo_id'],
				trim((string) $input['codigo_barras']),
				(bool) $input['habilitado']
			);
			$ejemplar = $this->ejemplarService->createEjemplar($request);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar,
			], 201);
		} catch (ValidationException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
				'errors' => $e->getErrors(),
			], 422);
		} catch (BusinessValidationException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
				'errors' => [
					$e->getField() => [$e->getMessage()],
				],
			], 400);
		} catch (EntityAlreadyExistsException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 409);
		} catch (JsonException) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => 'JSON inválido',
			], 400);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::createEjemplar] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * PUT /ejemplares/{id}
	 */
	public function updateEjemplar(int $id): void
	{
		try {
			if ($id < 1) {
				JsonHelper::jsonResponse([
					'error' => true,
					'message' => 'ID inválido. El ID debe ser un entero positivo mayor que 0.',
				], 422);
				return;
			}

			$input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
			EjemplarRequestValidator::validate($input);

			$request = new EjemplarRequest(
				(int) $input['articulo_id'],
				trim((string) $input['codigo_barras']),
				(bool) $input['habilitado']
			);
			$ejemplar = $this->ejemplarService->updateEjemplar($id, $request);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar,
			]);
		} catch (ValidationException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
				'errors' => $e->getErrors(),
			], 422);
		} catch (BusinessValidationException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
				'errors' => [
					$e->getField() => [$e->getMessage()],
				],
			], 400);
		} catch (EntityAlreadyExistsException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 409);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (JsonException) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => 'JSON inválido',
			], 400);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::updateEjemplar] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * DELETE /ejemplares/{id}
	 */
	public function deleteEjemplar(int $id): void
	{
		try {
			$this->ejemplarService->deleteEjemplar($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'message' => 'Ejemplar eliminado',
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::deleteEjemplar] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * GET /articulos/{articuloId}/ejemplares
	 */
	public function getByArticuloId(int $articuloId): void
	{
		try {
			$ejemplares = $this->ejemplarService->getByArticuloId($articuloId);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplares,
			]);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::getByArticuloId] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * GET /articulos/{articuloId}/ejemplares/habilitados
	 */
	public function getHabilitadosByArticuloId(int $articuloId): void
	{
		try {
			$ejemplares = $this->ejemplarService->getHabilitadosByArticuloId($articuloId);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplares,
			]);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::getHabilitadosByArticuloId] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * PATCH /ejemplares/{id}/habilitar
	 */
	public function habilitar(int $id): void
	{
		try {
			$ejemplar = $this->ejemplarService->habilitarEjemplar($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar,
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::habilitar] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

	/**
	 * PATCH /ejemplares/{id}/deshabilitar
	 */
	public function deshabilitar(int $id): void
	{
		try {
			$ejemplar = $this->ejemplarService->deshabilitarEjemplar($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar,
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Exception $e) {
			JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
			error_log("[EjemplarController::deshabilitar] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
		}
	}

}
