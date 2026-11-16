<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudVisacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_visacion';

    protected $fillable = [
        'vendedor_id',
        'qf_id',
        'estado',
        'tipo_receta',
        'rut_paciente',
        'nombre_paciente',
        'rut_medico',
        'nombre_medico',
        'numero_receta',
        'items_json',
        'monto_total',
        'observaciones_qf',
        'codigo_firma_qf',
        'aprobada_at',
    ];

    protected $casts = [
        'items_json' => 'array',
        'monto_total' => 'decimal:2',
        'aprobada_at' => 'datetime',
    ];

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function quimico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qf_id');
    }
}
