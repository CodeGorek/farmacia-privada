<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('convenio_id')->nullable()->constrained('convenios');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('metodo_pago', 40)->default('efectivo');
            $table->string('estado', 30)->default('completada');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ventas');
    }
};
