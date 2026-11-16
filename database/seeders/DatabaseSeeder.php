<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Convenio;
use App\Models\AfiliadoConvenio;
use App\Models\Medicamento;
use App\Models\Lote;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\LibroControlado;
use App\Http\Controllers\PosController;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Rol::insert([
            ['nombre' => 'Químico Farmacéutico', 'descripcion' => 'Dirección técnica y control de estupefacientes', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Auxiliar de Farmacia', 'descripcion' => 'Dispensación en punto de venta (POS)', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Bodeguero', 'descripcion' => 'Recepción de lotes y control FEFO', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $convAdulto = Convenio::create([
            'nombre' => 'Convenio Adulto Mayor / FONASA',
            'rut_institucion' => '69.000.100-1',
            'porcentaje_descuento' => 15.00,
            'activo' => true,
        ]);

        $convIsapre = Convenio::create([
            'nombre' => 'Isapre SaludVida',
            'rut_institucion' => '96.500.200-K',
            'porcentaje_descuento' => 20.00,
            'activo' => true,
        ]);

        $paracetamol = Medicamento::create([
            'codigo_barra' => '780123456001',
            'nombre_comercial' => 'Paracetamol 500mg Caja x16',
            'principio_activo' => 'Paracetamol',
            'laboratorio' => 'Laboratorio Chile',
            'precio_unitario' => 1990,
            'requiere_receta' => false,
            'es_controlado' => false,
            'stock_minimo' => 20,
        ]);

        $lotePar26 = Lote::create([
            'medicamento_id' => $paracetamol->id,
            'numero_lote' => 'LOT-PAR-2026A',
            'fecha_elaboracion' => '2025-01-10',
            'fecha_vencimiento' => now()->addDays(22)->format('Y-m-d'),
            'stock_vitrina' => 14,
            'stock_bodega' => 40,
        ]);

        $lotePar27 = Lote::create([
            'medicamento_id' => $paracetamol->id,
            'numero_lote' => 'LOT-PAR-2027B',
            'fecha_elaboracion' => '2025-06-01',
            'fecha_vencimiento' => now()->addMonths(8)->format('Y-m-d'),
            'stock_vitrina' => 25,
            'stock_bodega' => 80,
        ]);

        $clonazepam = Medicamento::create([
            'codigo_barra' => '780123456002',
            'nombre_comercial' => 'Clonazepam 2mg Caja x30',
            'principio_activo' => 'Clonazepam',
            'laboratorio' => 'Laboratorio Saval',
            'precio_unitario' => 6490,
            'requiere_receta' => true,
            'es_controlado' => true,
            'stock_minimo' => 10,
        ]);

        $loteClo = Lote::create([
            'medicamento_id' => $clonazepam->id,
            'numero_lote' => 'LOT-CLO-2027A',
            'fecha_elaboracion' => '2025-03-15',
            'fecha_vencimiento' => now()->addDays(58)->format('Y-m-d'),
            'stock_vitrina' => 15,
            'stock_bodega' => 50,
        ]);

        $amoxicilina = Medicamento::create([
            'codigo_barra' => '780123456003',
            'nombre_comercial' => 'Amoxicilina 500mg Caja x21',
            'principio_activo' => 'Amoxicilina Trihidrato',
            'laboratorio' => 'Mintlab',
            'precio_unitario' => 4290,
            'requiere_receta' => true,
            'es_controlado' => false,
            'stock_minimo' => 15,
        ]);

        $loteAmox = Lote::create([
            'medicamento_id' => $amoxicilina->id,
            'numero_lote' => 'LOT-AMX-2026C',
            'fecha_elaboracion' => '2025-04-10',
            'fecha_vencimiento' => now()->addDays(18)->format('Y-m-d'),
            'stock_vitrina' => 8,
            'stock_bodega' => 30,
        ]);

        $losartan = Medicamento::create([
            'codigo_barra' => '780123456004',
            'nombre_comercial' => 'Losartán Potásico 50mg Caja x30',
            'principio_activo' => 'Losartán',
            'laboratorio' => 'Bagó',
            'precio_unitario' => 5490,
            'requiere_receta' => true,
            'es_controlado' => false,
            'stock_minimo' => 25,
        ]);

        $loteLos = Lote::create([
            'medicamento_id' => $losartan->id,
            'numero_lote' => 'LOT-LOS-2028A',
            'fecha_elaboracion' => '2025-08-01',
            'fecha_vencimiento' => now()->addMonths(16)->format('Y-m-d'),
            'stock_vitrina' => 42,
            'stock_bodega' => 120,
        ]);

        // =========================================================================
        // VADEMÉCUM DE PRODUCTOS ASOCIADOS A CADA CONVENIO (Cobertura Selectiva)
        // =========================================================================
        // 1. Isapre SaludVida: Cubre Clonazepam (25%), Amoxicilina (20%) y Losartán (20%). NO cubre Paracetamol.
        $convIsapre->medicamentos()->attach([
            $clonazepam->id => ['porcentaje_descuento' => 25.00],
            $amoxicilina->id => ['porcentaje_descuento' => 20.00],
            $losartan->id => ['porcentaje_descuento' => 20.00],
        ]);

        // 2. Convenio Adulto Mayor / FONASA: Cubre Paracetamol (15%) y Losartán Crónico (30%). NO cubre Amoxicilina ni Clonazepam.
        $convAdulto->medicamentos()->attach([
            $paracetamol->id => ['porcentaje_descuento' => 15.00],
            $losartan->id => ['porcentaje_descuento' => 30.00],
        ]);

        // =========================================================================
        // PADRÓN DE AFILIADOS POR RUT (Para Switch Aprobador en Línea)
        // =========================================================================
        AfiliadoConvenio::create([
            'rut' => '12345678-5',
            'nombre_afiliado' => 'María Alejandra González',
            'convenio_id' => $convIsapre->id,
            'estado' => 'ACTIVO',
        ]);

        AfiliadoConvenio::create([
            'rut' => '9876543-3',
            'nombre_afiliado' => 'Jorge Eduardo Ramírez',
            'convenio_id' => $convAdulto->id,
            'estado' => 'ACTIVO',
        ]);

        AfiliadoConvenio::create([
            'rut' => '15222333-4',
            'nombre_afiliado' => 'Pedro Soto Morales',
            'convenio_id' => $convIsapre->id,
            'estado' => 'SUSPENDIDO_MORA',
        ]);

        // Ventas iniciales
        $tbk1 = PosController::calcularTarifaTransbank(23880, 'efectivo');
        $v1 = Venta::create([
            'convenio_id' => null,
            'rut_cliente' => null,
            'subtotal' => 23880,
            'descuento' => 0,
            'total' => 23880,
            'metodo_pago' => 'efectivo',
            'tasa_transbank' => $tbk1['tasa_transbank'],
            'comision_transbank' => $tbk1['comision_transbank'],
            'iva_comision' => $tbk1['iva_comision'],
            'abono_liquido' => $tbk1['abono_liquido'],
            'codigo_autorizacion_tbk' => $tbk1['codigo_autorizacion_tbk'],
            'estado' => 'completada',
        ]);
        DetalleVenta::create([
            'venta_id' => $v1->id,
            'medicamento_id' => $paracetamol->id,
            'lote_id' => $lotePar26->id,
            'numero_lote' => $lotePar26->numero_lote,
            'cantidad' => 12,
            'precio_unitario' => 1990,
            'subtotal' => 23880,
        ]);

        $tbk2 = PosController::calcularTarifaTransbank(9735, 'debito');
        $v2 = Venta::create([
            'convenio_id' => $convIsapre->id,
            'rut_cliente' => '12.345.678-5',
            'subtotal' => 12980,
            'descuento' => 3245,
            'total' => 9735,
            'metodo_pago' => 'debito',
            'tasa_transbank' => $tbk2['tasa_transbank'],
            'comision_transbank' => $tbk2['comision_transbank'],
            'iva_comision' => $tbk2['iva_comision'],
            'abono_liquido' => $tbk2['abono_liquido'],
            'codigo_autorizacion_tbk' => $tbk2['codigo_autorizacion_tbk'],
            'estado' => 'completada',
        ]);
        DetalleVenta::create([
            'venta_id' => $v2->id,
            'medicamento_id' => $clonazepam->id,
            'lote_id' => $loteClo->id,
            'numero_lote' => $loteClo->numero_lote,
            'cantidad' => 2,
            'precio_unitario' => 6490,
            'subtotal' => 12980,
        ]);
        LibroControlado::create([
            'venta_id' => $v2->id,
            'medicamento_id' => $clonazepam->id,
            'lote_id' => $loteClo->id,
            'rut_paciente' => '12.345.678-5',
            'nombre_paciente' => 'María Alejandra González',
            'rut_medico' => '9.412.880-2',
            'nombre_medico' => 'Dr. Roberto Valdés (Psiquiatría)',
            'numero_receta' => 'REC-CHQ-00481',
            'cantidad_dispensada' => 2,
        ]);

        $tbk3 = PosController::calcularTarifaTransbank(11529, 'credito');
        $v3 = Venta::create([
            'convenio_id' => $convAdulto->id,
            'rut_cliente' => '9.876.543-3',
            'subtotal' => 16470,
            'descuento' => 4941,
            'total' => 11529,
            'metodo_pago' => 'credito',
            'tasa_transbank' => $tbk3['tasa_transbank'],
            'comision_transbank' => $tbk3['comision_transbank'],
            'iva_comision' => $tbk3['iva_comision'],
            'abono_liquido' => $tbk3['abono_liquido'],
            'codigo_autorizacion_tbk' => $tbk3['codigo_autorizacion_tbk'],
            'estado' => 'completada',
        ]);
        DetalleVenta::create([
            'venta_id' => $v3->id,
            'medicamento_id' => $losartan->id,
            'lote_id' => $loteLos->id,
            'numero_lote' => $loteLos->numero_lote,
            'cantidad' => 3,
            'precio_unitario' => 5490,
            'subtotal' => 16470,
        ]);
    }
}
