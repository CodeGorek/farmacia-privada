<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Convenio;
use App\Models\Medicamento;
use App\Models\Lote;
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

        Convenio::insert([
            ['nombre' => 'Convenio Adulto Mayor', 'rut_institucion' => '69.000.100-1', 'porcentaje_descuento' => 15.00, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Isapre SaludVida', 'rut_institucion' => '96.500.200-K', 'porcentaje_descuento' => 20.00, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
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

        // Dos lotes con distinta fecha de vencimiento para demostrar FEFO
        Lote::create([
            'medicamento_id' => $paracetamol->id,
            'numero_lote' => 'LOT-PAR-2027B',
            'fecha_elaboracion' => '2025-06-01',
            'fecha_vencimiento' => '2027-06-01',
            'stock_vitrina' => 25,
            'stock_bodega' => 80,
        ]);

        Lote::create([
            'medicamento_id' => $paracetamol->id,
            'numero_lote' => 'LOT-PAR-2026A',
            'fecha_elaboracion' => '2025-01-10',
            'fecha_vencimiento' => '2026-12-15', // Vence antes -> Prioridad FEFO #1
            'stock_vitrina' => 10,
            'stock_bodega' => 40,
        ]);

        $clonazepam = Medicamento::create([
            'codigo_barra' => '780123456002',
            'nombre_comercial' => 'Clonazepam 2mg Caja x30',
            'principio_activo' => 'Clonazepam',
            'laboratorio' => 'Saval',
            'precio_unitario' => 6490,
            'requiere_receta' => true,
            'es_controlado' => true,
            'stock_minimo' => 10,
        ]);

        Lote::create([
            'medicamento_id' => $clonazepam->id,
            'numero_lote' => 'LOT-CLO-2027A',
            'fecha_elaboracion' => '2025-03-15',
            'fecha_vencimiento' => '2027-03-15',
            'stock_vitrina' => 15,
            'stock_bodega' => 50,
        ]);
    }
}
