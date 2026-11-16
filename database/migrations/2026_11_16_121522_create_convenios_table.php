<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('rut_institucion', 20)->nullable();
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('convenios');
    }
};
