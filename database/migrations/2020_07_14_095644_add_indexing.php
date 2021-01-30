<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('register', function (Blueprint $table) {
            $table->index(['nomor_register']);
        });
        Schema::table('sampel', function (Blueprint $table) {
            $table->index(['nomor_sampel']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('register', function (Blueprint $table) {
            $table->dropIndex(['nomor_register']);
        });
        Schema::table('sampel', function (Blueprint $table) {
            $table->dropIndex(['nomor_sampel']);
        });
    }
}
