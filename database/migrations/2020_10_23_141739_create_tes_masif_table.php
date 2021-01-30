<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTesMasifTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tes_masif', function (Blueprint $table) {
            $table->id();
            $table->string('registration_code');
            $table->string('nama_pasien');
            $table->enum('jenis_registrasi', ['mandiri', 'tes-masif']);
            $table->string('nomor_sampel');
            $table->integer('fasyankes_id');
            $table->string('kewarganeraan')->nullable();
            $table->string('kategori')->nullable();
            $table->integer('kriteria')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->char('jenis_kelamin', 1)->nullable();
            $table->integer('provinsi_id')->nullable();
            $table->integer('kota_id')->nullable();
            $table->integer('kecamatan_id')->nullable();
            $table->bigInteger('kelurahan_id')->nullable();
            $table->string('alamat')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('no_hp')->nullable();
            $table->float('suhu')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('hasil_rdt')->nullable();
            $table->integer('usia_tahun')->nullable();
            $table->integer('usia_bulan')->nullable();
            $table->string('dokter')->nullable();
            $table->string('telp_fasyankes')->nullable();
            $table->integer('kunjungan')->nullable();
            $table->date('tanggal_kunjungan')->nullable();
            $table->string('rs_kunjungan')->nullable();
            $table->boolean('available')->default(true);
            $table->index(['tanggal_kunjungan', 'nomor_sampel', 'kategori']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_masif');
    }
}
