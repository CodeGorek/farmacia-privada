<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Extender medicamentos para distinguir productos no farmacéuticos y su categoría
        Schema::table('medicamentos', function (Blueprint $table) {
            if (!Schema::hasColumn('medicamentos', 'es_medicamento')) {
                $table->boolean('es_medicamento')->default(true)->after('laboratorio');
            }
            if (!Schema::hasColumn('medicamentos', 'categoria_producto')) {
                $table->string('categoria_producto', 60)->default('Medicamento')->after('es_medicamento');
            }
        });

        // 2. Tabla relacional de Auditoría del Sistema (IP, navegador, sistema operativo)
        if (!Schema::hasTable('auditorias_sistema')) {
            Schema::create('auditorias_sistema', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('usuario_nombre', 150)->nullable();
                $table->string('usuario_rol', 40)->nullable();
                $table->string('accion', 60);
                $table->string('modulo', 60);
                $table->text('detalle')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('navegador', 100)->nullable();
                $table->string('sistema_operativo', 100)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

        // 3. Tabla de Clientes registrados en la farmacia
        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->id();
                $table->string('rut', 20)->unique();
                $table->string('nombre_completo', 150);
                $table->string('telefono', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('direccion', 200)->nullable();
                $table->foreignId('convenio_id')->nullable()->constrained('convenios')->nullOnDelete();
                $table->string('observaciones', 250)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('auditorias_sistema');

        Schema::table('medicamentos', function (Blueprint $table) {
            if (Schema::hasColumn('medicamentos', 'categoria_producto')) {
                $table->dropColumn('categoria_producto');
            }
            if (Schema::hasColumn('medicamentos', 'es_medicamento')) {
                $table->dropColumn('es_medicamento');
            }
        });
    }
};
