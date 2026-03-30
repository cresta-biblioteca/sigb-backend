<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class RolesAndPermissionsSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        // ============================================================
        // ROLES
        // ============================================================
        $roles = $this->table('role');
        $roles->insert([
            [
                'nombre' => 'admin',
                'descripcion' => 'Administrador del sistema con acceso completo',
            ],
            [
                'nombre' => 'lector',
                'descripcion' => 'Usuario lector de la biblioteca',
            ],
        ])->save();

        // ============================================================
        // PERMISOS
        // ============================================================
        $permisos = $this->table('permiso');
        $permisos->insert([
            // Gestión de usuarios
            ['nombre' => 'usuarios:listar', 'descripcion' => 'Listar usuarios del sistema'],
            ['nombre' => 'usuarios:ver', 'descripcion' => 'Ver detalle de un usuario'],
            ['nombre' => 'usuarios:crear', 'descripcion' => 'Crear nuevos usuarios'],
            ['nombre' => 'usuarios:editar', 'descripcion' => 'Editar usuarios existentes'],
            ['nombre' => 'usuarios:eliminar', 'descripcion' => 'Eliminar usuarios'],

            // Gestión de lectores
            ['nombre' => 'lectores:listar', 'descripcion' => 'Listar lectores'],
            ['nombre' => 'lectores:ver', 'descripcion' => 'Ver detalle de un lector'],
            ['nombre' => 'lectores:crear', 'descripcion' => 'Crear nuevos lectores'],
            ['nombre' => 'lectores:editar', 'descripcion' => 'Editar lectores existentes'],
            ['nombre' => 'lectores:eliminar', 'descripcion' => 'Eliminar lectores'],

            // Catálogo (artículos, libros, ejemplares)
            ['nombre' => 'catalogo:listar', 'descripcion' => 'Listar artículos del catálogo'],
            ['nombre' => 'catalogo:ver', 'descripcion' => 'Ver detalle de un artículo'],
            ['nombre' => 'catalogo:crear', 'descripcion' => 'Crear artículos en el catálogo'],
            ['nombre' => 'catalogo:editar', 'descripcion' => 'Editar artículos del catálogo'],
            ['nombre' => 'catalogo:eliminar', 'descripcion' => 'Eliminar artículos del catálogo'],

            // Préstamos
            ['nombre' => 'prestamos:listar', 'descripcion' => 'Listar préstamos'],
            ['nombre' => 'prestamos:ver', 'descripcion' => 'Ver detalle de un préstamo'],
            ['nombre' => 'prestamos:crear', 'descripcion' => 'Registrar nuevos préstamos'],
            ['nombre' => 'prestamos:devolver', 'descripcion' => 'Registrar devolución de préstamos'],
            ['nombre' => 'prestamos:renovar', 'descripcion' => 'Renovar préstamos existentes'],

            // Reservas
            ['nombre' => 'reservas:listar', 'descripcion' => 'Listar reservas'],
            ['nombre' => 'reservas:ver', 'descripcion' => 'Ver detalle de una reserva'],
            ['nombre' => 'reservas:crear', 'descripcion' => 'Crear nuevas reservas'],
            ['nombre' => 'reservas:cancelar', 'descripcion' => 'Cancelar reservas'],

            // Configuración
            ['nombre' => 'roles:gestionar', 'descripcion' => 'Gestionar roles y permisos'],
            ['nombre' => 'tipos_prestamo:gestionar', 'descripcion' => 'Gestionar tipos de préstamo'],
            ['nombre' => 'tipos_documento:gestionar', 'descripcion' => 'Gestionar tipos de documento'],
        ])->save();

        // ============================================================
        // ASIGNACIÓN DE PERMISOS A ROLES
        // ============================================================

        // Obtener IDs de roles
        $adminRoleId = $this->fetchRow("SELECT id FROM role WHERE nombre = 'admin'")['id'];
        $lectorRoleId = $this->fetchRow("SELECT id FROM role WHERE nombre = 'lector'")['id'];

        // Obtener todos los permisos
        $allPermisos = $this->fetchAll("SELECT id, nombre FROM permiso");

        $rolePermiso = $this->table('role_permiso');

        // Admin: todos los permisos
        $adminPermisos = [];
        foreach ($allPermisos as $permiso) {
            $adminPermisos[] = [
                'role_id' => $adminRoleId,
                'permiso_id' => $permiso['id'],
            ];
        }
        $rolePermiso->insert($adminPermisos)->save();

        // Lector: permisos limitados
        $lectorPermisoNames = [
            'catalogo:listar',
            'catalogo:ver',
            'prestamos:ver',
            'prestamos:renovar',
            'reservas:listar',
            'reservas:ver',
            'reservas:crear',
            'reservas:cancelar',
        ];

        $lectorPermisos = [];
        foreach ($allPermisos as $permiso) {
            if (in_array($permiso['nombre'], $lectorPermisoNames, true)) {
                $lectorPermisos[] = [
                    'role_id' => $lectorRoleId,
                    'permiso_id' => $permiso['id'],
                ];
            }
        }
        $rolePermiso->insert($lectorPermisos)->save();
    }
}
