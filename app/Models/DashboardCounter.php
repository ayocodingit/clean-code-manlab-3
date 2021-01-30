<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardCounter extends Model
{
    protected $table = 'dashboard_counter';

    protected $fillable = [
        'nama', 'total'
    ];
}
