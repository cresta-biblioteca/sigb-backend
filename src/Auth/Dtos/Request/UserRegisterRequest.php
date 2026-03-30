<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

use DateTimeImmutable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserRegisterRequest',
    required: ['dni', 'password', 'nombre', 'apellido', 'fecha_nacimiento', 'telefono', 'email']
)]
readonly class UserRegisterRequest
{
    public function __construct(
        #[OA\Property(description: 'DNI del usuario (7-8 dígitos)', type: 'string', example: '12345678')]
        private string $dni,
        #[OA\Property(
            description: 'Mínimo 8 caracteres, una mayúscula, una minúscula y un número',
            type: 'string',
            format: 'password'
        )]
        private string $password,
        #[OA\Property(type: 'string', example: 'Juan')]
        private string $nombre,
        #[OA\Property(type: 'string', example: 'Pérez')]
        private string $apellido,
        #[OA\Property(property: 'legajo', type: 'string', example: 'L12345', nullable: true)]
        private ?string $legajo,
        #[OA\Property(type: 'string', example: 'M', nullable: true)]
        private string $genero,
        #[OA\Property(property: 'fecha_nacimiento', type: 'string', format: 'date', example: '2000-01-31')]
        private DateTimeImmutable $fechaNacimiento,
        #[OA\Property(type: 'string', example: '2615001234')]
        private string $telefono,
        #[OA\Property(type: 'string', format: 'email', example: 'juan.perez@example.com')]
        private string $email,
        #[OA\Property(property: 'cresta_id', type: 'string', nullable: true)]
        private ?string $crestaId
    ) {
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getLegajo(): ?string
    {
        return $this->legajo;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function getFechaNacimiento(): DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCrestaId(): ?string
    {
        return $this->crestaId;
    }
}
