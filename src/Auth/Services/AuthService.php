<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Mappers\UserMapper;
use App\Auth\Repositories\AuthRepository;
use App\Auth\Repositories\RoleRepository;
use App\Lectores\Repositories\LectorRepository;
use App\Auth\Models\User;
use App\Lectores\Models\Lector;

class AuthService
{
    private AuthRepository $authRepository;
    private LectorRepository $lectorRepository;
    private RoleRepository $roleRepository;

    public function __construct(AuthRepository $repository, LectorRepository $lectorRepository)
    {
        $this->authRepository = $repository;
        $this->lectorRepository = $lectorRepository;
    }

    public function register(UserRegisterRequest $request): ?UserRegisterResponse
    {
        if ($this->authRepository->findByDni($request->dni) !== null) {
            throw new UserAlreadyExistsException("User with DNI already exists");
        }

        if ($this->lectorRepository->existsByEmail($request->email) !== null) {
            throw new UserAlreadyExistsException("User with email already exists");
        }

        $role = $this->roleRepository->getRoleByName('lector');

        $user = User::create(
            $request->dni,
            $request->password,
            $role->getId()
        );

        $savedUser = $this->authRepository->create(
            [
                'dni' => $user->getDni(),
                'password' => $user->getPassword(),
                'role_id' => $user->getRoleId(),
            ]
        );

        $savedUser->setRole($role);

        // Ahora genero el lector
        $lector = Lector::create(
            $this->generarTarjetaId(),
            $user->getId(),
            $request->nombre,
            $request->apellido,
            $request->fechaNacimiento,
            $request->telefono,
            $request->email,
            $request->legajo,
            $request->genero,
            $request->crestaId
        );

        $savedLector = $this->lectorRepository->create(
            [
                'tarjeta_id' => $lector->getTarjetaId(),
                'user_id' => $lector->getUserId(),
                'nombre' => $lector->getNombre(),
                'apellido' => $lector->getApellido(),
                'fecha_nacimiento' => $lector->getFechaNacimiento()->format('Y-m-d'),
                'telefono' => $lector->getTelefono(),
                'email' => $lector->getEmail(),
                'legajo' => $lector->getLegajo(),
                'genero' => $lector->getGenero(),
                'cresta_id' => $lector->getCrestaId()
            ]
        );

        return UserMapper::toRegisterResponse($savedUser, $savedLector);
    }

    private function generarTarjetaId(): string
    {
        return "tarjeta identificadora " . uniqid();
    }
}
