<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserLoginResponse;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Exception\RoleNotFoundException;
use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Exception\UserNotFoundException;
use App\Auth\Mappers\UserMapper;
use App\Auth\Models\User;
use App\Auth\Repositories\AuthRepository;
use App\Auth\Repositories\RoleRepository;
use App\Lectores\Models\Lector;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Security\JwtTokenProvider;
use App\Shared\Security\PasswordEncoder;
use PDO;
use Throwable;

class AuthService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuthRepository $authRepository,
        private readonly LectorRepository $lectorRepository,
        private readonly RoleRepository $roleRepository,
        private readonly JwtTokenProvider $jwtTokenProvider,
        private readonly PasswordEncoder $passwordEncoder
    ) {
    }

    /**
     * @throws Throwable
     * @throws UserAlreadyExistsException
     */
    public function register(UserRegisterRequest $request): ?UserRegisterResponse
    {
        if ($this->authRepository->findByDni($request->dni) !== null) {
            throw new UserAlreadyExistsException("User with DNI already exists");
        }

        if ($this->lectorRepository->existsByEmail($request->email) !== null) {
            throw new UserAlreadyExistsException("User with email already exists");
        }

        $this->pdo->beginTransaction();

        try {
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

            $this->pdo->commit();

            return UserMapper::toRegisterResponse($savedUser, $savedLector);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @throws UserNotFoundException
     * @throws RoleNotFoundException
     */
    public function login(UserLoginRequest $request): UserLoginResponse
    {
        $user = $this->authRepository->findByDni($request->dni);
        if ($user === null) {
            throw new UserNotFoundException("User with DNI not found");
        }
        if (!$this->passwordEncoder->verify($request->password, $user->getPassword())) {
            throw new UserNotFoundException("Invalid credentials");
        }

        $role = $this->roleRepository->findById($user->getRoleId());
        if ($role === null) {
            throw new RoleNotFoundException("Role not found for user");
        }

        $token = $this->jwtTokenProvider->generateToken($user->getId(), $role->getNombre());

        return UserMapper::toLoginResponse($token);
    }

    private function generarTarjetaId(): string
    {
        return "tarjeta identificadora " . uniqid();
    }
}
