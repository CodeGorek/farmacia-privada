<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Extender medicamentos con Bioequivalencia ISP, Ley CENABAST, Costo y Cadena de Frío
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->boolean('es_bioequivalente')->default(false)->after('es_controlado');
            $table->boolean('es_cenabast')->default(false)->after('es_bioequivalente');
            $table->decimal('precio_maximo_cenabast', 10, 2)->nullable()->after('es_cenabast');
            $table->decimal('costo_neto_referencia', 10, 2)->default(0)->after('precio_maximo_cenabast');
            $table->boolean('requiere_cadena_frio')->default(false)->after('costo_neto_referencia');
        });

        // 2. Extender lotes con Cuarentena/Merma, Recall ISP y Costo de Compra
        Schema::table('lotes', function (Blueprint $table) {
            $table->decimal('costo_unitario_compra', 10, 2)->default(0)->after('stock_bodega');
            $table->integer('stock_cuarentena')->default(0)->after('costo_unitario_compra');
            $table->boolean('bloqueado_recall_isp')->default(false)->after('stock_cuarentena');
            $table->string('motivo_bloqueo', 180)->nullable()->after('bloqueado_recall_isp');
        });

        // 3. Extender ventas con Boleta Electrónica SII (DTE 39/33/61) y estado de anulación
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('tipo_dte', 35)->default('BOLETA_ELECTRONICA_39')->after('id');
            $table->string('folio_dte_sii', 40)->nullable()->after('tipo_dte');
            $table->boolean('anulada')->default(false)->after('estado');
        });

        // 4. Tabla de Apertura, Arqueo y Cierre de Caja por Turno (Reporte Z)
        Schema::create('aperturas_cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 20)->default('ABIERTA'); // ABIERTA, CERRADA
            $table->decimal('fondo_fijo_inicial', 12, 2)->default(50000);
            $table->decimal('ventas_efectivo_sistema', 12, 2)->default(0);
            $table->decimal('ventas_tbk_debito', 12, 2)->default(0);
            $table->decimal('ventas_tbk_credito', 12, 2)->default(0);
            $table->decimal('total_descuentos_convenio', 12, 2)->default(0);
            $table->decimal('efectivo_fisico_declarado', 12, 2)->nullable();
            $table->decimal('diferencia_arqueo', 12, 2)->default(0);
            $table->timestamp('apertura_at')->nullable();
            $table->timestamp('cierre_at')->nullable();
            $table->string('observaciones', 220)->nullable();
            $table->timestamps();
        });

        // 5. Tabla de Bitácora Diaria de Temperatura y Cadena de Frío ISP (DS 466)
        Schema::create('bitacora_temperatura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jornada', 30)->default('MANANA_09H'); // MANANA_09H, TARDE_17H
            $table->decimal('temp_sala', 4, 1)->default(21.4); // Rango ISP: 18°C a 25°C
            $table->integer('humedad_sala')->default(48);
            $table->decimal('temp_refrigerador', 4, 1)->default(4.2); // Rango ISP: 2°C a 8°C
            $table->boolean('conforme_isp')->default(true);
            $table->string('observaciones', 200)->nullable();
            $table->timestamps();
        });

        // 6. Tabla de Abastecimiento: Órdenes de Compra a Droguerías / CENABAST
        Schema::create('proveedores_ordenes', function (Blueprint $table) {
            $table->id();
            $table->string('folio_oc', 40)->unique();
            $table->string('proveedor', 100); // CENABAST, Droguería Socofar, Lab. Chile, Lab. Saval
            $table->foreignId('medicamento_id')->constrained('medicamentos')->cascadeOnDelete();
            $table->integer('cantidad_solicitada');
            $table->decimal('costo_unitario_neto', 10, 2);
            $table->decimal('total_neto', 12, 2);
            $table->string('estado', 30)->default('EMITIDA'); // EMITIDA, RECEPCIONADA
            $table->timestamps();
        });

        // 7. Tabla de Farmacovigilancia (RAM ISP) y Libro Digital de Reclamos SEREMI
        Schema::create('farmacovigilancia_reclamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_registro', 35)->default('RAM_ISP'); // RAM_ISP, RECLAMO_SEREMI, SUGERENCIA
            $table->string('rut_paciente', 20);
            $table->string('nombre_paciente', 150);
            $table->foreignId('medicamento_id')->nullable()->constrained('medicamentos')->nullOnDelete();
            $table->string('numero_lote', 30)->nullable();
            $table->text('descripcion_clinica');
            $table->string('severidad', 25)->default('MODERADA'); // LEVE, MODERADA, GRAVE
            $table->string('estado_reporte', 30)->default('REPORTADO_ISP');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('farmacovigilancia_reclamos');
        Schema::dropIfExists('proveedores_ordenes');
        Schema::dropIfExists('bitacora_temperatura');
        Schema::dropIfExists('aperturas_cierres_caja');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['tipo_dte', 'folio_dte_sii', 'anulada']);
        });
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn(['costo_unitario_compra', 'stock_cuarentena', 'bloqueado_recall_isp', 'motivo_bloqueo']);
        });
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->dropColumn(['es_bioequivalente', 'es_cenabast', 'precio_maximo_cenabast', 'costo_neto_referencia', 'requiere_cadena_frio']);
        });
    }
};
