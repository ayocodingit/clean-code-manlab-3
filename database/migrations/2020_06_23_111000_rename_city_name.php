<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameCityName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::table('kota')->where('id','3210')->update(['nama'=>'KAB MAJALENGKA']);
        \DB::table('kota')->where('id','3214')->update(['nama'=>'KAB PURWAKARTA']);
        \DB::table('kota')->where('id','3206')->update(['nama'=>'KAB TASIKMALAYA']);
        \DB::table('kota')->where('id','3202')->update(['nama'=>'KAB SUKABUMI']);
        \DB::table('kota')->where('id','3208')->update(['nama'=>'KAB KUNINGAN']);
        \DB::table('kota')->where('id','3218')->update(['nama'=>'KAB PANGANDARAN']);
        \DB::table('kota')->where('id','3215')->update(['nama'=>'KAB KARAWANG']);
        \DB::table('kota')->where('id','3201')->update(['nama'=>'KAB BOGOR']);
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
