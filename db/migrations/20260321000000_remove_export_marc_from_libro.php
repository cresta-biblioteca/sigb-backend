<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveExportMarcFromLibro extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE libro DROP COLUMN export_marc');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE libro ADD COLUMN export_marc TEXT NULL');
    }
}
