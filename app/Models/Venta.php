<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_dte',
        'folio_dte_sii',
        'user_id',
        'convenio_id',
        'rut_cliente',
        'subtotal',
        'descuento',
        'total',
        'metodo_pago',
        'tasa_transbank',
        'comision_transbank',
        'iva_comision',
        'abono_liquido',
        'codigo_autorizacion_tbk',
        'estado',
        'anulada',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'tasa_transbank' => 'decimal:2',
        'comision_transbank' => 'decimal:2',
        'iva_comision' => 'decimal:2',
        'abono_liquido' => 'decimal:2',
        'anulada' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function registrosControlados(): HasMany
    {
        return $this->hasMany(LibroControlado::class);
    }
}
