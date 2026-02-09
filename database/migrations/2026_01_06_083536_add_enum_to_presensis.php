<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE presensis MODIFY COLUMN status ENUM('Hadir', 'Izin', 'Alpa', 'Kurang') NOT NULL DEFAULT 'Hadir'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE presensis MODIFY COLUMN status ENUM('Hadir', 'Izin', 'Alpa') NOT NULL DEFAULT 'Hadir'");
    }
};
