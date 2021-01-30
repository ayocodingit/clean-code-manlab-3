<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sampel extends Model
{
    use SoftDeletes;
    const NUMBER_FORMAT_RUJUKAN = '[rRaA]{1}[0-9]{1,}';
    const NUMBER_FORMAT_MANDIRI = '[lLbB]{1}[0-9]{1,}';
    const NUMBER_FORMAT_TES_MASIF = '[lLrRaAbB]{1}[0-9]{1,}';
    const NUMBER_FORMAT = '[lLrRaAbB]{1}[0-9]{1,}';

    protected $table = 'sampel';

    protected $appends = [
        'jenis_sampel',
        'kondisi_sampel',
    ];

    protected $fillable = [
        'nomor_register',
        'creator_user_id',
        'nomor_sampel',
        'jenis_sampel_id',
        'jenis_sampel_nama',
        'lab_pcr_id',
        'lab_pcr_nama',
        'waktu_pcr_sample_analyzed',
        'waktu_extraction_sample_extracted',
        'petugas_pengambilan_sampel', // Isinya adalah kondisi sampel, di aliaskan 'kondisi_sampel'
        'tanggal_pengambilan_sampel',
        'waktu_pengambilan_sampel',
        'waktu_sample_taken',
        'sampel_status',
        'register_id',
        'validator_id',
        'waktu_sample_verified',
        'waktu_sample_valid',
        'waktu_sample_invalid',
        'waktu_sample_print',
        'valid_file_id',
        'counter_print_hasil',
        'is_from_migration',
        'jam_pengambilan_sampel',
        'waktu_waiting_sample',
        'waktu_extraction_sample_reextract',
        'waktu_extraction_sample_sent',
        'waktu_pcr_sample_received',
        'waktu_sample_verified',
        'waktu_sample_valid'
    ];

    protected $dates = [
        'waktu_pcr_sample_received',
        'waktu_extraction_sample_extracted',
        'tanggal_pengambilan_sampel',
        'waktu_waiting_sample',
        'waktu_sample_taken',
        'waktu_extraction_sample_reextract',
        'waktu_extraction_sample_sent',
        'waktu_pcr_sample_analyzed',
        'waktu_sample_verified',
        'waktu_sample_valid',
        'waktu_sample_invalid',
        'waktu_sample_print',
        'waktu_inkonklusif',
        'waktu_swab_ulang'
    ];

    protected $casts = [
        'is_musnah_ekstraksi' => 'boolean',
        'is_musnah_pcr' => 'boolean',
    ];

    public function fasyankes()
    {
        return $this->belongsTo(Fasyankes::class);
    }

    public function status()
    {
        return $this->belongsTo(StatusSampel::class, 'sampel_status');
    }

    public function ekstraksi()
    {
        return $this->hasOne(Ekstraksi::class, 'sampel_id')->withDefault(['catatan_pengiriman' => null]);
    }

    public function pcr()
    {
        return $this->hasOne(PemeriksaanSampel::class, 'sampel_id')->withDefault(['kesimpulan_pemeriksaan' => null]);
    }

    public function logs()
    {
        return $this->hasMany(SampelLog::class)->orderBy('created_at', 'desc');
    }

    public function getJenisSampelAttribute()
    {
        return $this->jenis_sampel_nama;
    }

    public function updateState($newstate, $options = [], $tanggal = null)
    {
        $arr = array_merge($options, [
            'sampel_id' => $this->id,
            'sampel_status' => $newstate,
            'sampel_status_before' => $this->sampel_status,
        ]);
        $log = SampelLog::create($arr);
        if (empty($this->{'waktu_'.$newstate})) {
            $this->{'waktu_'.$newstate} = date('Y-m-d H:i:s');
            if ($tanggal != null && $newstate == 'pcr_sample_analyzed') {
                $this->{'waktu_'.$newstate} = $tanggal;
            }
        }
        $this->sampel_status = $newstate;
        $this->save();
    }

    public function addLog($options = [], $newstate = null)
    {
        if (!$newstate) {
            $newstate = $this->sampel_status;
        }
        $arr = array_merge($options, [
            'sampel_id' => $this->id,
            'sampel_status' => $newstate,
            'sampel_status_before' => $this->sampel_status,
        ]);
        $log = SampelLog::create($arr);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function pemeriksaanSampel()
    {
        return $this->hasOne(PemeriksaanSampel::class, 'sampel_id');
    }

    public function validator()
    {
        return $this->belongsTo(Validator::class);
    }

    public function validFile()
    {
        return $this->belongsTo(File::class, 'valid_file_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->is_musnah_ekstraksi)) {
                $model->is_musnah_ekstraksi = false;
            }
            if (empty($model->is_musnah_pcr)) {
                $model->is_musnah_pcr = false;
            }
        });
    }

    public function getKondisiSampelAttribute()
    {
        return  $this->is_from_migration && !$this->petugas_pengambilan_sampel ? 'Baik' : $this->petugas_pengambilan_sampel;
    }

    public function labPCR()
    {
        return $this->belongsTo(LabPCR::class, 'lab_pcr_id');
    }

    public function jenis_vtm()
    {
        return $this->belongsTo(JenisVTM::class, 'jenis_vtm_id');
    }
}
