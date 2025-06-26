<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('permissions', function (Blueprint $table) {
    $table->uuid('id')->primary(); // Jika ingin menggunakan UUID sebagai primary key
    $table->uuid('user_id'); // Sesuaikan dengan tipe user.id
    $table->date('date');
    $table->enum('type', ['sick', 'leave', 'other']);
    $table->text('description');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->timestamps();


    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade');

    $table->unique(['user_id', 'date']);
});
}


    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};