<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReagenPCR extends Model
{
    protected $table = 'reagen_pcr';

    protected $fillable = ['nama', 'ct_normal'];

    protected $hidden = ['id', 'created_at', 'updated_at'];
}
