<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Dtos\Request\ChangePasswordRequest;
use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserLoginResponse;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Exceptions\InvalidCredentialsException;
use App\Auth\Exceptions\UserAlreadyExistsException;
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
        if ($this->authRepository->findByDni($request->getDni()) !== null) {
            throw new UserAlreadyExistsException('dni');
        }

        if ($this->lectorRepository->existsByEmail($request->getEmail())) {
            throw new UserAlreadyExistsException('email');
        }

        $this->pdo->beginTransaction();

        try {
            $role = $this->roleRepository->getRoleByName('lector');
            if ($role === null) {
                throw new \RuntimeException("Role 'lector' not found in database");
            }

            $user = User::create(
                $request->getDni(),
                $request->getPassword(),
                $role->getId()
            );

            $hashedPassword = $this->passwordEncoder->hash($request->getPassword());

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
                $request->getNombre(),
                $request->getApellido(),
                $request->getFechaNacimiento(),
                $request->getTelefono(),
                $request->getEmail(),
                $request->getLegajo(),
                $request->getGenero(),
                $request->getCrestaId()
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
     * @throws InvalidCredentialsException
     */
    public function login(UserLoginRequest $request): UserLoginResponse
    {
        $user = $this->authRepository->findByDni($request->getDni());
        if ($user === null) {
            throw new InvalidCredentialsException();
        }
        if (!$this->passwordEncoder->verify($request->getPassword(), $user->getPassword())) {
            throw new InvalidCredentialsException();
        }


        $role = $this->roleRepository->findById($user->getRoleId());
        if ($role === null) {
            throw new \RuntimeException(
                "Role not found for user {$user->getId()}, role_id: {$user->getRoleId()}"
            );
        }

        $lectorId = null;
        if ($role->getNombre() === 'lector') {
            $lector = $this->lectorRepository->findByUserId($user->getId());
            $lectorId = $lector?->getId();
        }

        $token = $this->jwtTokenProvider->generateToken($user->getId(), $role->getNombre(), $user->getDni(), $lectorId);

        return UserMapper::toLoginResponse($token);
    }

    // public function logout(): void
    // {
    //     En una implementación real, podrías invalidar el token JWT aquí.
    //     Sin embargo, dado que los tokens JWT son stateless, no hay una forma directa de
    //     invalidarlos sin mantener una lista de tokens revocados.
    //     Para este ejemplo, simplemente no hacemos nada en el método logout.
    // }

    public function changePassword(ChangePasswordRequest $request, int $userId): void
    {
        $user = $this->authRepository->findById($userId);
        if ($user === null) {
            throw new InvalidCredentialsException();
        }

        if (!$this->passwordEncoder->verify($request->getCurrentPassword(), $user->getPassword())) {
            throw new InvalidCredentialsException();
        }

        $newHashedPassword = $this->passwordEncoder->hash($request->getNewPassword());
        $this->authRepository->updatePassword($userId, $newHashedPassword);
    }

    // TODO: re-implementar logica de generacion de tarjeta
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
