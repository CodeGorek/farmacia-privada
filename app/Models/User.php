<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'rut',
        'name',
        'email',
        'rol',
        'registro_isp',
        'telefono',
        'horario_habitual',
        'activo',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function getRolNombreAttribute(): string
    {
        return match ($this->rol) {
            'superadmin' => 'Super Administrador',
            'admin' => 'Administrador General',
            'qf' => 'Químico Farmacéutico (D.T.)',
            'vendedor' => 'Auxiliar / Vendedor de Farmacia',
            'bodeguero' => 'Encargado de Bodega FEFO',
            default => ucfirst($this->rol),
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->rol === 'superadmin';
    }

    public function isAdminOrSuper(): bool
    {
        return in_array($this->rol, ['admin', 'superadmin'], true);
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(TurnoPersonal::class);
    }

    public function ausencias(): HasMany
    {
        return $this->hasMany(AusenciaReemplazo::class, 'user_id');
    }

    public function reemplazosAsignados(): HasMany
    {
        return $this->hasMany(AusenciaReemplazo::class, 'reemplazante_id');
    }
}
