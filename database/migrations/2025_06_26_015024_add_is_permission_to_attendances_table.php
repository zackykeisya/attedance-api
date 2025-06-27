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
    $table->boolean('is_permission')->default(false)->after('clock_out');
    $table->string('permission_type')->nullable()->after('is_permission');
});

}

public function down()
{
    Schema::table('attendances', function (Blueprint $table) {
        $table->dropColumn('is_permission');
        $table->dropColumn('permission_type');
    });
}

};
