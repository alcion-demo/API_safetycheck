<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'area_code',
    'warning_type',
    'area_name',
    'warning_name',
    'is_active',
    'active_disaster_key',
])]
class WeatherWarningState extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
