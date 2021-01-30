<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewDurasiPeriksa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("DROP VIEW IF EXISTS durasi_lama_periksa");

        $sql = \DB::statement("CREATE VIEW durasi_lama_periksa AS 
        SELECT e.sampel_id, e.tanggal_mulai_ekstraksi,e.jam_mulai_ekstraksi,p.tanggal_input_hasil,p.jam_input_hasil,
        concat(e.tanggal_mulai_ekstraksi,' ',e.jam_mulai_ekstraksi,':00') AS extraksi_datetime,
        concat(p.tanggal_input_hasil,' ',p.jam_input_hasil,':00') AS input_hasil_datetime,
        extract (epoch from (concat(p.tanggal_input_hasil,' ',p.jam_input_hasil,':00')::timestamp - concat(e.tanggal_mulai_ekstraksi,' ',e.jam_mulai_ekstraksi,':00')::timestamp))::integer/60 as durasi_lama_pemeriksaan
        FROM ekstraksi AS e
        LEFT JOIN pemeriksaansampel as p
        ON e.sampel_id=p.sampel_id
        WHERE p.tanggal_input_hasil IS NOT NULL AND e.tanggal_mulai_ekstraksi IS NOT null");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('view_durasi_periksa');
    }
}
