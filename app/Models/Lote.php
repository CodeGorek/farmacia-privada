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
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_elaboracion' => 'date',
        'stock_vitrina' => 'integer',
        'stock_bodega' => 'integer',
    ];

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }
}
