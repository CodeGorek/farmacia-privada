<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Tabla pivote Vademécum: qué medicamentos están cubiertos por cada convenio
        Schema::create('convenio_medicamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convenio_id')->constrained('convenios')->cascadeOnDelete();
            $table->foreignId('medicamento_id')->constrained('medicamentos')->cascadeOnDelete();
            $table->decimal('porcentaje_descuento', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['convenio_id', 'medicamento_id']);
        });

        // 2. Padrón de afiliados por RUT para el Switch Aprobador de Convenios
        Schema::create('afiliados_convenio', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 20)->unique()->index();
            $table->string('nombre_afiliado', 150);
            $table->foreignId('convenio_id')->constrained('convenios')->cascadeOnDelete();
            $table->string('estado', 30)->default('ACTIVO'); // ACTIVO, SUSPENDIDO_MORA
            $table->timestamps();
        });

        // 3. Agregar rut_cliente a la tabla ventas para trazabilidad del beneficio
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('rut_cliente', 20)->nullable()->after('convenio_id');
        });
    }

    public function down(): void {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('rut_cliente');
        });
        Schema::dropIfExists('afiliados_convenio');
        Schema::dropIfExists('convenio_medicamento');
    }
};
