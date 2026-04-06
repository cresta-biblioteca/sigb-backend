<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Dtos\Request\ChangePasswordRequest;
use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Mappers\UserMapper;
use App\Auth\Services\AuthService;
use App\Auth\Validators\UserChangePasswordValidator;
use App\Auth\Validators\UserLoginValidator;
use App\Auth\Validators\UserRegisterValidator;
use App\Shared\Http\JsonHelper;
use DateTimeImmutable;
use OpenApi\Attributes as OA;

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
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) ?? [];

        UserRegisterValidator::validate($data);

        $response = $this->authService->register(UserMapper::toRegisterRequest($data));
        JsonHelper::jsonResponse($response, 201);
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
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) ?? [];
        UserChangePasswordValidator::validate($data);

        $this->authService->changePassword(UserMapper::toChangePasswordRequest($data), $_SERVER['USER_ID']);
        JsonHelper::jsonResponse(['message' => 'Contraseña actualizada correctamente'], 200);
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
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) ?? [];

        UserLoginValidator::validate($data);

        $response = $this->authService->login(UserMapper::toLoginRequest($data));
        JsonHelper::jsonResponse($response, 200);
    }
}
