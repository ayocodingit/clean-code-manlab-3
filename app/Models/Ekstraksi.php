<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ekstraksi extends Model
{
    protected $table = 'ekstraksi';

    protected $fillable = [
        'sampel_id',
        'user_id',
        'tanggal_mulai_ekstraksi',
        'tanggal_penerimaan_sampel',
        'is_from_migration',
        'petugas_penerima_sampel',
        'jam_penerimaan_sampel',
        'catatan_penerimaan',
        'operator_ekstraksi',
        'jam_mulai_ekstraksi',
        'metode_ekstraksi',
        'nama_kit_ekstraksi',
        'alat_ekstraksi',
        'catatan_pengiriman',
        'nama_pengirim_rna',
        'jam_pengiriman_rna',
        'tanggal_pengiriman_rna',
    ];

    protected $dates = [
        'tanggal_penerimaan_sampel',
        'tanggal_mulai_ekstraksi',
        'tanggal_pengiriman_rna',
    ];
}
