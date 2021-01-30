<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardChart extends Model
{
    protected $table = 'dashboard_chart';

    protected $guarded = [
        'id', 'created_at', 'updated_at'
    ];
}
