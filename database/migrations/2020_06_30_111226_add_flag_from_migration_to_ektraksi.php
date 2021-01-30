<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlagFromMigrationToEktraksi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ekstraksi', function (Blueprint $table) {
            $table->boolean('is_from_migration')->default(0);
        });

        Schema::table('pengambilan_sampel', function (Blueprint $table) {
            $table->boolean('is_from_migration')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ektraksi', function (Blueprint $table) {
            //
        });
    }
}
