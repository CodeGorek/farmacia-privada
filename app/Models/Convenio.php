<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convenio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'rut_institucion',
        'porcentaje_descuento',
        'activo',
    ];

    protected $casts = [
        'porcentaje_descuento' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function afiliados(): HasMany
    {
        return $this->hasMany(AfiliadoConvenio::class);
    }

    public function medicamentos(): BelongsToMany
    {
        return $this->belongsToMany(Medicamento::class, 'convenio_medicamento')
            ->withPivot('porcentaje_descuento')
            ->withTimestamps();
    }
}
