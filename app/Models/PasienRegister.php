<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PasienRegister extends Pivot
{
    protected $table = 'pasien_register';

    protected $fillable = [
        'pasien_id',
        'register_id',
        'is_from_migration',
    ];

    public $timestamps = false;

    public function pasien()
    {
        return $this->belongsTo(Pasien::class);
    }
}
