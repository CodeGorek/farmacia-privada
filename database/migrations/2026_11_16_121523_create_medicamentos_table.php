<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_barra', 50)->unique();
            $table->string('nombre_comercial', 150);
            $table->string('principio_activo', 150);
            $table->string('laboratorio', 100)->nullable();
            $table->decimal('precio_unitario', 10, 2);
            $table->boolean('requiere_receta')->default(false);
            $table->boolean('es_controlado')->default(false);
            $table->integer('stock_minimo')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('medicamentos');
    }
};
