<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlagImportData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->boolean('is_from_migration')->default(0);
        });

        Schema::table('register', function (Blueprint $table) {
            $table->boolean('is_from_migration')->default(0);
        });

        Schema::table('pasien_register', function (Blueprint $table) {
            $table->boolean('is_from_migration')->default(0);
        });

        Schema::table('sampel', function (Blueprint $table) {
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
        //
    }
}
