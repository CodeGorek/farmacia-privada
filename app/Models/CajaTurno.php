<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaTurno extends Model
{
    use HasFactory;

    protected $table = 'aperturas_cierres_caja';

    protected $fillable = [
        'user_id',
        'estado',
        'fondo_fijo_inicial',
        'ventas_efectivo_sistema',
        'ventas_tbk_debito',
        'ventas_tbk_credito',
        'total_descuentos_convenio',
        'efectivo_fisico_declarado',
        'diferencia_arqueo',
        'apertura_at',
        'cierre_at',
        'observaciones',
    ];

    protected $casts = [
        'apertura_at' => 'datetime',
        'cierre_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
