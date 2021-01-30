<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTablePasien extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->foreignId('kecamatan_id')->nullable(true)->constrained('kecamatan');
            $table->foreignId('kelurahan_id')->nullable(true)->constrained('kelurahan');
            $table->foreignId('provinsi_id')->nullable(true)->constrained('provinsi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropColumn('kecamatan_id');
            $table->dropColumn('kelurahan_id');
            $table->dropColumn('provinsi_id');
        });
    }
}
