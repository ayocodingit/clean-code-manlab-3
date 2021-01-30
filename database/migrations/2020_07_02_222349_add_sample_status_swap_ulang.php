<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSampleStatusSwapUlang extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::table('status_sampel')->insert(['sampel_status'=>'swap_ulang','deskripsi'=>'swap_ulang','chamber'=>'verifikasi']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::table('status_sampel')->where('sampel_status','swap_ulang')->delete();
    }
}
