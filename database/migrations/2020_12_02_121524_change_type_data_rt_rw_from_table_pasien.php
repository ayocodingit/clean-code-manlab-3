<?php

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\ChangeColumn;
use Illuminate\Support\Facades\Schema;

class ChangeTypeDataRtRwFromTablePasien extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            ALTER TABLE pasien  
            ALTER COLUMN no_rt type varchar(3), 
            ALTER COLUMN no_rw type varchar(3) 
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Type::hasType('char')) {
            Type::addType('char', StringType::class);
        }
        Schema::table('pasien', function (Blueprint $table) {
            $table->char('no_rt', 3)->nullable()->change();
            $table->char('no_rw', 3)->nullable()->change();
        });
    }
}
