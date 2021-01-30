<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TesMasif extends Model
{
    protected $table = 'tes_masif';

    protected $fillable = [
        "registration_code",
        "jenis_registrasi",
        "nama_pasien",
        "nomor_sampel",
        "fasyankes_id",
        "kewarganeraan",
        "kategori",
        "kriteria",
        "nik",
        "tempat_lahir",
        "tanggal_lahir",
        "jenis_kelamin",
        "provinsi_id",
        "kota_id",
        "kecamatan_id",
        "kelurahan_id",
        "alamat",
        "rt",
        "rw",
        "no_hp",
        "suhu",
        "keterangan",
        "hasil_rdt",
        "usia_tahun",
        "usia_bulan",
        "dokter",
        "telp_fasyankes",
        "kunjungan",
        "tanggal_kunjungan",
        "rs_kunjungan",
    ];

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }

    public function fasyankes()
    {
        return $this->belongsTo(Fasyankes::class);
    }

    public function register()
    {
        return $this->hasMany(Register::class);
    }

    public function setProvinsiIdAttribute($value)
    {
        $this->attributes['provinsi_id'] = getConvertCodeDagri($value);
    }

    public function setKotaIdAttribute($value)
    {
        $this->attributes['kota_id'] = getConvertCodeDagri($value);
    }

    public function setKecamatanIdAttribute($value)
    {
        $this->attributes['kecamatan_id'] = getConvertCodeDagri($value);
    }

    public function setKelurahanIdAttribute($value)
    {
        $this->attributes['kelurahan_id'] = getConvertCodeDagri($value);
    }

    public function setKriteriaAttribute($value)
    {
        $this->attributes['kriteria'] = $value ? array_search(strtolower($value), array_map('strtolower', Pasien::STATUSES)) : defaultStatus;
    }


}
