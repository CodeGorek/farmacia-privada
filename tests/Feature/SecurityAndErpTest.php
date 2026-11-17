<?php

namespace Tests\Feature;

use App\Http\Controllers\PosController;
use App\Models\AfiliadoConvenio;
use App\Models\AusenciaReemplazo;
use App\Models\CajaTurno;
use App\Models\Convenio;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAndErpTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $rol, string $email, string $rut, bool $activo = true): User
    {
        return User::create([
            'rut' => $rut,
            'name' => 'Usuario ' . strtoupper($rol),
            'email' => $email,
            'rol' => $rol,
            'registro_isp' => $rol === 'qf' ? 'SIS-QF-99999' : null,
            'horario_habitual' => 'Lunes a Viernes 08:30 - 17:30',
            'activo' => $activo,
            'password' => Hash::make('123456'),
        ]);
    }

    public function test_lote_vencido_y_lote_en_recall_isp_no_se_venden(): void
    {
        $vendedor = $this->crearUsuario('vendedor', 'vend1@farmacia.cl', '11.111.111-1');

        $med = Medicamento::create([
            'codigo_barra' => '780100100100',
            'nombre_comercial' => 'Omeprazol 20mg',
            'principio_activo' => 'Omeprazol',
            'laboratorio' => 'Chile',
            'precio_unitario' => 2990,
            'stock_minimo' => 10,
        ]);

        // Lote vencido ayer -> excluido por FEFO
        Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOT-VENCIDO',
            'fecha_elaboracion' => now()->subYear()->format('Y-m-d'),
            'fecha_vencimiento' => now()->subDay()->format('Y-m-d'),
            'stock_vitrina' => 20,
            'stock_bodega' => 0,
        ]);

        // Lote bloqueado por Recall ISP -> excluido por FEFO
        Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOT-RECALL',
            'fecha_elaboracion' => now()->subMonths(2)->format('Y-m-d'),
            'fecha_vencimiento' => now()->addMonths(10)->format('Y-m-d'),
            'stock_vitrina' => 15,
            'stock_bodega' => 0,
            'bloqueado_recall_isp' => true,
            'motivo_bloqueo' => 'Alerta sanitaria ISP',
        ]);

        // Lote vigente y habilitado -> único vendible
        $loteValido = Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOT-VALIDO',
            'fecha_elaboracion' => now()->subMonth()->format('Y-m-d'),
            'fecha_vencimiento' => now()->addMonths(12)->format('Y-m-d'),
            'stock_vitrina' => 4,
            'stock_bodega' => 0,
        ]);

        // Solicitar 5 unidades cuando solo 4 son válidas debe fallar con HTTP 422
        $resFail = $this->actingAs($vendedor)->postJson('/pos/procesar-venta', [
            'medicamento_id' => $med->id,
            'cantidad' => 5,
        ]);
        $resFail->assertStatus(422);

        // Solicitar 3 unidades debe descontar únicamente del lote válido
        $resOk = $this->actingAs($vendedor)->postJson('/pos/procesar-venta', [
            'medicamento_id' => $med->id,
            'cantidad' => 3,
        ]);
        $resOk->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'lotes_fefo' => [
                    ['lote' => 'LOT-VALIDO', 'cantidad' => 3],
                ],
            ]);

        $this->assertEquals(1, $loteValido->fresh()->stock_vitrina);
    }

    public function test_switch_rut_aplica_descuento_selectivo_solo_a_vademecum(): void
    {
        $vendedor = $this->crearUsuario('vendedor', 'vend2@farmacia.cl', '11.111.112-K');

        $convenio = Convenio::create([
            'nombre' => 'Isapre Prueba',
            'rut_institucion' => '96.000.111-1',
            'porcentaje_descuento' => 20.00,
            'activo' => true,
        ]);

        $medAdherido = Medicamento::create([
            'codigo_barra' => '780200200201',
            'nombre_comercial' => 'Losartán 50mg',
            'principio_activo' => 'Losartán',
            'precio_unitario' => 5000,
        ]);

        $medExcluido = Medicamento::create([
            'codigo_barra' => '780200200202',
            'nombre_comercial' => 'Paracetamol 500mg',
            'principio_activo' => 'Paracetamol',
            'precio_unitario' => 2000,
        ]);

        Lote::create([
            'medicamento_id' => $medAdherido->id,
            'numero_lote' => 'LOT-A1',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
            'stock_vitrina' => 10,
        ]);

        Lote::create([
            'medicamento_id' => $medExcluido->id,
            'numero_lote' => 'LOT-B1',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
            'stock_vitrina' => 10,
        ]);

        // Solo Losartán pertenece al vademécum (20% descuento)
        $convenio->medicamentos()->attach($medAdherido->id, ['porcentaje_descuento' => 20.00]);

        AfiliadoConvenio::create([
            'rut' => '12345678-5',
            'nombre_afiliado' => 'Paciente Convenio',
            'convenio_id' => $convenio->id,
            'estado' => 'ACTIVO',
        ]);

        // Validar RUT en switch
        $resSwitch = $this->actingAs($vendedor)->postJson('/pos/validar-rut-convenio', [
            'rut' => '12.345.678-5',
        ]);
        $resSwitch->assertStatus(200)
            ->assertJson([
                'tiene_beneficio' => true,
                'estado_switch' => 'APROBADO',
                'convenio_id' => $convenio->id,
            ]);

        // Procesar carrito mixto: 5000 (20% dcto = 1000) + 2000 (0% dcto) = 7000 bruto - 1000 = 6000 total
        $resVenta = $this->actingAs($vendedor)->postJson('/pos/procesar-venta', [
            'convenio_id' => $convenio->id,
            'rut_cliente' => '12.345.678-5',
            'metodo_pago' => 'efectivo',
            'items' => [
                ['medicamento_id' => $medAdherido->id, 'cantidad' => 1],
                ['medicamento_id' => $medExcluido->id, 'cantidad' => 1],
            ],
        ]);

        $resVenta->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'subtotal' => 7000,
                'descuento' => 1000,
                'total' => 6000,
            ]);
    }

    public function test_calculo_tarifas_transbank_debito_credito_y_efectivo(): void
    {
        $debito = PosController::calcularTarifaTransbank(10000, 'debito');
        $this->assertEquals(1.49, $debito['tasa_transbank']);
        $this->assertEquals(149, $debito['comision_transbank']);
        $this->assertEquals(28, $debito['iva_comision']); // round(149 * 0.19) = 28
        $this->assertEquals(9823, $debito['abono_liquido']);

        $credito = PosController::calcularTarifaTransbank(10000, 'credito');
        $this->assertEquals(2.29, $credito['tasa_transbank']);
        $this->assertEquals(229, $credito['comision_transbank']);
        $this->assertEquals(44, $credito['iva_comision']); // round(229 * 0.19) = 44
        $this->assertEquals(9727, $credito['abono_liquido']);

        $efectivo = PosController::calcularTarifaTransbank(10000, 'efectivo');
        $this->assertFalse($efectivo['es_transbank']);
        $this->assertEquals(0, $efectivo['total_descuento_tbk']);
        $this->assertEquals(10000, $efectivo['abono_liquido']);
    }

    public function test_nota_credito_dte61_anula_venta_y_reintegra_lote_fefo(): void
    {
        $admin = $this->crearUsuario('admin', 'admin.nc@farmacia.cl', '10.100.100-1');
        $med = Medicamento::create([
            'codigo_barra' => '780300300300',
            'nombre_comercial' => 'Atorvastatina 20mg',
            'principio_activo' => 'Atorvastatina',
            'precio_unitario' => 4500,
        ]);
        $lote = Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOT-NC61',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
            'stock_vitrina' => 10,
        ]);

        $resVenta = $this->actingAs($admin)->postJson('/pos/procesar-venta', [
            'medicamento_id' => $med->id,
            'cantidad' => 4,
            'metodo_pago' => 'efectivo',
        ]);
        $ventaId = $resVenta->json('venta_id');
        $this->assertEquals(6, $lote->fresh()->stock_vitrina);

        $resNc = $this->actingAs($admin)->post("/operaciones/venta/{$ventaId}/anular-nc61");
        $resNc->assertRedirect();

        $venta = Venta::findOrFail($ventaId);
        $this->assertTrue($venta->anulada);
        $this->assertEquals('NOTA_CREDITO_61', $venta->tipo_dte);
        $this->assertEquals(10, $lote->fresh()->stock_vitrina);
    }

    public function test_rbac_bloquea_vendedor_en_admin_visacion_inventario_y_recall(): void
    {
        $vendedor = $this->crearUsuario('vendedor', 'vend.rbac@farmacia.cl', '11.222.333-4');
        $admin = $this->crearUsuario('admin', 'adm.rbac@farmacia.cl', '10.222.333-5');
        $qf = $this->crearUsuario('qf', 'qf.rbac@farmacia.cl', '13.222.333-6');

        $med = Medicamento::create([
            'codigo_barra' => '780400400400',
            'nombre_comercial' => 'Ibuprofeno RBAC',
            'principio_activo' => 'Ibuprofeno',
            'precio_unitario' => 1500,
        ]);
        $lote = Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOT-RBAC',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
            'stock_vitrina' => 10,
        ]);

        // Vendedor bloqueado en Admin, Visación QF, Alta Medicamento y Recall ISP
        $this->actingAs($vendedor)->get('/admin/personal')->assertRedirect('/pos');
        $this->actingAs($vendedor)->get('/visacion-qf')->assertRedirect('/pos');
        $this->actingAs($vendedor)->post('/inventario/medicamentos', [
            'codigo_barra' => '780999888777',
            'nombre_comercial' => 'Intento',
            'principio_activo' => 'Test',
            'precio_unitario' => 1000,
            'stock_minimo' => 5,
        ])->assertSessionHas('rbac_warning');
        $this->actingAs($vendedor)->post("/operaciones/lote/{$lote->id}/recall-isp")
            ->assertSessionHas('rbac_warning');
        $this->assertFalse((bool) $lote->fresh()->bloqueado_recall_isp);

        // QF bloqueado en Admin, pero permitido en Visación
        $this->actingAs($qf)->get('/admin/personal')->assertRedirect('/visacion-qf');
        $this->actingAs($qf)->get('/visacion-qf')->assertStatus(200);

        // Admin accede a /admin/personal
        $this->actingAs($admin)->get('/admin/personal')->assertStatus(200);
    }

    public function test_login_bloquea_cuenta_deshabilitada_y_licencia_medica_vigente(): void
    {
        $deshabilitado = $this->crearUsuario('vendedor', 'inactivo@farmacia.cl', '12.000.001-1', false);
        $conLicencia = $this->crearUsuario('vendedor', 'licencia@farmacia.cl', '12.000.002-2', true);

        AusenciaReemplazo::create([
            'user_id' => $conLicencia->id,
            'tipo' => 'LICENCIA_MEDICA',
            'fecha_inicio' => today()->subDay()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(5)->format('Y-m-d'),
            'motivo_folio' => 'LME-TEST-01',
            'estado' => 'VIGENTE',
        ]);

        $this->post('/login', [
            'identificador' => $deshabilitado->email,
            'password' => '123456',
        ])->assertSessionHasErrors('identificador');
        $this->assertGuest();

        $this->post('/login', [
            'identificador' => $conLicencia->email,
            'password' => '123456',
        ])->assertSessionHasErrors('identificador');
        $this->assertGuest();
    }

    public function test_rate_limiting_login_bloquea_al_sexto_intento(): void
    {
        $this->crearUsuario('vendedor', 'brute@farmacia.cl', '12.000.003-3');

        for ($i = 0; $i < 5; $i++) {
            $res = $this->post('/login', [
                'identificador' => 'brute@farmacia.cl',
                'password' => 'clave-incorrecta',
            ]);
            $this->assertNotEquals(429, $res->getStatusCode());
        }

        // El 6to intento dentro del mismo minuto debe disparar HTTP 429 Too Many Requests
        $resLimit = $this->post('/login', [
            'identificador' => 'brute@farmacia.cl',
            'password' => 'clave-incorrecta',
        ]);
        $resLimit->assertStatus(429);
    }

    public function test_proteccion_sqli_y_xss_en_entradas(): void
    {
        $admin = $this->crearUsuario('admin', 'sec.admin@farmacia.cl', '12.000.004-4');

        // Intento de SQL Injection en Switch RUT
        $resSqli = $this->actingAs($admin)->postJson('/pos/validar-rut-convenio', [
            'rut' => "' OR 1=1 --",
        ]);
        $resSqli->assertStatus(200)
            ->assertJson(['tiene_beneficio' => false, 'estado_switch' => 'SIN_CONVENIO']);

        // Intento de Stored XSS en nombre comercial (Blade debe escapar con &lt;script&gt;)
        $this->actingAs($admin)->post('/inventario/medicamentos', [
            'codigo_barra' => '780555666777',
            'nombre_comercial' => "<script>alert('xss')</script>",
            'principio_activo' => 'Paracetamol',
            'precio_unitario' => 1000,
            'stock_minimo' => 5,
        ]);

        $resInv = $this->actingAs($admin)->get('/inventario');
        $resInv->assertStatus(200);
        $resInv->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_cierre_caja_z_calcula_cuadratura_exacta(): void
    {
        $vendedor = $this->crearUsuario('vendedor', 'caja@farmacia.cl', '12.000.005-5');

        $this->actingAs($vendedor)->post('/operaciones/caja/abrir', [
            'fondo_fijo_inicial' => 50000,
        ])->assertRedirect();

        $caja = CajaTurno::where('estado', 'ABIERTA')->firstOrFail();

        $med = Medicamento::create([
            'codigo_barra' => '780600600600',
            'nombre_comercial' => 'Vitamina C',
            'principio_activo' => 'Ácido Ascórbico',
            'precio_unitario' => 3000,
        ]);
        Lote::create([
            'medicamento_id' => $med->id,
            'numero_lote' => 'LOT-VITC',
            'fecha_elaboracion' => '2025-01-01',
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
            'stock_vitrina' => 10,
        ]);

        $this->actingAs($vendedor)->postJson('/pos/procesar-venta', [
            'medicamento_id' => $med->id,
            'cantidad' => 2,
            'metodo_pago' => 'efectivo',
        ])->assertStatus(200);

        // Esperado en gaveta: 50000 + 6000 = 56000. Declarado: 56000 -> diferencia 0
        $this->actingAs($vendedor)->post("/operaciones/caja/{$caja->id}/cerrar-z", [
            'efectivo_fisico_declarado' => 56000,
            'observaciones' => 'Arqueo conforme',
        ])->assertRedirect();

        $cajaFresh = $caja->fresh();
        $this->assertEquals('CERRADA', $cajaFresh->estado);
        $this->assertEquals(6000, (int) $cajaFresh->ventas_efectivo_sistema);
        $this->assertEquals(0, (int) $cajaFresh->diferencia_arqueo);
    }
}
