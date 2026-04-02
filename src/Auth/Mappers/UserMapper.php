<?php

declare(strict_types=1);

namespace App\Auth\Mappers;

use App\Auth\Dtos\Request\ChangePasswordRequest;
use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserLoginResponse;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Models\User;
use App\Lectores\Models\Lector;
use DateTimeImmutable;

class UserMapper
{
    public static function toRegisterRequest(array $data): UserRegisterRequest
    {
        return new UserRegisterRequest(
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
    }
    public static function toLoginRequest(array $data): UserLoginRequest
    {
        return new UserLoginRequest(
            $data['dni'] ?? '',
            $data['password'] ?? ''
        );
    }

    public static function toChangePasswordRequest(array $data): ChangePasswordRequest
    {
        return new ChangePasswordRequest(
            $data['current_password'] ?? '',
            $data['new_password'] ?? ''
        );
    }

    public static function toRegisterResponse(User $user, Lector $lector): UserRegisterResponse
    {
        return new UserRegisterResponse(
            $user->getId(),
            $lector->getId(),
            $lector->getNombre() . " " . $lector->getApellido()
        );
    }

    public static function toLoginResponse(string $token): UserLoginResponse
    {
        return new UserLoginResponse($token);
    }
}
