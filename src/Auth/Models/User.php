<?php

declare(strict_types=1);

namespace App\Auth\Models;

use App\Shared\Entity;

class User extends Entity
{
    private const DNI_PATTERN = '/^\d{7,8}$/';

    private string $dni;
    private string $password;
    private int $roleId;
    private ?Role $role = null;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo User (valida datos, hashea password)
     */
    public static function create(string $dni, string $password, int $roleId): self
    {
        $user = new self();
        $user->setDni($dni);
        $user->setPassword($password);
        $user->setRoleId($roleId);

        return $user;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $user = new self();
        $user->id = (int) $row['id'];
        $user->dni = $row['dni'];
        $user->password = $row['password'];
        $user->roleId = (int) $row['role_id'];
        $user->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        return $user;
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function setDni(string $dni): void
    {
        $this->assertNotEmpty($dni, 'dni');
        $this->assertMatchesPattern(
            $dni,
            self::DNI_PATTERN,
            'dni',
            'El DNI debe contener 7 u 8 digitos'
        );
        $this->dni = $dni;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Establece el password (lo hashea automáticamente si no está hasheado)
     */
    public function setPassword(string $password): void
    {
        $this->assertNotEmpty($password, 'password');
        $this->assertMinLength($password, 6, 'password');

        if (!$this->isHashed($password)) {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $this->password = $password;
        }
    }

    /**
     * Verifica si un password coincide con el hash almacenado
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function setRoleId(int $roleId): void
    {
        $this->assertPositive($roleId, 'role_id');
        $this->roleId = $roleId;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
        $this->roleId = $role->getId();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'dni' => $this->dni,
            'role_id' => $this->roleId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];

        if ($this->role !== null) {
            $data['role'] = $this->role->toArray();
        }

        return $data;
    }

    /**
     * Verifica si un string ya es un hash de password
     */
    private function isHashed(string $password): bool
    {
        $info = password_get_info($password);
        return $info['algo'] !== null && $info['algo'] !== 0;
    }
}
