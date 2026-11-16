<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'audit_logs';

    protected $fillable = [
        'evento',
        'entidad',
        'entidad_id',
        'usuario_id',
        'ip',
        'payload',
        'fecha_evento',
    ];
}
