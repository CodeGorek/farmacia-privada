<?php

namespace Tests\Feature;

use App\Models\Lote;
use App\Models\Medicamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
