<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicamento_id',
        'numero_lote',
        'fecha_vencimiento',
        'fecha_elaboracion',
        'stock_vitrina',
        'stock_bodega',
        'descuento_porcentaje',
        'motivo_descuento',
        'promo_2x1',
        'costo_unitario_compra',
        'stock_cuarentena',
        'bloqueado_recall_isp',
        'motivo_bloqueo',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_elaboracion' => 'date',
        'stock_vitrina' => 'integer',
        'stock_bodega' => 'integer',
        'descuento_porcentaje' => 'decimal:2',
        'promo_2x1' => 'boolean',
        'costo_unitario_compra' => 'decimal:2',
        'stock_cuarentena' => 'integer',
        'bloqueado_recall_isp' => 'boolean',
    ];

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }
}
