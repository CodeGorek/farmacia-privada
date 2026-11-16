<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('libro_controlados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('medicamento_id')->constrained('medicamentos');
            $table->foreignId('lote_id')->constrained('lotes');
            $table->string('rut_paciente', 20);
            $table->string('nombre_paciente', 150);
            $table->string('rut_medico', 20);
            $table->string('nombre_medico', 150);
            $table->string('numero_receta', 50);
            $table->integer('cantidad_dispensada');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('libro_controlados');
    }
};
