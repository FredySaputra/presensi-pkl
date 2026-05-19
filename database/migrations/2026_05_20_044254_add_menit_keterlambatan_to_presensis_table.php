<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->integer('menit_telat')->nullable()->after('jam_masuk');
            $table->integer('menit_pulang_cepat')->nullable()->after('jam_pulang');
        });
    }

    public function down()
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropColumn(['menit_telat', 'menit_pulang_cepat']);
        });
    }
};
