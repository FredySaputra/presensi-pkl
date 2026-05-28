<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hari_liburs', function (Blueprint $table) {
            // Hapus batasan unique pada kolom tanggal agar bisa ada libur yang bertumpuk
            $table->dropUnique('hari_liburs_tanggal_unique');

            // Tambahkan relasi ke tabel sekolah (nullable karena libur nasional tidak punya sekolah_id)
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolahs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hari_liburs', function (Blueprint $table) {
            $table->unique('tanggal');
            $table->dropForeign(['sekolah_id']);
            $table->dropColumn('sekolah_id');
        });
    }
};
