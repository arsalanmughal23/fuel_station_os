<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankCalibration extends Model
{
    protected $fillable = [
        'tank_id',
        'deep_cm',
        'volume_liters',
    ];

    protected $casts = [
        'deep_cm' => 'decimal:3',
        'volume_liters' => 'decimal:3',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}
