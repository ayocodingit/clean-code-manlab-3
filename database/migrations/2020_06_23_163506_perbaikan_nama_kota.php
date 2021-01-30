<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PerbaikanNamaKota extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::table('kota')->where('id','3204')->update(['nama'=>'KAB BANDUNG']);
        \DB::table('kota')->where('id','3212')->update(['nama'=>'KAB INDRAMAYU']);
        \DB::table('kota')->where('id','3203')->update(['nama'=>'KAB CIANJUR']);
        \DB::table('kota')->where('id','3205')->update(['nama'=>'KAB GARUT']);
        \DB::table('kota')->where('id','3277')->update(['nama'=>'KOTA CIMAHI']);
        \DB::table('kota')->where('id','3217')->update(['nama'=>'KAB BANDUNG BARAT']);
        \DB::table('kota')->where('id','3209')->update(['nama'=>'KAB CIREBON']);
        \DB::table('kota')->where('id','3211')->update(['nama'=>'KAB SUMEDANG']);
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
