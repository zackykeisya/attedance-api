<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('attendances', function (Blueprint $table) {
        $table->string('izin_jenis')->nullable(); // 'sakit' atau 'keperluan'
        $table->text('izin_keterangan')->nullable();
    });
}

public function down()
{
    Schema::table('attendances', function (Blueprint $table) {
        $table->dropColumn(['izin_jenis', 'izin_keterangan']);
    });
}

};
