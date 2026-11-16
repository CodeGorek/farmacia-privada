<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraTemperatura extends Model
{
    use HasFactory;

    protected $table = 'bitacora_temperatura';

    protected $fillable = [
        'user_id',
        'jornada',
        'temp_sala',
        'humedad_sala',
        'temp_refrigerador',
        'conforme_isp',
        'observaciones',
    ];

    protected $casts = [
        'conforme_isp' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
