<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Extender tabla users con rol farmacéutico, RUT, registro ISP y estado activo
        Schema::table('users', function (Blueprint $table) {
            $table->string('rut', 20)->nullable()->unique()->after('id');
            $table->string('rol', 30)->default('vendedor')->after('email'); // admin, qf, vendedor, bodeguero
            $table->string('registro_isp', 40)->nullable()->after('rol'); // N° Registro Superintendencia de Salud / ISP
            $table->string('telefono', 30)->nullable()->after('registro_isp');
            $table->string('horario_habitual', 100)->default('Lunes a Viernes 08:30 - 17:30')->after('telefono');
            $table->boolean('activo')->default(true)->after('horario_habitual');
        });

        // 2. Tabla de Turnos (Diurnos, Tarde, Turnos Nocturnos de Guardia 24h)
        Schema::create('turnos_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo_turno', 40); // DIURNO_MANANA, DIURNO_TARDE, NOCTURNO_GUARDIA, ROTATIVO
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('hora_entrada', 10)->default('08:00');
            $table->string('hora_salida', 10)->default('16:00');
            $table->boolean('es_nocturno')->default(false);
            $table->string('observaciones', 200)->nullable();
            $table->timestamps();
        });

        // 3. Tabla de Licencias Médicas, Días Libres, Vacaciones y Reemplazos con Fechas
        Schema::create('ausencias_reemplazos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reemplazante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 40); // LICENCIA_MEDICA, DIA_LIBRE, VACACIONES, PERMISO
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo_folio', 150)->nullable();
            $table->string('estado', 30)->default('VIGENTE');
            $table->timestamps();
        });

        // 4. Tabla de Solicitudes de Visación de Recetas (Vendedor -> Químico Farmacéutico)
        Schema::create('solicitudes_visacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('qf_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 30)->default('PENDIENTE'); // PENDIENTE, APROBADA, RECHAZADA, PROCESADA
            $table->string('tipo_receta', 40)->default('RECETA_RETENIDA'); // RECETA_RETENIDA, RECETA_CHEQUE, ANTIBIOTICO
            $table->string('rut_paciente', 20);
            $table->string('nombre_paciente', 150);
            $table->string('rut_medico', 20);
            $table->string('nombre_medico', 150);
            $table->string('numero_receta', 50);
            $table->json('items_json');
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->string('observaciones_qf', 250)->nullable();
            $table->string('codigo_firma_qf', 40)->nullable();
            $table->timestamp('aprobada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('solicitudes_visacion');
        Schema::dropIfExists('ausencias_reemplazos');
        Schema::dropIfExists('turnos_personal');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rut', 'rol', 'registro_isp', 'telefono', 'horario_habitual', 'activo']);
        });
    }
};
