<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Dtos\Request\ChangePasswordRequest;
use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Exception\UserNotFoundException;
use App\Auth\Services\AuthService;
use App\Auth\Validators\UserChangePasswordValidator;
use App\Auth\Validators\UserLoginValidator;
use App\Auth\Validators\UserRegisterValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use DateTimeImmutable;
use Throwable;

readonly class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function createUser(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];

            UserRegisterValidator::validate($data);

            $request = new UserRegisterRequest(
                $data['dni'],
                $data['password'],
                $data['nombre'],
                $data['apellido'],
                $data['legajo'] ?? null,
                $data['genero'] ?? null,
                new DateTimeImmutable($data['fecha_nacimiento']),
                $data['telefono'],
                $data['email'],
                $data['cresta_id'] ?? null
            );

            $response = $this->authService->register($request);
            JsonHelper::jsonResponse($response, 201);
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
        } catch (UserAlreadyExistsException $e) {
            JsonHelper::jsonResponse(['message' => 'El usuario ya existe'], 409);
        } catch (Throwable $e) {
            error_log('[AuthController::register] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
        }
    }

    public function changePassword(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            UserChangePasswordValidator::validate($data);

            $request = new ChangePasswordRequest(
                $data['current_password'] ?? '',
                $data['new_password'] ?? ''
            );

            $this->authService->changePassword($request, $_SERVER['USER_ID']);
            JsonHelper::jsonResponse(['message' => 'Contraseña actualizada correctamente'], 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (UserNotFoundException $e) {
            JsonHelper::jsonResponse(['message' => 'Credenciales inválidas'], 401);
        } catch (Throwable $e) {
            error_log('[AuthController::changePassword] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
        }
    }

    public function login(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];

            UserLoginValidator::validate($data);

            $request = new UserLoginRequest(
                $data['dni'] ?? '',
                $data['password'] ?? ''
            );

            $response = $this->authService->login($request);
            JsonHelper::jsonResponse($response, 200);
        } catch (UserNotFoundException $e) {
            JsonHelper::jsonResponse(['message' => 'Credenciales inválidas'], 401);
        } catch (Throwable $e) {
            error_log('[AuthController::login] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
        }
    }
}
