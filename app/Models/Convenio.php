<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
