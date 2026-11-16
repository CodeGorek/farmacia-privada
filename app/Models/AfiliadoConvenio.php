<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfiliadoConvenio extends Model
{
    use HasFactory;

    protected $table = 'afiliados_convenio';

    protected $fillable = [
        'rut',
        'nombre_afiliado',
        'convenio_id',
        'estado',
    ];

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    /**
     * Normaliza un RUT chileno al formato sin puntos y con guión en mayúscula (ej. 12345678-5)
     */
    public static function normalizarRut(string $rut): string
    {
        $limpio = preg_replace('/[^0-9kK]/', '', strtoupper(trim($rut)));
        if (strlen($limpio) < 2) {
            return $limpio;
        }
        $cuerpo = substr($limpio, 0, -1);
        $dv = substr($limpio, -1);
        return ltrim($cuerpo, '0') . '-' . $dv;
    }

    /**
     * Formatea un RUT normalizado con puntos y guión (ej. 12.345.678-5)
     */
    public static function formatearRut(string $rut): string
    {
        $norm = self::normalizarRut($rut);
        if (!str_contains($norm, '-')) {
            return $rut;
        }
        [$cuerpo, $dv] = explode('-', $norm);
        return number_format((int) $cuerpo, 0, '', '.') . '-' . $dv;
    }
}
