<?php

declare(strict_types=1);

namespace App\Circulacion\Controllers;

use App\Circulacion\Dtos\Request\CreateReservaRequest;
use App\Circulacion\Services\ReservaService;
use App\Circulacion\Validators\CreateReservaValidator;
use App\Shared\Http\ExceptionHandler;
use App\Shared\Http\JsonHelper;
use Throwable;

readonly class ReservaController
{
    public function __construct(private ReservaService $reservaService)
    {
    }

    public function addReserva(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) ?? [];

            CreateReservaValidator::validate($data);

            $reservaRequest = CreateReservaRequest::fromArray($data);

            $response = $this->reservaService->addReserva($reservaRequest);

            JsonHelper::jsonResponse($response, 201);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'ReservaController::addReserva');
        }
    }
}
