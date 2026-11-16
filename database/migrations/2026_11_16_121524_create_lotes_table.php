<?php

// database/migrations/2026_11_16_121524_create_lotes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicamento_id')->constrained('medicamentos');
            $table->string('numero_lote', 30);
            $table->date('fecha_vencimiento')->index(); // Índice vital para FEFO
            $table->date('fecha_elaboracion');
            $table->integer('stock_vitrina')->default(0);
            $table->integer('stock_bodega')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lotes');
    }
};
