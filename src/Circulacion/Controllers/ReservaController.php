<?php

namespace App\Circulacion\Controllers;

use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Models\Reserva;
use App\Circulacion\Services\ReservaService;
use App\Circulacion\Validators\CreateReservaValidator;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Http\JsonHelper;

readonly class ReservaController
{
    public function __construct(
        private ReservaService $reservaService
    )
    {

    }

    public function addReserva(Reserva $reserva): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        CreateReservaValidator::validate($data);

        $reservaRequest = CreateReservaRequest::fromArray($data);

        try {
            $createdReserva = $this->reservaService->addReserva($reservaRequest);

            JsonHelper::jsonResponse($createdReserva, 201);
        } catch (\RuntimeException $e) {
            JsonHelper::jsonResponse(['message' => $e->getMessage()], 400);
        } catch (EntityNotFoundException $e) {
            JsonHelper::jsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            error_log('[AuthController::addReserva] '
                . $e->getMessage()
                . ' in ' . $e->getFile()
                . ':' . $e->getLine());
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
        }
    }
}