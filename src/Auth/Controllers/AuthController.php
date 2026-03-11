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
use OpenApi\Attributes as OA;
use Throwable;

readonly class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    #[OA\Post(
        path: '/auth/register',
        description: 'Crea un nuevo usuario y su perfil de lector en el sistema',
        summary: 'Registrar usuario',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserRegisterRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado exitosamente',
                content: new OA\JsonContent(ref: '#/components/schemas/UserRegisterResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Datos de entrada no válidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'El usuario ya existe',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación de negocio',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'field', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
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

    #[OA\Post(
        path: '/auth/change-password',
        description: 'Actualiza la contraseña del usuario autenticado. Requiere token JWT.',
        summary: 'Cambiar contraseña',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ChangePasswordRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contraseña actualizada correctamente',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Datos de entrada no válidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado o contraseña actual incorrecta',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
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
            error_log('[AuthController::changePassword] '
                . $e->getMessage()
                . ' in ' . $e->getFile()
                . ':' . $e->getLine());
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
        }
    }

    #[OA\Post(
        path: '/auth/login',
        description: 'Autentica un usuario con DNI y contraseña, retorna un token JWT',
        summary: 'Iniciar sesión',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserLoginRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(ref: '#/components/schemas/UserLoginResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
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
