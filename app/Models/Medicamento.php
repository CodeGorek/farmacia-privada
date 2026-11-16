<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_barra',
        'nombre_comercial',
        'principio_activo',
        'laboratorio',
        'precio_unitario',
        'requiere_receta',
        'es_controlado',
        'stock_minimo',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'requiere_receta' => 'boolean',
        'es_controlado' => 'boolean',
        'stock_minimo' => 'integer',
    ];

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class)->orderBy('fecha_vencimiento', 'asc');
    }

    public function getStockTotalVitrinaAttribute(): int
    {
        return (int) $this->lotes->sum('stock_vitrina');
    }

    public function getStockTotalBodegaAttribute(): int
    {
        return (int) $this->lotes->sum('stock_bodega');
    }
}
