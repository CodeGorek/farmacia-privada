<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'rut',
        'nombre_completo',
        'telefono',
        'email',
        'direccion',
        'convenio_id',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public static function normalizarRut(string $rut): string
    {
        return AfiliadoConvenio::normalizarRut($rut);
    }

    public static function formatearRut(string $rut): string
    {
        return AfiliadoConvenio::formatearRut(static::normalizarRut($rut));
    }
}
