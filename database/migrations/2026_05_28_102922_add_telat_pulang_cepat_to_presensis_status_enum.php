<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE presensis MODIFY COLUMN status ENUM('Hadir', 'Izin', 'Alpa', 'Kurang', 'Telat', 'Pulang Cepat') NOT NULL DEFAULT 'Hadir'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE presensis MODIFY COLUMN status ENUM('Hadir', 'Izin', 'Alpa', 'Kurang') NOT NULL DEFAULT 'Hadir'");
    }
};
