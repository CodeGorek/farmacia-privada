<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'proveedores_ordenes';

    protected $fillable = [
        'folio_oc',
        'proveedor',
        'medicamento_id',
        'cantidad_solicitada',
        'costo_unitario_neto',
        'total_neto',
        'estado',
    ];

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }
}
