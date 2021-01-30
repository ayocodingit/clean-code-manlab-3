<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewDurasiPeriksaSampel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("DROP VIEW IF EXISTS durasi_lama_periksa");
        \DB::statement("DROP VIEW IF EXISTS view_durasi_periksa_sampel");

        \DB::statement("create view view_durasi_periksa_sampel as select s.sampel_id,e.tanggal_mulai_ekstraksi,s.tanggal_input_hasil,
        (s.tanggal_input_hasil-e.tanggal_mulai_ekstraksi) as durasi_periksa_sampel
        from ekstraksi as e
        left join pemeriksaansampel as s on s.id=e.sampel_id
        where e.tanggal_mulai_ekstraksi is not null and s.tanggal_input_hasil is not null");
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
