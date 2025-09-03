    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::table('sekolahs', function (Blueprint $table) {
                // Tambah kolom untuk menyimpan hari libur (1=Senin, 2=Selasa, dst.)
                // Dibuat nullable jika sekolah tidak punya hari libur spesifik.
                $table->tinyInteger('hari_libur')->nullable()->after('nama_sekolah');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('sekolahs', function (Blueprint $table) {
                $table->dropColumn('hari_libur');
            });
        }
    };
    