<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Convenio;
use App\Models\AfiliadoConvenio;
use App\Models\Medicamento;
use App\Models\Lote;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\LibroControlado;
use App\Models\TurnoPersonal;
use App\Models\AusenciaReemplazo;
use App\Models\SolicitudVisacion;
use App\Models\CajaTurno;
use App\Models\BitacoraTemperatura;
use App\Models\OrdenCompra;
use App\Models\Farmacovigilancia;
use App\Models\Cliente;
use App\Models\AuditoriaSistema;
use App\Http\Controllers\PosController;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Rol::insert([
            ['nombre' => 'Químico Farmacéutico', 'descripcion' => 'Dirección técnica y visación de prescripciones médicas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Auxiliar de Farmacia', 'descripcion' => 'Dispensación en punto de venta (POS)', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Administrador General', 'descripcion' => 'Gestión de cuentas, turnos, licencias y reemplazos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Super Administrador', 'descripcion' => 'Control total del sistema, gestión de administradores y auditoría', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 0. CUENTA SUPER ADMINISTRADOR
        $superAdmin = User::create([
            'rut' => '9.000.001-9',
            'name' => 'Dirección General (Super Admin)',
            'email' => 'superadmin@farmacia.cl',
            'rol' => 'superadmin',
            'registro_isp' => null,
            'telefono' => '+56 9 9000 0001',
            'horario_habitual' => 'Acceso Total 24/7',
            'activo' => true,
            'password' => Hash::make('SuperFarma#2026!'),
        ]);

        // 1. CUENTAS DE USUARIO
        $vendedor = User::create([
            'rut' => '17.450.221-8',
            'name' => 'Camila Andrea Muñoz (Vendedora)',
            'email' => 'vendedor@farmacia.cl',
            'rol' => 'vendedor',
            'registro_isp' => 'AUX-ISP-19402',
            'telefono' => '+56 9 8412 3301',
            'horario_habitual' => 'Lunes a Viernes 08:30 - 17:00',
            'activo' => true,
            'password' => Hash::make('CajaPos#2026!'),
        ]);

        $qfTitular = User::create([
            'rut' => '13.890.112-4',
            'name' => 'Dr. Sebastián Morales (Q.F. Titular)',
            'email' => 'qf@farmacia.cl',
            'rol' => 'qf',
            'registro_isp' => 'SIS-QF-48291',
            'telefono' => '+56 9 9201 7745',
            'horario_habitual' => 'Lunes a Viernes 08:00 - 18:00',
            'activo' => true,
            'password' => Hash::make('QuimicoDT#2026!'),
        ]);

        $admin = User::create([
            'rut' => '10.555.888-K',
            'name' => 'Javier Ignacio Araya (Administrador)',
            'email' => 'admin@farmacia.cl',
            'rol' => 'admin',
            'registro_isp' => null,
            'telefono' => '+56 9 7110 8899',
            'horario_habitual' => 'Lunes a Viernes 09:00 - 18:30',
            'activo' => true,
            'password' => Hash::make('AdminFarma#2026!'),
        ]);

        $qfReemplazo = User::create([
            'rut' => '15.777.333-1',
            'name' => 'Dra. Valentina Pizarro (Q.F. Guardia/Reemplazo)',
            'email' => 'qf.reemplazo@farmacia.cl',
            'rol' => 'qf',
            'registro_isp' => 'SIS-QF-61044',
            'telefono' => '+56 9 6543 2109',
            'horario_habitual' => 'Turno Nocturno 22:00 - 08:00 & Fines de Semana',
            'activo' => true,
            'password' => Hash::make('GuardiaQF#2026!'),
        ]);

        $vendedorLicencia = User::create([
            'rut' => '19.111.444-2',
            'name' => 'Matías Rojas Fuentes (Vendedor Tarde)',
            'email' => 'matias@farmacia.cl',
            'rol' => 'vendedor',
            'registro_isp' => 'AUX-ISP-22810',
            'telefono' => '+56 9 5432 1098',
            'horario_habitual' => 'Lunes a Sábado 14:00 - 22:00',
            'activo' => true,
            'password' => Hash::make('TurnoTarde#2026!'),
        ]);

        // 2. TURNOS DIURNOS Y NOCTURNOS
        TurnoPersonal::create([
            'user_id' => $qfTitular->id,
            'tipo_turno' => 'DIURNO_MANANA',
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(5)->format('Y-m-d'),
            'hora_entrada' => '08:00',
            'hora_salida' => '18:00',
            'es_nocturno' => false,
            'observaciones' => 'Dirección Técnica Diurna Obligatoria (DS 466 MINSAL)',
        ]);

        TurnoPersonal::create([
            'user_id' => $vendedor->id,
            'tipo_turno' => 'DIURNO_MANANA',
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(5)->format('Y-m-d'),
            'hora_entrada' => '08:30',
            'hora_salida' => '17:00',
            'es_nocturno' => false,
            'observaciones' => 'Atención Caja Principal POS #1 y #2',
        ]);

        TurnoPersonal::create([
            'user_id' => $qfReemplazo->id,
            'tipo_turno' => 'NOCTURNO_GUARDIA',
            'fecha_inicio' => today()->format('Y-m-d'),
            'fecha_fin' => today()->addDays(2)->format('Y-m-d'),
            'hora_entrada' => '22:00',
            'hora_salida' => '08:00',
            'es_nocturno' => true,
            'observaciones' => 'Turno Nocturno de Urgencia comunal SEREMI de Salud',
        ]);

        // 3. LICENCIAS MÉDICAS, DÍAS LIBRES Y REEMPLAZOS
        AusenciaReemplazo::create([
            'user_id' => $vendedorLicencia->id,
            'reemplazante_id' => $vendedor->id,
            'tipo' => 'LICENCIA_MEDICA',
            'fecha_inicio' => today()->subDays(1)->format('Y-m-d'),
            'fecha_fin' => today()->addDays(6)->format('Y-m-d'),
            'motivo_folio' => 'Licencia Médica Electrónica LME #8849201 (Reposo 7 días)',
            'estado' => 'VIGENTE',
        ]);

        AusenciaReemplazo::create([
            'user_id' => $qfTitular->id,
            'reemplazante_id' => $qfReemplazo->id,
            'tipo' => 'DIA_LIBRE',
            'fecha_inicio' => today()->addDays(6)->format('Y-m-d'),
            'fecha_fin' => today()->addDays(7)->format('Y-m-d'),
            'motivo_folio' => 'Descanso compensatorio fin de semana (Reemplazo D.T. informado a SEREMI)',
            'estado' => 'VIGENTE',
        ]);

        // 4. CONVENIOS Y MEDICAMENTOS (CON BIOEQUIVALENCIA Y LEY CENABAST)
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
            'nombre_comercial' => 'Paracetamol 500mg Caja x16 (Bioequivalente)',
            'principio_activo' => 'Paracetamol',
            'laboratorio' => 'Laboratorio Chile',
            'precio_unitario' => 1990,
            'requiere_receta' => false,
            'es_controlado' => false,
            'es_bioequivalente' => true,
            'es_cenabast' => true,
            'precio_maximo_cenabast' => 2200,
            'costo_neto_referencia' => 890,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 20,
        ]);

        $lotePar26 = Lote::create([
            'medicamento_id' => $paracetamol->id,
            'numero_lote' => 'LOT-PAR-2026A',
            'fecha_elaboracion' => '2025-01-10',
            'fecha_vencimiento' => now()->addDays(22)->format('Y-m-d'),
            'stock_vitrina' => 14,
            'stock_bodega' => 40,
            'costo_unitario_compra' => 890,
        ]);

        $lotePar27 = Lote::create([
            'medicamento_id' => $paracetamol->id,
            'numero_lote' => 'LOT-PAR-2027B',
            'fecha_elaboracion' => '2025-06-01',
            'fecha_vencimiento' => now()->addMonths(8)->format('Y-m-d'),
            'stock_vitrina' => 25,
            'stock_bodega' => 80,
            'costo_unitario_compra' => 890,
        ]);

        $clonazepam = Medicamento::create([
            'codigo_barra' => '780123456002',
            'nombre_comercial' => 'Clonazepam 2mg Caja x30',
            'principio_activo' => 'Clonazepam',
            'laboratorio' => 'Laboratorio Saval',
            'precio_unitario' => 6490,
            'requiere_receta' => true,
            'es_controlado' => true,
            'es_bioequivalente' => true,
            'es_cenabast' => false,
            'costo_neto_referencia' => 2900,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 10,
        ]);

        $loteClo = Lote::create([
            'medicamento_id' => $clonazepam->id,
            'numero_lote' => 'LOT-CLO-2027A',
            'fecha_elaboracion' => '2025-03-15',
            'fecha_vencimiento' => now()->addDays(58)->format('Y-m-d'),
            'stock_vitrina' => 15,
            'stock_bodega' => 50,
            'costo_unitario_compra' => 2900,
        ]);

        $amoxicilina = Medicamento::create([
            'codigo_barra' => '780123456003',
            'nombre_comercial' => 'Amoxicilina 500mg Caja x21',
            'principio_activo' => 'Amoxicilina Trihidrato',
            'laboratorio' => 'Mintlab',
            'precio_unitario' => 4290,
            'requiere_receta' => true,
            'es_controlado' => false,
            'es_bioequivalente' => true,
            'es_cenabast' => false,
            'costo_neto_referencia' => 1850,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 15,
        ]);

        $loteAmox = Lote::create([
            'medicamento_id' => $amoxicilina->id,
            'numero_lote' => 'LOT-AMX-2026C',
            'fecha_elaboracion' => '2025-04-10',
            'fecha_vencimiento' => now()->addDays(18)->format('Y-m-d'),
            'stock_vitrina' => 8,
            'stock_bodega' => 30,
            'costo_unitario_compra' => 1850,
        ]);

        $losartanBio = Medicamento::create([
            'codigo_barra' => '780123456004',
            'nombre_comercial' => 'Losartán Potásico 50mg Caja x30 (Bioequivalente CENABAST)',
            'principio_activo' => 'Losartán 50mg',
            'laboratorio' => 'Bagó / CENABAST',
            'precio_unitario' => 5490,
            'requiere_receta' => true,
            'es_controlado' => false,
            'es_bioequivalente' => true,
            'es_cenabast' => true,
            'precio_maximo_cenabast' => 5990,
            'costo_neto_referencia' => 2100,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 25,
        ]);

        $loteLos = Lote::create([
            'medicamento_id' => $losartanBio->id,
            'numero_lote' => 'LOT-LOS-2028A',
            'fecha_elaboracion' => '2025-08-01',
            'fecha_vencimiento' => now()->addMonths(16)->format('Y-m-d'),
            'stock_vitrina' => 42,
            'stock_bodega' => 120,
            'costo_unitario_compra' => 2100,
        ]);

        // Medicamento Referente de Marca (más caro) para demostrar el Comparador Automático de Bioequivalencia (Ley de Fármacos)
        $losartanMarca = Medicamento::create([
            'codigo_barra' => '780123456005',
            'nombre_comercial' => 'Cozaar (Losartán de Marca) 50mg Caja x30',
            'principio_activo' => 'Losartán 50mg',
            'laboratorio' => 'MSD Internacional',
            'precio_unitario' => 14990,
            'requiere_receta' => true,
            'es_controlado' => false,
            'es_bioequivalente' => false,
            'es_cenabast' => false,
            'costo_neto_referencia' => 8900,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 10,
        ]);

        Lote::create([
            'medicamento_id' => $losartanMarca->id,
            'numero_lote' => 'LOT-COZ-2028M',
            'fecha_elaboracion' => '2025-09-01',
            'fecha_vencimiento' => now()->addMonths(18)->format('Y-m-d'),
            'stock_vitrina' => 12,
            'stock_bodega' => 20,
            'costo_unitario_compra' => 8900,
        ]);

        // Vademécum por Convenio
        $convIsapre->medicamentos()->attach([
            $clonazepam->id => ['porcentaje_descuento' => 25.00],
            $amoxicilina->id => ['porcentaje_descuento' => 20.00],
            $losartanBio->id => ['porcentaje_descuento' => 20.00],
            $losartanMarca->id => ['porcentaje_descuento' => 20.00],
        ]);

        $convAdulto->medicamentos()->attach([
            $paracetamol->id => ['porcentaje_descuento' => 15.00],
            $losartanBio->id => ['porcentaje_descuento' => 30.00],
        ]);

        // Padrón de Afiliados por RUT
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

        // 5. BANDEJA DEL QUÍMICO FARMACÉUTICO INICIA LIMPIA (solo recibe solicitudes reales enviadas desde Caja POS)

        // 6. APERTURA DE CAJA DE TURNO, TEMPERATURA DS 466, ÓRDENES DE COMPRA CENABAST Y FARMACOVIGILANCIA
        CajaTurno::create([
            'user_id' => $vendedor->id,
            'estado' => 'ABIERTA',
            'fondo_fijo_inicial' => 50000,
            'apertura_at' => now()->subHours(3),
            'observaciones' => 'Apertura de Turno Mañana Caja #1',
        ]);

        BitacoraTemperatura::create([
            'user_id' => $qfTitular->id,
            'jornada' => 'MANANA_09H',
            'temp_sala' => 21.4,
            'humedad_sala' => 48,
            'temp_refrigerador' => 4.2,
            'conforme_isp' => true,
            'observaciones' => 'Control matinal conforme DS 466 MINSAL',
        ]);

        OrdenCompra::create([
            'folio_oc' => 'OC-CENA-4810',
            'proveedor' => 'CENABAST (Ley 21.198)',
            'medicamento_id' => $losartanBio->id,
            'cantidad_solicitada' => 150,
            'costo_unitario_neto' => 2100,
            'total_neto' => 315000,
            'estado' => 'RECEPCIONADA',
        ]);

        OrdenCompra::create([
            'folio_oc' => 'OC-SOCO-4811',
            'proveedor' => 'Droguería Socofar',
            'medicamento_id' => $amoxicilina->id,
            'cantidad_solicitada' => 80,
            'costo_unitario_neto' => 1850,
            'total_neto' => 148000,
            'estado' => 'EMITIDA',
        ]);

        Farmacovigilancia::create([
            'user_id' => $qfTitular->id,
            'tipo_registro' => 'RAM_ISP',
            'rut_paciente' => '12.345.678-5',
            'nombre_paciente' => 'María Alejandra González',
            'medicamento_id' => $amoxicilina->id,
            'numero_lote' => 'LOT-AMX-2026C',
            'descripcion_clinica' => 'Rash cutáneo leve reportado a las 48h de iniciado el tratamiento antibiótico.',
            'severidad' => 'LEVE',
            'estado_reporte' => 'REPORTADO_RED_RAM_ISP',
        ]);

        // Ventas iniciales con Folio DTE SII
        $tbk1 = PosController::calcularTarifaTransbank(23880, 'efectivo');
        $v1 = Venta::create([
            'tipo_dte' => 'BOLETA_ELECTRONICA_39',
            'folio_dte_sii' => 'DTE39-001041',
            'user_id' => $vendedor->id,
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
            'tipo_dte' => 'BOLETA_ELECTRONICA_39',
            'folio_dte_sii' => 'DTE39-001042',
            'user_id' => $vendedor->id,
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
            'tipo_dte' => 'BOLETA_ELECTRONICA_39',
            'folio_dte_sii' => 'DTE39-001043',
            'user_id' => $vendedor->id,
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
            'medicamento_id' => $losartanBio->id,
            'lote_id' => $loteLos->id,
            'numero_lote' => $loteLos->numero_lote,
            'cantidad' => 3,
            'precio_unitario' => 5490,
            'subtotal' => 16470,
        ]);

        // 7. PRODUCTOS NO FARMACÉUTICOS (PERFUMERÍA, DERMOCOSMÉTICA, INSUMOS)
        $protectorSolar = Medicamento::create([
            'codigo_barra' => '780999000101',
            'nombre_comercial' => 'Protector Solar Eucerin Oil Control FPS 50+ 50ml',
            'principio_activo' => 'Dermocosmética',
            'laboratorio' => 'Eucerin / Beiersdorf',
            'es_medicamento' => false,
            'categoria_producto' => 'Dermocosmética',
            'precio_unitario' => 18990,
            'requiere_receta' => false,
            'es_controlado' => false,
            'es_bioequivalente' => false,
            'es_cenabast' => false,
            'costo_neto_referencia' => 11200,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 8,
        ]);
        Lote::create([
            'medicamento_id' => $protectorSolar->id,
            'numero_lote' => 'LOT-DERM-2027A',
            'fecha_elaboracion' => '2025-10-01',
            'fecha_vencimiento' => now()->addMonths(14)->format('Y-m-d'),
            'stock_vitrina' => 10,
            'stock_bodega' => 24,
            'costo_unitario_compra' => 11200,
        ]);

        $termometro = Medicamento::create([
            'codigo_barra' => '780999000102',
            'nombre_comercial' => 'Termómetro Digital Infrarrojo Sin Contacto',
            'principio_activo' => 'Insumos Médicos',
            'laboratorio' => 'Omron Healthcare',
            'es_medicamento' => false,
            'categoria_producto' => 'Insumos Médicos',
            'precio_unitario' => 24990,
            'requiere_receta' => false,
            'es_controlado' => false,
            'es_bioequivalente' => false,
            'es_cenabast' => false,
            'costo_neto_referencia' => 14500,
            'requiere_cadena_frio' => false,
            'stock_minimo' => 5,
        ]);
        Lote::create([
            'medicamento_id' => $termometro->id,
            'numero_lote' => 'LOT-INS-2028B',
            'fecha_elaboracion' => '2025-11-01',
            'fecha_vencimiento' => now()->addMonths(24)->format('Y-m-d'),
            'stock_vitrina' => 6,
            'stock_bodega' => 15,
            'costo_unitario_compra' => 14500,
        ]);

        // 8. RECETA APROBADA DE HISTORIAL PARA MÓDULO DE RECETAS APROBADAS
        SolicitudVisacion::create([
            'vendedor_id' => $vendedor->id,
            'qf_id' => $qfTitular->id,
            'estado' => 'APROBADA',
            'tipo_receta' => 'RECETA_CHEQUE_PSICOTROPICO',
            'rut_paciente' => '12.345.678-5',
            'nombre_paciente' => 'María Alejandra González',
            'rut_medico' => '9.412.880-2',
            'nombre_medico' => 'Dr. Roberto Valdés (Psiquiatría Adultos)',
            'numero_receta' => 'REC-CHQ-00481',
            'items_json' => [
                [
                    'medicamento_id' => $clonazepam->id,
                    'codigo_barra' => $clonazepam->codigo_barra,
                    'nombre_comercial' => $clonazepam->nombre_comercial,
                    'principio_activo' => $clonazepam->principio_activo,
                    'es_controlado' => true,
                    'requiere_receta' => true,
                    'cantidad' => 2,
                    'precio_unitario' => 6490,
                ],
            ],
            'monto_total' => 9735,
            'observaciones_qf' => 'Receta cheque original verificada conforme a DS 404/466 MINSAL.',
            'codigo_firma_qf' => 'QF-ISP-94A2F1',
            'aprobada_at' => now()->subHours(2),
        ]);

        // 9. CLIENTES REGISTRADOS
        Cliente::create([
            'rut' => '12345678-5',
            'nombre_completo' => 'María Alejandra González',
            'telefono' => '+56 9 8765 4321',
            'email' => 'maria.gonzalez@correo.cl',
            'direccion' => 'Av. Providencia 1245, Depto 402',
            'convenio_id' => $convIsapre->id,
            'observaciones' => 'Paciente crónica en tratamiento continuo.',
            'activo' => true,
        ]);

        Cliente::create([
            'rut' => '9876543-3',
            'nombre_completo' => 'Jorge Eduardo Ramírez',
            'telefono' => '+56 9 7654 3210',
            'email' => 'jorge.ramirez@correo.cl',
            'direccion' => 'Calle Los Aromos 480, Ñuñoa',
            'convenio_id' => $convAdulto->id,
            'observaciones' => 'Beneficiario Adulto Mayor FONASA.',
            'activo' => true,
        ]);

        // 10. REGISTROS INICIALES DE AUDITORÍA
        AuditoriaSistema::create([
            'user_id' => $superAdmin->id,
            'usuario_nombre' => $superAdmin->name,
            'usuario_rol' => $superAdmin->rol,
            'accion' => 'INICIALIZACION',
            'modulo' => 'Sistema',
            'detalle' => 'Inicialización del sistema PharmaCore ERP con cuentas base y módulos de auditoría activos.',
            'ip' => '127.0.0.1',
            'navegador' => 'Google Chrome',
            'sistema_operativo' => 'Windows 10/11',
            'user_agent' => 'Seed Inicial',
        ]);

        AuditoriaSistema::create([
            'user_id' => $qfTitular->id,
            'usuario_nombre' => $qfTitular->name,
            'usuario_rol' => $qfTitular->rol,
            'accion' => 'APROBAR_RECETA',
            'modulo' => 'Visación Q.F.',
            'detalle' => 'Aprobó receta REC-CHQ-00481 para paciente María Alejandra González (Firma QF-ISP-94A2F1).',
            'ip' => '127.0.0.1',
            'navegador' => 'Microsoft Edge',
            'sistema_operativo' => 'Windows 10/11',
            'user_agent' => 'Seed Inicial',
        ]);
    }
}
