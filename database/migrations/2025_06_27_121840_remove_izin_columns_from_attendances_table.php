<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveIzinColumnsFromAttendancesTable extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('izin_jenis');
            $table->dropColumn('izin_keterangan');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('izin_jenis')->nullable();
            $table->text('izin_keterangan')->nullable();
        });
    }
}
