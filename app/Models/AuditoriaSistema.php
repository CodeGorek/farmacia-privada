<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaSistema extends Model
{
    use HasFactory;

    protected $table = 'auditorias_sistema';

    protected $fillable = [
        'user_id',
        'usuario_nombre',
        'usuario_rol',
        'accion',
        'modulo',
        'detalle',
        'ip',
        'navegador',
        'sistema_operativo',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function detectarNavegador(?string $ua): string
    {
        if (!$ua) {
            return 'Desconocido';
        }
        if (str_contains($ua, 'Edg/')) return 'Microsoft Edge';
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) return 'Opera';
        if (str_contains($ua, 'Chrome/') && !str_contains($ua, 'Edg/')) return 'Google Chrome';
        if (str_contains($ua, 'Firefox/')) return 'Mozilla Firefox';
        if (str_contains($ua, 'Safari/') && !str_contains($ua, 'Chrome/')) return 'Safari';
        if (str_contains($ua, 'curl')) return 'cURL / CLI';
        return 'Otro Navegador';
    }

    public static function detectarSO(?string $ua): string
    {
        if (!$ua) {
            return 'Desconocido';
        }
        if (preg_match('/Windows NT 10\.0/i', $ua)) return 'Windows 10/11';
        if (preg_match('/Windows/i', $ua)) return 'Windows';
        if (preg_match('/Android/i', $ua)) return 'Android';
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) return 'iOS / iPadOS';
        if (preg_match('/Macintosh|Mac OS X/i', $ua)) return 'macOS';
        if (preg_match('/Ubuntu|Linux/i', $ua)) return 'Linux';
        return 'Otro SO';
    }

    public static function detectarDispositivo(?string $ua): string
    {
        if (!$ua) {
            return 'Escritorio (PC)';
        }
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(android(?!.*mobile))/i', $ua)) {
            return 'Tablet';
        }
        if (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry/i', $ua)) {
            return 'Móvil';
        }
        return 'Escritorio (PC)';
    }

    public function getTipoDispositivoAttribute(): string
    {
        return static::detectarDispositivo($this->user_agent);
    }

    public static function registrar(string $accion, string $modulo, string $detalle, ?User $user = null): void
    {
        try {
            $actor = $user ?? auth()->user();
            $req = request();
            $ua = $req ? $req->userAgent() : null;

            static::create([
                'user_id' => $actor?->id,
                'usuario_nombre' => $actor?->name ?? 'Sistema / Invitado',
                'usuario_rol' => $actor?->rol ?? 'invitado',
                'accion' => strtoupper($accion),
                'modulo' => $modulo,
                'detalle' => $detalle,
                'ip' => $req ? $req->ip() : '127.0.0.1',
                'navegador' => static::detectarNavegador($ua),
                'sistema_operativo' => static::detectarSO($ua),
                'user_agent' => $ua ? substr($ua, 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            // No interrumpir flujo operativo si falla el log de auditoría
        }
    }
}
