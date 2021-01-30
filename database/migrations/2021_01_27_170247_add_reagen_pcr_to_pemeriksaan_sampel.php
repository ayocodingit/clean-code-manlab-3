<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReagenPcrToPemeriksaanSampel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemeriksaansampel', function (Blueprint $table) {
            $table->string('nama_reagen_pcr')->nullable();
            $table->integer('ct_normal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pemeriksaansampel', function (Blueprint $table) {
            $table->dropColumn('nama_reagen_pcr');
            $table->dropColumn('ct_normal');
        });
    }
}
