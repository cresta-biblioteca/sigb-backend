<?php

declare(strict_types=1);

namespace App\Auth\Mappers;

use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Models\User;
use App\Lectores\Models\Lector;

class UserMapper
{
    public static function toRegisterResponse(User $user, Lector $lector): UserRegisterResponse
    {
        return new UserRegisterResponse(
            $user->getId(),
            $lector->getId(),
            $lector->getNombre() . " " . $lector->getApellido()
        );
    }
}
