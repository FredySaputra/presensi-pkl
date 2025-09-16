    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            // Menambahkan 'Kurang' ke dalam pilihan ENUM
            DB::statement("ALTER TABLE presensis MODIFY COLUMN status ENUM('Hadir', 'Izin', 'Alpa', 'Kurang') NOT NULL");
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            // Mengembalikan ke kondisi semula jika migrasi di-rollback
            DB::statement("ALTER TABLE presensis MODIFY COLUMN status ENUM('Hadir', 'Izin', 'Alpa') NOT NULL");
        }
    };