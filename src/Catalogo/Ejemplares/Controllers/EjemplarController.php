<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Controllers;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarCatalogFilterRequest;
use App\Catalogo\Ejemplares\Mappers\EjemplarMapper;
use App\Catalogo\Ejemplares\Services\EjemplarService;
use App\Catalogo\Ejemplares\Validators\EjemplarCatalogQueryValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\EntityAlreadyExistsException;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use JsonException;
use Throwable;

class EjemplarController
{
	public function __construct(private EjemplarService $service)
	{
	}

	/**
	 * GET /ejemplares
	 * Filtros opcionales: codigo_barras, articulo_id, habilitado
	 */
	public function getAll(): void
	{
		try {
			/** @var EjemplarCatalogFilterRequest $filterRequest */
			$filterRequest = EjemplarCatalogQueryValidator::fromQuery($_GET);

			$ejemplares = !empty($filterRequest->filters)
				? $this->service->search($filterRequest->filters)
				: $this->service->getAll();

			$response = array_map(
				fn($ejemplarDto) => $ejemplarDto->toArray(),
				$ejemplares
			);

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
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * GET /ejemplares/{id}
	 */
	public function showById(int $id): void
	{
		try {
			$ejemplar = $this->service->getById($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar->toArray(),
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * POST /ejemplares
	 */
	public function create(): void
	{
		try {
			$input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

			$request = EjemplarMapper::fromArray($input);
			$ejemplar = $this->service->create($request);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar->toArray(),
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
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * PUT /ejemplares/{id}
	 */
	public function update(int $id): void
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

			$request = EjemplarMapper::fromArray($input);
			$ejemplar = $this->service->update($id, $request);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar->toArray(),
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
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * DELETE /ejemplares/{id}
	 */
	public function destroy(int $id): void
	{
		try {
			$this->service->delete($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'message' => 'Ejemplar eliminado',
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * GET /articulos/{articuloId}/ejemplares
	 */
	public function getByArticuloId(int $articuloId): void
	{
		try {
			$ejemplares = $this->service->getByArticuloId($articuloId);

			$response = array_map(
				fn($ejemplarDto) => $ejemplarDto->toArray(),
				$ejemplares
			);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $response,
			]);
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * GET /articulos/{articuloId}/ejemplares/habilitados
	 */
	public function getHabilitadosByArticuloId(int $articuloId): void
	{
		try {
			$ejemplares = $this->service->getHabilitadosByArticuloId($articuloId);

			$response = array_map(
				fn($ejemplarDto) => $ejemplarDto->toArray(),
				$ejemplares
			);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $response,
			]);
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * PATCH /ejemplares/{id}/habilitar
	 */
	public function habilitar(int $id): void
	{
		try {
			$ejemplar = $this->service->habilitar($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar->toArray(),
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	/**
	 * PATCH /ejemplares/{id}/deshabilitar
	 */
	public function deshabilitar(int $id): void
	{
		try {
			$ejemplar = $this->service->deshabilitar($id);

			JsonHelper::jsonResponse([
				'error' => false,
				'data' => $ejemplar->toArray(),
			]);
		} catch (EntityNotFoundException $e) {
			JsonHelper::jsonResponse([
				'error' => true,
				'message' => $e->getMessage(),
			], 404);
		} catch (Throwable $e) {
			$this->handleServerError($e);
		}
	}

	private function handleServerError(Throwable $e): void
	{
		JsonHelper::jsonResponse([
			'error' => true,
			'message' => 'Error interno del servidor',
		], 500);

		error_log($e->getMessage());
	}
}
