<?php

declare(strict_types=1);

namespace App\Auth\Mappers;

use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Models\User;

class UserMapper {
    public static function fromRegisterRequest(UserRegisterRequest $request): User {
        return new User(
            dni: $request->dni,
            password: $request->password,
            nombre: $request->nombre,
            apellido: $request->apellido,
            legajo: $request->legajo,
            genero: $request->genero,
            fechaNacimiento: $request->fechaNacimiento,
            telefono: $request->telefono,
            email: $request->email,
            crestaId: $request->crestaId
        );
    }

    public static function toRegisterResponse(User $user): UserRegisterResponse {
        return new UserRegisterResponse(
            id: $user->id,
            dni: $user->dni,
            nombre: $user->nombre,
            apellido: $user->apellido,
            legajo: $user->legajo,
            genero: $user->genero,
            fechaNacimiento: $user->fechaNacimiento,
            telefono: $user->telefono,
            email: $user->email,
            crestaId: $user->crestaId
        );
    }
}