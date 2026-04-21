<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRenovacionesToPrestamo extends AbstractMigration
{
    public function up(): void
    {
        $this->table('prestamo')
            ->addColumn('cant_renovaciones', 'integer', [
                'signed'  => false,
                'default' => 0,
                'after'   => 'lector_id',
            ])
            ->addColumn('max_renovaciones', 'integer', [
                'signed'  => false,
                'default' => 0,
                'after'   => 'cant_renovaciones',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('prestamo')
            ->removeColumn('cant_renovaciones')
            ->removeColumn('max_renovaciones')
            ->update();
    }
}
