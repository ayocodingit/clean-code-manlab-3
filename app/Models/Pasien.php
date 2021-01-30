<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    const STATUSES = STATUSES;

    protected $appends = ['status_name'];

    protected $table = 'pasien';

    protected $fillable = [
        'nama_lengkap',
        'nik',
        'tanggal_lahir',
        'tempat_lahir',
        'kewarganegaraan',
        'no_hp',
        'no_telp',
        'pekerjaan',
        'jenis_kelamin',
        'provinsi_id',
        'kota_id',
        'kecamatan_id',
        'kelurahan_id',
        'kecamatan',
        'kelurahan',
        'no_rw',
        'no_rt',
        'alamat_lengkap',
        'keterangan_lain',
        'suhu',
        'usia_tahun',
        'usia_bulan',
        'sumber_pasien',
        'is_from_migration',
        'usia_bulan',
        'usia_tahun',
        'status',
        'pelaporan_id',
        'pelaporan_id_case'
    ];

    protected $dates = [
        // 'tanggal_lahir',
    ];

    protected $casts = [
        'tanggal_lahir'=> 'date:Y-m-d',
    ];

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class);
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function kelurahan()
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function getStatusNameAttribute()
    {
        return $this->status ? self::STATUSES[$this->status] : null;
    }


}
