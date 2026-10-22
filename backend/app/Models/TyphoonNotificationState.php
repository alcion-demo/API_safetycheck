<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'event_id',
    'region_name',
    'forecast_period',
    'latest_probability',
    'last_notified_probability',
    'last_notified_serial',
    'below_threshold',
])]
class TyphoonNotificationState extends Model
{
    protected $casts = [
        'latest_probability' => 'integer',
        'last_notified_probability' => 'integer',
        'below_threshold' => 'boolean',
    ];
}
