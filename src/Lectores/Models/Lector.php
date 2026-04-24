<?php

declare(strict_types=1);

namespace App\Lectores\Models;

use App\Auth\Models\User;
use App\Shared\Entity;
use DateTimeImmutable;

class Lector extends Entity
{
    private const DNI_PATTERN = '/^\d{7,8}$/';
    private const GENEROS_VALIDOS = ['M', 'F', 'X'];
    private const TARJETA_PATTERN = '/^\d{6}$/';

    private string $tarjetaId;
    private int $userId;
    private string $nombre;
    private string $apellido;
    private ?string $legajo;
    private ?string $genero;
    private DateTimeImmutable $fechaNacimiento;
    private string $telefono;
    private string $email;
    private ?int $crestaId;

    private ?User $user = null;
    /** @var Carrera[] */
    private array $carreras = [];

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Lector (valida datos)
     */
    public static function create(
        string $tarjetaId,
        int $userId,
        string $nombre,
        string $apellido,
        DateTimeImmutable $fechaNacimiento,
        string $telefono,
        string $email,
        ?string $legajo = null,
        ?string $genero = null,
        ?int $crestaId = null
    ): self {
        $lector = new self();
        $lector->setTarjetaId($tarjetaId);
        $lector->setUserId($userId);
        $lector->setNombre($nombre);
        $lector->setApellido($apellido);
        $lector->setFechaNacimiento($fechaNacimiento);
        $lector->setTelefono($telefono);
        $lector->setEmail($email);
        $lector->setLegajo($legajo);
        $lector->setGenero($genero);
        $lector->setCrestaId($crestaId);

        return $lector;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $lector = new self();
        $lector->id = (int) $row['id'];
        $lector->tarjetaId = $row['tarjeta_id'];
        $lector->userId = (int) $row['user_id'];
        $lector->nombre = $row['nombre'];
        $lector->apellido = $row['apellido'];
        $lector->legajo = $row['legajo'];
        $lector->genero = $row['genero'];
        $lector->fechaNacimiento = new DateTimeImmutable($row['fecha_nacimiento']);
        $lector->telefono = $row['telefono'];
        $lector->email = $row['email'];
        $lector->crestaId = $row['cresta_id'] !== null ? (int) $row['cresta_id'] : null;
        $lector->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            $row['deleted_at'] ?? null
        );

        return $lector;
    }

    public function getTarjetaId(): string
    {
        return $this->tarjetaId;
    }

    public function setTarjetaId(string $tarjetaId): void
    {
        $this->assertNotEmpty($tarjetaId, 'tarjeta_id');
        $this->assertMatchesPattern(
            $tarjetaId,
            self::TARJETA_PATTERN,
            'tarjeta_id',
            'El ID de tarjeta debe tener exactamente 6 digitos'
        );
        $this->tarjetaId = $tarjetaId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->assertPositive($userId, 'user_id');
        $this->userId = $userId;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->assertNotEmpty($nombre, 'nombre');
        $this->assertMaxLength($nombre, 100, 'nombre');
        $this->nombre = $nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): void
    {
        $this->assertNotEmpty($apellido, 'apellido');
        $this->assertMaxLength($apellido, 100, 'apellido');
        $this->apellido = $apellido;
    }

    public function getNombreCompleto(): string
    {
        return "{$this->apellido}, {$this->nombre}";
    }

    public function getLegajo(): ?string
    {
        return $this->legajo;
    }

    public function setLegajo(?string $legajo): void
    {
        if ($legajo !== null) {
            $this->assertMaxLength($legajo, 8, 'legajo');
        }
        $this->legajo = $legajo;
    }

    public function getGenero(): ?string
    {
        return $this->genero;
    }

    public function setGenero(?string $genero): void
    {
        if ($genero !== null) {
            $this->assertInArray($genero, self::GENEROS_VALIDOS, 'genero');
        }
        $this->genero = $genero;
    }

    public function getFechaNacimiento(): DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(DateTimeImmutable $fechaNacimiento): void
    {
        $this->assertNotFutureDate($fechaNacimiento, 'fecha_nacimiento');
        $this->fechaNacimiento = $fechaNacimiento;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): void
    {
        $this->assertNotEmpty($telefono, 'telefono');
        $this->assertMaxLength($telefono, 50, 'telefono');
        $this->telefono = $telefono;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->assertNotEmpty($email, 'email');
        $this->assertValidEmail($email, 'email');
        $this->assertMaxLength($email, 255, 'email');
        $this->email = $email;
    }

    public function getCrestaId(): ?int
    {
        return $this->crestaId;
    }

    public function setCrestaId(?int $crestaId): void
    {
        if ($crestaId !== null) {
            $this->assertPositive($crestaId, 'cresta_id');
        }
        $this->crestaId = $crestaId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->userId = $user->getId();
    }

    /**
     * @return Carrera[]
     */
    public function getCarreras(): array
    {
        return $this->carreras;
    }

    /**
     * @param Carrera[] $carreras
     */
    public function setCarreras(array $carreras): void
    {
        $this->carreras = $carreras;
    }

    public function addCarrera(Carrera $carrera): void
    {
        $this->carreras[] = $carrera;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'tarjeta_id' => $this->tarjetaId,
            'user_id' => $this->userId,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'legajo' => $this->legajo,
            'genero' => $this->genero,
            'fecha_nacimiento' => $this->fechaNacimiento->format('Y-m-d'),
            'telefono' => $this->telefono,
            'email' => $this->email,
            'cresta_id' => $this->crestaId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->user !== null) {
            $data['user'] = $this->user->toArray();
        }

        if (!empty($this->carreras)) {
            $data['carreras'] = array_map(fn(Carrera $c) => $c->toArray(), $this->carreras);
        }

        return $data;
    }
}
