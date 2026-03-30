<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Controllers;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Services\EjemplarService;
use App\Catalogo\Ejemplares\Validators\EjemplarRequestValidator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;

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
        if (isset($_GET['codigo_barras']) && $_GET['codigo_barras'] !== '') {
            $codigoBarras = trim((string) $_GET['codigo_barras']);

            if (!EjemplarRequestValidator::validateCodigoBarras($codigoBarras)) {
                throw ValidationException::forField(
                    'codigo_barras',
                    'El campo codigo_barras debe contener solo dígitos (máximo 13)'
                );
            }

            $ejemplar = $this->ejemplarService->getByCodigoBarras($codigoBarras);
            JsonHelper::jsonResponse(['data' => $ejemplar === null ? [] : [$ejemplar]]);
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

            JsonHelper::jsonResponse(['data' => $this->ejemplarService->getByHabilitado($habilitado)]);
            return;
        }

        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getAll()]);
    }

    /**
     * GET /ejemplares/{id}
     */
    public function getById(int $id): void
    {
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getById($id)]);
    }

    /**
     * POST /ejemplares
     */
    public function createEjemplar(): void
    {
        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        EjemplarRequestValidator::validate($input);

        $request = new EjemplarRequest(
            (int) $input['articulo_id'],
            trim((string) $input['codigo_barras']),
            (bool) $input['habilitado'],
            isset($input['signatura_topografica']) ? trim((string) $input['signatura_topografica']) : null
        );

        JsonHelper::jsonResponse(['data' => $this->ejemplarService->createEjemplar($request)], 201);
    }

    /**
     * PUT /ejemplares/{id}
     */
    public function updateEjemplar(int $id): void
    {
        if ($id < 1) {
            throw ValidationException::forField('id', 'El ID debe ser un entero positivo mayor que 0');
        }

        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        EjemplarRequestValidator::validate($input);

        $request = new EjemplarRequest(
            (int) $input['articulo_id'],
            trim((string) $input['codigo_barras']),
            (bool) $input['habilitado'],
            isset($input['signatura_topografica']) ? trim((string) $input['signatura_topografica']) : null
        );

        JsonHelper::jsonResponse(['data' => $this->ejemplarService->updateEjemplar($id, $request)]);
    }

    /**
     * DELETE /ejemplares/{id}
     */
    public function deleteEjemplar(int $id): void
    {
        $this->ejemplarService->deleteEjemplar($id);
        JsonHelper::jsonResponse(['message' => 'Ejemplar eliminado']);
    }

    /**
     * GET /articulos/{articuloId}/ejemplares
     */
    public function getByArticuloId(int $articuloId): void
    {
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getByArticuloId($articuloId)]);
    }

    /**
     * GET /articulos/{articuloId}/ejemplares/habilitados
     */
    public function getHabilitadosByArticuloId(int $articuloId): void
    {
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->getHabilitadosByArticuloId($articuloId)]);
    }

    /**
     * PATCH /ejemplares/{id}/habilitar
     */
    public function habilitar(int $id): void
    {
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->habilitarEjemplar($id)]);
    }

    /**
     * PATCH /ejemplares/{id}/deshabilitar
     */
    public function deshabilitar(int $id): void
    {
        JsonHelper::jsonResponse(['data' => $this->ejemplarService->deshabilitarEjemplar($id)]);
    }
}
