<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReagenEkstraksiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reagen_ekstraksi', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->enum('metode_ekstraksi', ['otomatis', 'manual'])->default('otomatis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reagen_ekstraksi');
    }
}
