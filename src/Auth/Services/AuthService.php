<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserLoginResponse;
use App\Auth\Dtos\Response\UserRegisterResponse;
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

        if ($this->lectorRepository->existsByEmail($request->email)) {
            throw new UserAlreadyExistsException("User with email already exists");
        }

        $this->pdo->beginTransaction();

        try {
            $role = $this->roleRepository->getRoleByName('lector');
            if ($role === null) {
                throw new \RuntimeException("Role 'lector' not found in database");
            }

            $user = User::create(
                $request->dni,
                $request->password,
                $role->getId()
            );

            $hashedPassword = $this->passwordEncoder->hash($request->password);

            $savedUser = $this->authRepository->create(
                [
                    'dni' => $user->getDni(),
                    'password' => $hashedPassword,
                    'role_id' => $user->getRoleId(),
                ]
            );

            $savedUser->setRole($role);

            $lector = Lector::create(
                $this->generarTarjetaId(),
                $savedUser->getId(),
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
            throw new \RuntimeException(
                "Role not found for user {$user->getId()}, role_id: {$user->getRoleId()}"
            );
        }

        $token = $this->jwtTokenProvider->generateToken($user->getId(), $role->getNombre());

        return UserMapper::toLoginResponse($token);
    }

    private function generarTarjetaId(): string
    {
        $maxIntentos = 10;

        for ($i = 0; $i < $maxIntentos; $i++) {
            $tarjetaId = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (!$this->lectorRepository->existsByTarjetaId($tarjetaId)) {
                return $tarjetaId;
            }
        }

        throw new \RuntimeException('No se pudo generar una tarjeta ID única');
    }
}
