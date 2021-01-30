<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Register extends Model
{
    use SoftDeletes;

    const RUMAH_SAKIT = 'rumah sakit';
    const DINKES = 'dinkes';
    const PUSKESMAS = 'puskesmas';
    const PENDAFTARAN = 'pendaftaran';

    public static $instansi_pengirim_types = [self::RUMAH_SAKIT, self::DINKES, self::PUSKESMAS];

    protected $table = 'register';

    protected $fillable = [
        'nomor_register',
        'fasyankes_id',
        'nomor_rekam_medis',
        'nama_dokter',
        'no_telp',
        'register_uuid',
        'creator_user_id',
        'sumber_pasien',
        'jenis_registrasi',
        'tanggal_kunjungan',
        'kunjungan_ke',
        'rs_kunjungan',
        'dinkes_pengirim',
        'other_dinas_pengirim',
        'nama_rs',
        'other_nama_rs',
        'fasyankes_pengirim',
        'hasil_rdt',
        'is_from_migration',
        'registration_code',
        'tes_masif_id'
        // 'keterangan_lain'
    ];

    protected $hidden = ['fasyankes_id'];

    public function fasyankes()
    {
        return $this->belongsTo(Fasyankes::class);
    }

    public function pasienRegister()
    {
        return $this->belongsTo(pasienRegister::class, 'id', 'register_id');
    }

    public function logs()
    {
        return $this->hasMany(RegisterLog::class, 'register_id', 'id');
    }

    public function tes_masif()
    {
        return $this->belongsTo(TesMasif::class);
    }
}
