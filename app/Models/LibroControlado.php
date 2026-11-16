<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibroControlado extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'medicamento_id',
        'lote_id',
        'rut_paciente',
        'nombre_paciente',
        'rut_medico',
        'nombre_medico',
        'numero_receta',
        'cantidad_dispensada',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
