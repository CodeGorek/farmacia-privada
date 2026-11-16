<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Farmacovigilancia extends Model
{
    use HasFactory;

    protected $table = 'farmacovigilancia_reclamos';

    protected $fillable = [
        'user_id',
        'tipo_registro',
        'rut_paciente',
        'nombre_paciente',
        'medicamento_id',
        'numero_lote',
        'descripcion_clinica',
        'severidad',
        'estado_reporte',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }
}
