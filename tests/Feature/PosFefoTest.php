<?php

namespace Tests\Feature;

use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\SolicitudVisacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosFefoTest extends TestCase
{
    use RefreshDatabase;

    public function test_algoritmo_fefo_descuenta_primero_el_lote_mas_proximo_a_vencer(): void
    {
        $medicamento = Medicamento::create([
            'codigo_barra' => '780999111',
            'nombre_comercial' => 'Ibuprofeno 400mg',
            'principio_activo' => 'Ibuprofeno',
            'laboratorio' => 'Mintlab',
            'precio_unitario' => 2500,
            'requiere_receta' => false,
            'es_controlado' => false,
            'stock_minimo' => 10,
        ]);

        // Lote con vencimiento lejano (2028)
        $loteLejano = Lote::create([
            'medicamento_id' => $medicamento->id,
            'numero_lote' => 'LOTE-2028',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => '2028-05-01',
            'stock_vitrina' => 10,
            'stock_bodega' => 20,
        ]);

        // Lote con vencimiento más próximo (2026) -> Debe descontarse primero
        $loteProximo = Lote::create([
            'medicamento_id' => $medicamento->id,
            'numero_lote' => 'LOTE-2026',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => '2026-12-01',
            'stock_vitrina' => 5,
            'stock_bodega' => 0,
        ]);

        // Solicitamos 8 unidades: debe tomar 5 de LOTE-2026 y 3 de LOTE-2028
        $response = $this->postJson('/api/pos/procesar-venta', [
            'medicamento_id' => $medicamento->id,
            'cantidad' => 8,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'lotes_fefo' => [
                    ['lote' => 'LOTE-2026', 'cantidad' => 5],
                    ['lote' => 'LOTE-2028', 'cantidad' => 3],
                ],
            ]);

        $this->assertEquals(0, $loteProximo->fresh()->stock_vitrina);
        $this->assertEquals(7, $loteLejano->fresh()->stock_vitrina);
    }

    public function test_vendedor_requiere_visacion_qf_aprobada_para_medicamento_controlado(): void
    {
        $vendedor = User::create([
            'rut' => '17.450.221-8',
            'name' => 'Vendedor Prueba',
            'email' => 'vendedor.test@farmacia.cl',
            'rol' => 'vendedor',
            'activo' => true,
            'password' => Hash::make('123456'),
        ]);

        $med = Medicamento::create([
            'codigo_barra' => '780999222',
            'nombre_comercial' => 'Clonazepam 2mg Test',
            'principio_activo' => 'Clonazepam',
            'laboratorio' => 'Saval',
            'precio_unitario' => 6490,
            'requiere_receta' => true,
            'es_controlado' => true,
            'stock_minimo' => 5,
        ]);

        Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOTE-CTRL-1',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => '2028-05-01',
            'stock_vitrina' => 5,
            'stock_bodega' => 10,
        ]);

        // Sin visación aprobada debe ser rechazado con 422
        $resSinVisacion = $this->actingAs($vendedor)->postJson('/pos/procesar-venta', [
            'medicamento_id' => $med->id,
            'cantidad' => 1,
        ]);
        $resSinVisacion->assertStatus(422);

        // Con visación APROBADA por Q.F. debe permitir el cobro y pasar la solicitud a PROCESADA
        $visacion = SolicitudVisacion::create([
            'vendedor_id' => $vendedor->id,
            'estado' => 'APROBADA',
            'tipo_receta' => 'RECETA_CHEQUE',
            'rut_paciente' => '12.345.678-5',
            'nombre_paciente' => 'Paciente Test',
            'rut_medico' => '9.412.880-2',
            'nombre_medico' => 'Dr. Test',
            'numero_receta' => 'REC-TEST-001',
            'items_json' => [['medicamento_id' => $med->id, 'cantidad' => 1]],
            'monto_total' => 6490,
            'codigo_firma_qf' => 'QF-ISP-TEST01',
            'aprobada_at' => now(),
        ]);

        $resConVisacion = $this->actingAs($vendedor)->postJson('/pos/procesar-venta', [
            'medicamento_id' => $med->id,
            'cantidad' => 1,
            'solicitud_visacion_id' => $visacion->id,
        ]);
        $resConVisacion->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertEquals('PROCESADA', $visacion->fresh()->estado);
    }
}
