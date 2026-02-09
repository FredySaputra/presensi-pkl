<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->enum('metode_izin', ['WA', 'Surat'])->nullable()->after('status');
            $table->string('file_surat')->nullable()->after('metode_izin'); 
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropColumn(['metode_izin', 'file_surat']);
        });
    }
};