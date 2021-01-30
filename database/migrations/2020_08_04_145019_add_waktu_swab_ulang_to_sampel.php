<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWaktuSwabUlangToSampel extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sampel', function (Blueprint $table) {
            $table->datetime('waktu_swab_ulang')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sampel', function (Blueprint $table) {
            $table->dropColumn('waktu_swab_ulang');
        });
    }
}
