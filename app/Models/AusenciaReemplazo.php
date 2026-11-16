<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AusenciaReemplazo extends Model
{
    use HasFactory;

    protected $table = 'ausencias_reemplazos';

    protected $fillable = [
        'user_id',
        'reemplazante_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'motivo_folio',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function titular(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reemplazante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reemplazante_id');
    }
}
