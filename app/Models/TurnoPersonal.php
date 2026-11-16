<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoPersonal extends Model
{
    use HasFactory;

    protected $table = 'turnos_personal';

    protected $fillable = [
        'user_id',
        'tipo_turno',
        'fecha_inicio',
        'fecha_fin',
        'hora_entrada',
        'hora_salida',
        'es_nocturno',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_nocturno' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
