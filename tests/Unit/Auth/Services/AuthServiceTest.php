<?php

use App\Auth\Dtos\Request\ChangePasswordRequest;
use App\Auth\Dtos\Request\UserLoginRequest;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserLoginResponse;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Exceptions\InvalidCredentialsException;
use App\Auth\Exceptions\UserAlreadyExistsException;
use App\Auth\Models\Role;
use App\Auth\Models\User;
use App\Auth\Repositories\AuthRepository;
use App\Auth\Repositories\RoleRepository;
use App\Auth\Services\AuthService;
use App\Lectores\Models\Lector;
use App\Lectores\Repositories\LectorRepository;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Security\JwtTokenProvider;
use App\Shared\Security\PasswordEncoder;

beforeEach(function () {
    $this->dni = '40123567';
    $this->password = 'Password_123';
    $this->hashedPassword = password_hash($this->password, PASSWORD_BCRYPT);
    $this->email = 'test@example.com';

    $this->role = Role::create('lector', 'usuario normal del sistema');
    $this->role->setId(1);

    $this->savedUser = User::fromDatabase([
        'id' => 1,
        'dni' => $this->dni,
        'password' => $this->hashedPassword,
        'role_id' => $this->role->getId(),
        'created_at' => null,
        'updated_at' => null,
    ]);
    $this->savedUser->setRole($this->role);

    $this->userRequest = new UserRegisterRequest(
        $this->dni,
        $this->password,
        'John',
        'Doe',
        null,
        'M',
        new DateTimeImmutable('2000-01-01'),
        '2266556677',
        'johndoe@gmail.com',
        null
    );

    $this->pdo = Mockery::mock(PDO::class);
    $this->authRepository = Mockery::mock(AuthRepository::class);
    $this->lectorRepository = Mockery::mock(LectorRepository::class);
    $this->roleRepository = Mockery::mock(RoleRepository::class);
    $this->jwtProvider = Mockery::mock(JwtTokenProvider::class);
    $this->passwordEncoder = Mockery::mock(PasswordEncoder::class);

    $this->service = new AuthService(
        $this->pdo,
        $this->authRepository,
        $this->lectorRepository,
        $this->roleRepository,
        $this->jwtProvider,
        $this->passwordEncoder
    );
});

afterEach(function () {
    Mockery::close();
});

test('login lanza UserNotFoundException si el usuario no existe', function () {
    // Arrange
    $request = new UserLoginRequest(dni: $this->dni, password: $this->password);

    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->dni)
        ->once()
        ->andReturnNull();

    // Act & Assert
    expect(fn() => $this->service->login($request))
        ->toThrow(InvalidCredentialsException::class);
});

test('login lanza UserNotFoundException si la contraseña es incorrecta', function () {
    // Arrange
    $request = new UserLoginRequest(dni: $this->dni, password: 'Wrong_password123');

    $user = User::fromDatabase([
        'id' => 1,
        'dni' => $this->dni,
        'password' => $this->hashedPassword, // utiliza $this->password
        'role_id' => 1,
        'created_at' => null,
        'updated_at' => null,
    ]);

    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->dni)
        ->once()
        ->andReturn($user);

    $this->passwordEncoder
        ->shouldReceive('verify')
        ->with($request->getPassword(), $this->hashedPassword)
        ->once()
        ->andReturn(false);

    // Act & Assert
    expect(fn() => $this->service->login($request))
        ->toThrow(InvalidCredentialsException::class);
});

test('login lanza RuntimeException si el rol del usuario no existe', function () {
    // Arrange
    $request = new UserLoginRequest(dni: $this->dni, password: $this->password);

    $user = User::fromDatabase([
        'id' => 1,
        'dni' => $this->dni,
        'password' => $this->hashedPassword,
        'role_id' => 1,
        'created_at' => null,
        'updated_at' => null,
    ]);

    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->dni)
        ->once()
        ->andReturn($user);

    $this->passwordEncoder
        ->shouldReceive('verify')
        ->with($request->getPassword(), $this->hashedPassword)
        ->once()
        ->andReturn(true);

    $this->roleRepository
        ->shouldReceive('findById')
        ->with($user->getRoleId())
        ->once()
        ->andReturnNull();

    // Act & Assert
    expect(fn() => $this->service->login($request))
        ->toThrow(\RuntimeException::class);
});

test('login retorna un token cuando las credenciales son válidas', function () {
    $request = new UserLoginRequest(dni: $this->dni, password: $this->password);

    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->dni)
        ->once()
        ->andReturn($this->savedUser);

    $this->passwordEncoder
        ->shouldReceive('verify')
        ->with($request->getPassword(), $this->hashedPassword)
        ->once()
        ->andReturn(true);

    $this->roleRepository
        ->shouldReceive('findById')
        ->with($this->savedUser->getRoleId())
        ->once()
        ->andReturn($this->role);

    $this->jwtProvider
        ->shouldReceive('generateToken')
        ->with($this->savedUser->getId(), $this->role->getNombre())
        ->once()
        ->andReturn('jwt.token.aqui');

    $result = $this->service->login($request);

    expect($result)->toBeInstanceOf(UserLoginResponse::class);
});

test("registro exitoso cuando las credenciales son validas y no viene con legajo ni ID de cresta", function (): void {
    $request = $this->userRequest;

    $savedLector = Lector::fromDatabase([
        'id' => 1,
        'tarjeta_id' => '000001',
        'user_id' => $this->savedUser->getId(),
        'nombre' => $request->getNombre(),
        'apellido' => $request->getApellido(),
        'fecha_nacimiento' => $request->getFechaNacimiento()->format('Y-m-d'),
        'telefono' => $request->getTelefono(),
        'email' => $request->getEmail(),
        'legajo' => $request->getLegajo(),
        'genero' => $request->getGenero(),
        'cresta_id' => $request->getCrestaId(),
        'created_at' => null,
        'updated_at' => null,
    ]);

    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($request->getDni())
        ->once()
        ->andReturn(null);

    $this->lectorRepository
        ->shouldReceive('existsByEmail')
        ->with($request->getEmail())
        ->once()
        ->andReturn(false);

    $this->pdo
        ->shouldReceive('beginTransaction')
        ->once();

    $this->roleRepository
        ->shouldReceive('getRoleByName')
        ->with('lector')
        ->once()
        ->andReturn($this->role);

    $this->passwordEncoder
        ->shouldReceive('hash')
        ->with($request->getPassword())
        ->once()
        ->andReturn($this->hashedPassword);

    $this->authRepository
        ->shouldReceive('create')
        ->with([
            'dni' => $request->getDni(),
            'password' => $this->hashedPassword,
            'role_id' => $this->role->getId(),
        ])
        ->once()
        ->andReturn($this->savedUser);

    $this->lectorRepository
        ->shouldReceive('existsByTarjetaId')
        ->with(Mockery::any())
        ->once()
        ->andReturn(false);

    $this->lectorRepository
        ->shouldReceive('create')
        ->with(Mockery::on(function (array $data) use ($request): bool {
            return isset($data['tarjeta_id'])
                && $data['user_id'] === $this->savedUser->getId()
                && $data['nombre'] === $request->getNombre()
                && $data['apellido'] === $request->getApellido()
                && $data['fecha_nacimiento'] === $request->getFechaNacimiento()->format('Y-m-d')
                && $data['telefono'] === $request->getTelefono()
                && $data['email'] === $request->getEmail()
                && $data['legajo'] === $request->getLegajo()
                && $data['genero'] === $request->getGenero()
                && $data['cresta_id'] === $request->getCrestaId();
        }))
        ->once()
        ->andReturn($savedLector);

    $this->pdo
        ->shouldReceive('commit')
        ->once();

    $result = $this->service->register($request);
    expect($result)->toBeInstanceOf(UserRegisterResponse::class);
});

test('registro lanza UserAlreadyExistsException si el DNI ya está en uso', function (): void {
    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->userRequest->getDni())
        ->once()
        ->andReturn($this->savedUser);

    expect(fn() => $this->service->register($this->userRequest))
        ->toThrow(UserAlreadyExistsException::class);
});

test('registro lanza UserAlreadyExistsException si el email ya está en uso', function (): void {
    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->userRequest->getDni())
        ->once()
        ->andReturnNull();

    $this->lectorRepository
        ->shouldReceive('existsByEmail')
        ->with($this->userRequest->getEmail())
        ->once()
        ->andReturn(true);

    expect(fn() => $this->service->register($this->userRequest))
        ->toThrow(UserAlreadyExistsException::class);
});

test('registro lanza RuntimeException si el rol lector no existe en la base de datos', function (): void {
    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->userRequest->getDni())
        ->once()
        ->andReturnNull();

    $this->lectorRepository
        ->shouldReceive('existsByEmail')
        ->with($this->userRequest->getEmail())
        ->once()
        ->andReturn(false);

    $this->pdo
        ->shouldReceive('beginTransaction')
        ->once();

    $this->roleRepository
        ->shouldReceive('getRoleByName')
        ->with('lector')
        ->once()
        ->andReturnNull();

    $this->pdo
        ->shouldReceive('rollBack')
        ->once();

    expect(fn() => $this->service->register($this->userRequest))
        ->toThrow(\RuntimeException::class);
});

test('registro lanza RuntimeException y hace rollback si se superan los 10 intentos de generar tarjeta ID', function (): void {
    $this->authRepository
        ->shouldReceive('findByDni')
        ->with($this->userRequest->getDni())
        ->once()
        ->andReturnNull();

    $this->lectorRepository
        ->shouldReceive('existsByEmail')
        ->with($this->userRequest->getEmail())
        ->once()
        ->andReturn(false);

    $this->pdo
        ->shouldReceive('beginTransaction')
        ->once();

    $this->roleRepository
        ->shouldReceive('getRoleByName')
        ->with('lector')
        ->once()
        ->andReturn($this->role);

    $this->passwordEncoder
        ->shouldReceive('hash')
        ->with($this->userRequest->getPassword())
        ->once()
        ->andReturn($this->hashedPassword);

    $this->authRepository
        ->shouldReceive('create')
        ->with([
            'dni' => $this->userRequest->getDni(),
            'password' => $this->hashedPassword,
            'role_id' => $this->role->getId(),
        ])
        ->once()
        ->andReturn($this->savedUser);

    $this->lectorRepository
        ->shouldReceive('existsByTarjetaId')
        ->with(Mockery::any())
        ->times(10)
        ->andReturn(true);

    $this->pdo
        ->shouldReceive('rollBack')
        ->once();

    expect(fn() => $this->service->register($this->userRequest))
        ->toThrow(\RuntimeException::class);
});

test('registro hace rollback y lanza BusinessRuleException cuando el dominio rechaza los datos', function (): void {
    $requestConDniInvalido = new UserRegisterRequest(
        'INVALIDO',
        $this->password,
        'John',
        'Doe',
        null,
        'M',
        new DateTimeImmutable('2000-01-01'),
        '2266556677',
        'johndoe@gmail.com',
        null
    );

    $this->authRepository
        ->shouldReceive('findByDni')
        ->with('INVALIDO')
        ->once()
        ->andReturnNull();

    $this->lectorRepository
        ->shouldReceive('existsByEmail')
        ->with($requestConDniInvalido->getEmail())
        ->once()
        ->andReturn(false);

    $this->pdo
        ->shouldReceive('beginTransaction')
        ->once();

    $this->roleRepository
        ->shouldReceive('getRoleByName')
        ->with('lector')
        ->once()
        ->andReturn($this->role);

    $this->pdo
        ->shouldReceive('rollBack')
        ->once();

    expect(fn() => $this->service->register($requestConDniInvalido))
        ->toThrow(BusinessRuleException::class);
});

test('cambio de contraseña exitoso cuando las credenciales son válidas', function (): void {
    $request = new ChangePasswordRequest($this->password, 'New_password_123');

    $this->authRepository
        ->shouldReceive('findById')
        ->with($this->savedUser->getId())
        ->once()
        ->andReturn($this->savedUser);

    $this->passwordEncoder
        ->shouldReceive('verify')
        ->with($request->getCurrentPassword(), $this->savedUser->getPassword())
        ->once()
        ->andReturn(true);

    $this->passwordEncoder
        ->shouldReceive('hash')
        ->with($request->getNewPassword())
        ->once()
        ->andReturn('hashed_new_password');

    $this->authRepository
        ->shouldReceive('updatePassword')
        ->with($this->savedUser->getId(), 'hashed_new_password')
        ->once();

    $this->service->changePassword($request, $this->savedUser->getId());
});

test('cambio de contraseña lanza InvalidCredentialsException si el usuario no existe', function (): void {
    $request = new ChangePasswordRequest($this->password, 'New_password_123');

    $this->authRepository
        ->shouldReceive('findById')
        ->with(999)
        ->once()
        ->andReturnNull();

    expect(fn() => $this->service->changePassword($request, 999))
        ->toThrow(InvalidCredentialsException::class);
});

test('cambio de contraseña lanza InvalidCredentialsException si la contraseña actual es incorrecta', function (): void {
    $request = new ChangePasswordRequest('Wrong_password123', 'New_password_123');

    $this->authRepository
        ->shouldReceive('findById')
        ->with($this->savedUser->getId())
        ->once()
        ->andReturn($this->savedUser);

    $this->passwordEncoder
        ->shouldReceive('verify')
        ->with($request->getCurrentPassword(), $this->savedUser->getPassword())
        ->once()
        ->andReturn(false);

    expect(fn() => $this->service->changePassword($request, $this->savedUser->getId()))
        ->toThrow(InvalidCredentialsException::class);
});

