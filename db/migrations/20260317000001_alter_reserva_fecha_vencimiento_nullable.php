<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterReservaFechaVencimientoNullable extends AbstractMigration
{
    /**
     * fecha_vencimiento pasa a ser nullable para soportar el caso donde
     * no hay ejemplares disponibles al momento de la reserva. Se asigna
     * cuando un ejemplar queda libre.
     */
    public function up(): void
    {
        $this->execute("
            ALTER TABLE reserva
                MODIFY COLUMN fecha_vencimiento DATETIME NULL DEFAULT NULL;
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE reserva
                MODIFY COLUMN fecha_vencimiento DATETIME NOT NULL;
        ");
    }
}
