<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropMateriaTables extends AbstractMigration
{
    public function up(): void
    {
        // Eliminar tablas de materia ya que el concepto fue descartado del modelo de datos
        $this->table('materia_articulo')->drop()->save();
        $this->table('carrera_materia')->drop()->save();
        $this->table('materia')->drop()->save();
    }

    public function down(): void
    {
        $this->table('materia', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('titulo', 'string', ['limit' => 100, 'null' => false])
            ->create();

        $this->table('carrera_materia', ['id' => false])
            ->addColumn('carrera_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('materia_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addForeignKey('carrera_id', 'carrera', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('materia_id', 'materia', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('materia_articulo', ['id' => false])
            ->addColumn('articulo_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('materia_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addForeignKey('articulo_id', 'articulo', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('materia_id', 'materia', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
