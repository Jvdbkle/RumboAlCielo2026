<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\CerrarLiquidacion;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarLiquidacion;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarTablero;
use RumboAlCielo\Aplicacion\CasosDeUso\EmitirListaDiaria;
use RumboAlCielo\Aplicacion\CasosDeUso\ExportarPlanilla;
use RumboAlCielo\Aplicacion\CasosDeUso\ExportarReservas;
use RumboAlCielo\Aplicacion\CasosDeUso\GenerarLiquidacion;
use RumboAlCielo\Aplicacion\CasosDeUso\PublicarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarPlato;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\ResultadoLiquidacion;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Infraestructura\Excel\GeneradorXlsx;
use RumboAlCielo\Tests\Dobles\AlmacenFotosEnMemoria;
use RumboAlCielo\Tests\Dobles\FamiliarRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\FeriadoRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\GeneradorHojaCalculoEnMemoria;
use RumboAlCielo\Tests\Dobles\LiquidacionRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\MenuRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PensionRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\ReservaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;
use ZipArchive;

/** Módulo de reportes: HU-23 tablero, HU-24 lista diaria, HU-25 exportar reservas, HU-26 liquidación y HU-27 planilla. */
final class ReportesTest extends TestCase
{
    private const JUAN = 2;   // empleado titular, persona 2
    private const LUIS = 3;   // empleado titular, persona 3

    private RolRepositorioEnMemoria $roles;
    private UsuarioRepositorioEnMemoria $usuarios;
    private PersonaRepositorioEnMemoria $personas;
    private MenuRepositorioEnMemoria $menus;
    private ReservaRepositorioEnMemoria $reservas;
    private PensionRepositorioEnMemoria $pensiones;
    private LiquidacionRepositorioEnMemoria $liquidaciones;
    private int $personaMaria;   // familiar de Juan
    private int $personaRosa;    // familiar de Luis
    private int $siguienteReserva = 1;

    protected function setUp(): void
    {
        // Lunes 16/11/2026, 10:00: ya pasó la hora de corte.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 10:00'));

        $this->roles = new RolRepositorioEnMemoria();
        $this->roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $this->roles->agregar(new Rol(2, Rol::EMPLEADO));

        $this->usuarios = new UsuarioRepositorioEnMemoria();
        $this->personas = new PersonaRepositorioEnMemoria();
        $familiares = new FamiliarRepositorioEnMemoria();
        $this->menus = new MenuRepositorioEnMemoria();
        $this->reservas = new ReservaRepositorioEnMemoria();
        $this->pensiones = new PensionRepositorioEnMemoria();
        $this->liquidaciones = new LiquidacionRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($this->usuarios, $this->personas, $this->roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);
        $registrarUsuario->ejecutar('Luis', 'Mamani', 'luis@correo.com', '71000000', 2);

        $registrarFamiliar = new RegistrarFamiliar($familiares, $this->personas, $this->usuarios, $this->roles);
        $idMaria = $registrarFamiliar->ejecutar(self::JUAN, 'María', 'López', 'conyuge');
        $idRosa = $registrarFamiliar->ejecutar(self::LUIS, 'Rosa', 'Quispe', 'hija');
        $this->personaMaria = $familiares->buscarPorId($idMaria)->getIdPersona();
        $this->personaRosa = $familiares->buscarPorId($idRosa)->getIdPersona();

        // Menú publicado de la semana del 16 al 19 de noviembre.
        $registrarPlato = new RegistrarPlato($this->menus, new AlmacenFotosEnMemoria());
        foreach (['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19'] as $dia) {
            $registrarPlato->ejecutar(
                $dia,
                'Sopa de maní',
                'Pique macho',
                'Ensalada mixta',
                'Gelatina',
                'Refresco de linaza',
                new ArchivoSubido('sopa.jpg', 1000, 'image/jpeg', '/tmp/x'),
                new ArchivoSubido('plato.png', 1000, 'image/png', '/tmp/x'),
            );
        }
        (new PublicarMenu($this->menus, new FeriadoRepositorioEnMemoria()))->ejecutar('2026-11-16');
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
    }

    private function reserva(
        int $idPersona,
        int $idTitular,
        string $fecha,
        EstadoReserva $estado = EstadoReserva::Confirmada,
        Modalidad $modalidad = Modalidad::Diaria,
    ): void {
        $tarifa = $modalidad === Modalidad::Pensionada ? 15.0 : 18.0;
        $id = $this->siguienteReserva++;
        $this->reservas->agregar(
            new Reserva($id, $idPersona, $idTitular, 1, new DateTimeImmutable($fecha), $modalidad, $tarifa, $estado, 'codigo-' . $id)
        );
    }

    /** Agrega $cantidad reservas a la persona en días distintos de octubre de 2026 (lunes a jueves). */
    private function reservasDeOctubre(int $idPersona, int $idTitular, int $cantidad, Modalidad $modalidad, EstadoReserva $estado = EstadoReserva::Entregada): void
    {
        $dias = ['01', '05', '06', '07', '08', '12', '13', '14', '15', '19', '20', '21', '22', '26', '27', '28', '29'];
        foreach (array_slice($dias, 0, $cantidad) as $dia) {
            $this->reserva($idPersona, $idTitular, '2026-10-' . $dia, $estado, $modalidad);
        }
    }

    private function pensionActivaDeOctubre(int $idPersona, int $idTitular): void
    {
        $this->pensiones->guardar(new Pension(0, $idPersona, $idTitular, new DateTimeImmutable('2026-10-01'), Pension::TARIFA, EstadoPension::Activa));
    }

    private function tablero(): ConsultarTablero
    {
        return new ConsultarTablero($this->usuarios, $this->roles, $this->menus, $this->reservas);
    }

    private function listaDiaria(): EmitirListaDiaria
    {
        return new EmitirListaDiaria($this->reservas, $this->personas, $this->usuarios);
    }

    private function exportador(): ExportarReservas
    {
        return new ExportarReservas($this->reservas, $this->personas, $this->menus, new GeneradorHojaCalculoEnMemoria());
    }

    private function liquidar(): ResultadoLiquidacion
    {
        $octubre = new DateTimeImmutable('2026-10-01');
        (new GenerarLiquidacion($this->reservas, $this->pensiones, $this->liquidaciones))->ejecutar($octubre);
        return (new ConsultarLiquidacion($this->liquidaciones, $this->personas, $this->usuarios))->ejecutar($octubre);
    }

    private function filaDe(ResultadoLiquidacion $resultado, int $idPersona): \RumboAlCielo\Aplicacion\CasosDeUso\FilaLiquidacion
    {
        $nombre = $this->personas->buscarPorId($idPersona)->obtenerNombreCompleto();
        foreach ($resultado->filas as $fila) {
            if ($fila->nombrePersona === $nombre) {
                return $fila;
            }
        }
        $this->fail("La liquidación no incluye a $nombre.");
    }

    private function mensajeDeRechazo(callable $accion): string
    {
        try {
            $accion();
        } catch (ReglaDeNegocioException $error) {
            return $error->getMessage();
        }
        $this->fail('Se esperaba que el sistema rechazara la operación.');
    }

    // ---------------------------------------------------------------- HU-23

    /** HU-23 criterio 1: empleados habilitados, estado del menú y reservas del día por tipo, entregadas y pendientes. */
    public function test_el_tablero_muestra_los_indicadores_del_dia(): void
    {
        $this->reserva(2, self::JUAN, '2026-11-16', EstadoReserva::Entregada);
        $this->reserva(3, self::LUIS, '2026-11-16', EstadoReserva::Confirmada);
        $this->reserva($this->personaMaria, self::JUAN, '2026-11-16', EstadoReserva::Confirmada, Modalidad::Pensionada);
        $this->reserva($this->personaRosa, self::LUIS, '2026-11-16', EstadoReserva::Entregada, Modalidad::Pensionada);
        $this->reserva(2, self::JUAN, '2026-11-16', EstadoReserva::Cancelada);   // no cuenta
        $this->reserva(2, self::JUAN, '2026-11-17', EstadoReserva::Confirmada);  // otro día, no cuenta

        $tablero = $this->tablero()->ejecutar();

        $this->assertSame(2, $tablero->empleadosHabilitados);
        $this->assertSame('Publicado', $tablero->estadoMenu);
        $this->assertSame(4, $tablero->totalReservasHoy());
        $this->assertSame(2, $tablero->pensionados->total);
        $this->assertSame(1, $tablero->pensionados->entregadas);
        $this->assertSame(1, $tablero->pensionados->pendientes);
        $this->assertSame(2, $tablero->noPensionados->total);
        $this->assertSame(1, $tablero->noPensionados->entregadas);
        $this->assertSame(1, $tablero->noPensionados->pendientes);
    }

    /** HU-23 criterio 2: sin información el tablero muestra ceros y "Sin datos". */
    public function test_el_tablero_sin_informacion_muestra_ceros_y_sin_datos(): void
    {
        $tablero = (new ConsultarTablero(
            new UsuarioRepositorioEnMemoria(),
            $this->roles,
            new MenuRepositorioEnMemoria(),
            new ReservaRepositorioEnMemoria(),
        ))->ejecutar();

        $this->assertSame(0, $tablero->empleadosHabilitados);
        $this->assertSame('Sin datos', $tablero->estadoMenu);
        $this->assertSame(0, $tablero->totalReservasHoy());
        $this->assertSame(0, $tablero->pensionados->pendientes);
        $this->assertSame(0, $tablero->noPensionados->entregadas);
    }

    // ---------------------------------------------------------------- HU-24

    /** HU-24 criterio 1: pasadas las 09:00 la lista trae el total y cada persona con su casilla de control. */
    public function test_la_lista_diaria_pasadas_las_nueve_trae_el_total_y_las_personas(): void
    {
        $this->reserva(2, self::JUAN, '2026-11-16');
        $this->reserva(3, self::LUIS, '2026-11-16', EstadoReserva::Entregada);
        $this->reserva(3, self::LUIS, '2026-11-16', EstadoReserva::Cancelada);   // no aparece

        $lista = $this->listaDiaria()->ejecutar();

        $this->assertSame(2, $lista->total);
        $this->assertSame(2, count($lista->grupos));
        $this->assertSame('Juan Pérez', $lista->grupos[0]->personas[0]->nombre);
        $this->assertSame(false, $lista->grupos[0]->personas[0]->entregada);
        $this->assertSame(true, $lista->grupos[1]->personas[0]->entregada);
    }

    /** HU-24 criterio 2: cada familiar queda debajo de su empleado titular. */
    public function test_la_lista_diaria_agrupa_a_los_familiares_bajo_su_titular(): void
    {
        $this->reserva($this->personaMaria, self::JUAN, '2026-11-16');  // primero el familiar, luego el titular
        $this->reserva(2, self::JUAN, '2026-11-16');

        $lista = $this->listaDiaria()->ejecutar();

        $this->assertSame(2, $lista->total);
        $this->assertSame(1, count($lista->grupos));
        $this->assertSame('Juan Pérez', $lista->grupos[0]->nombreTitular);
        $this->assertSame('Juan Pérez', $lista->grupos[0]->personas[0]->nombre);
        $this->assertTrue($lista->grupos[0]->personas[0]->esTitular);
        $this->assertSame('María López', $lista->grupos[0]->personas[1]->nombre);
        $this->assertFalse($lista->grupos[0]->personas[1]->esTitular);
    }

    /** HU-24 criterio 3: antes de las 09:00 la lista todavía no está disponible. */
    public function test_la_lista_diaria_antes_de_las_nueve_no_esta_disponible(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:59'));
        $this->reserva(2, self::JUAN, '2026-11-16');

        $mensaje = $this->mensajeDeRechazo(fn () => $this->listaDiaria()->ejecutar());

        $this->assertStringContainsString('disponible después de las 09:00', $mensaje);
    }

    /** HU-24 criterio 4: sin reservas el total es cero. */
    public function test_la_lista_diaria_sin_reservas_muestra_total_cero(): void
    {
        $lista = $this->listaDiaria()->ejecutar();

        $this->assertSame(0, $lista->total);
        $this->assertSame([], $lista->grupos);
    }

    // ---------------------------------------------------------------- HU-25

    /** HU-25 criterio 1: el CSV del rango trae fecha, usuario, menú y estado de cada reserva. */
    public function test_exportar_reservas_a_csv_trae_fecha_usuario_menu_y_estado(): void
    {
        $this->reserva(2, self::JUAN, '2026-11-16');
        $this->reserva($this->personaMaria, self::JUAN, '2026-11-17', EstadoReserva::Entregada);

        $archivo = $this->exportador()->ejecutar(new DateTimeImmutable('2026-11-16'), new DateTimeImmutable('2026-11-19'), 'csv');

        $this->assertSame('reservas_2026-11-16_a_2026-11-19.csv', $archivo->nombre);
        $this->assertStringContainsString('Fecha;Usuario;Menú;Estado', $archivo->contenido);
        $this->assertStringContainsString('"16/11/2026";"Juan Pérez";"Sopa de maní / Pique macho";"Confirmada"', $archivo->contenido);
        $this->assertStringContainsString('"17/11/2026";"María López";"Sopa de maní / Pique macho";"Entregada"', $archivo->contenido);
    }

    /** HU-25 criterio 1 (Excel): el mismo reporte sale como hoja de cálculo. */
    public function test_exportar_reservas_a_excel_arma_la_hoja_con_las_mismas_columnas(): void
    {
        $this->reserva(2, self::JUAN, '2026-11-16');

        $archivo = $this->exportador()->ejecutar(new DateTimeImmutable('2026-11-16'), new DateTimeImmutable('2026-11-16'), 'xlsx');

        $this->assertSame('reservas_2026-11-16_a_2026-11-16.xlsx', $archivo->nombre);
        $hoja = json_decode($archivo->contenido, true);
        $this->assertSame(['Fecha', 'Usuario', 'Menú', 'Estado'], $hoja['encabezados']);
        $this->assertSame(['16/11/2026', 'Juan Pérez', 'Sopa de maní / Pique macho', 'Confirmada'], $hoja['filas'][0]);
    }

    /** HU-25 criterio 2: sin reservas en el rango se informa y no se genera el archivo. */
    public function test_exportar_sin_reservas_en_el_rango_informa_y_no_genera_archivo(): void
    {
        $this->reserva(2, self::JUAN, '2026-11-16');

        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->exportador()->ejecutar(new DateTimeImmutable('2026-12-01'), new DateTimeImmutable('2026-12-31'), 'csv')
        );

        $this->assertStringContainsString('No hay reservas en el rango seleccionado', $mensaje);
    }

    /** HU-25 criterio 3: sin rango de fechas se pide completarlo. */
    public function test_exportar_sin_rango_pide_completar_las_fechas(): void
    {
        $this->reserva(2, self::JUAN, '2026-11-16');

        $mensaje = $this->mensajeDeRechazo(fn () => $this->exportador()->ejecutar(null, null, 'csv'));

        $this->assertStringContainsString('Completa el rango de fechas', $mensaje);
    }

    // ---------------------------------------------------------------- HU-26

    /** HU-26 criterio 1: pensionada con 12 almuerzos = 12 × 15 = 180 Bs. */
    public function test_la_liquidacion_de_una_pensionada_con_12_almuerzos_es_180(): void
    {
        $this->pensionActivaDeOctubre($this->personaMaria, self::JUAN);
        $this->reservasDeOctubre($this->personaMaria, self::JUAN, 12, Modalidad::Pensionada);

        $fila = $this->filaDe($this->liquidar(), $this->personaMaria);

        $this->assertSame(12, $fila->cantidad);
        $this->assertSame(180.0, $fila->total);
        $this->assertSame(0.0, $fila->penalidad);
    }

    /** HU-26 criterio 2: pensionada con menos de 10 almuerzos paga la penalidad equivalente a 10 (150 Bs). */
    public function test_la_liquidacion_de_una_pensionada_con_menos_de_10_cobra_150(): void
    {
        $this->pensionActivaDeOctubre($this->personaRosa, self::LUIS);
        $this->reservasDeOctubre($this->personaRosa, self::LUIS, 4, Modalidad::Pensionada);

        $fila = $this->filaDe($this->liquidar(), $this->personaRosa);

        $this->assertSame(4, $fila->cantidad);
        $this->assertSame(90.0, $fila->penalidad);
        $this->assertSame(150.0, $fila->total);
    }

    /** HU-26 criterio 2 (borde): una pensionada que no consumió nada también paga los 150 Bs. */
    public function test_la_liquidacion_de_una_pensionada_sin_consumo_cobra_150(): void
    {
        $this->pensionActivaDeOctubre($this->personaRosa, self::LUIS);
        $this->reservasDeOctubre(2, self::JUAN, 1, Modalidad::Diaria);   // alguien más sí consumió

        $fila = $this->filaDe($this->liquidar(), $this->personaRosa);

        $this->assertSame(0, $fila->cantidad);
        $this->assertSame(150.0, $fila->total);
    }

    /** HU-26 criterio 3: modalidad diaria con 5 almuerzos = 5 × 18 = 90 Bs. */
    public function test_la_liquidacion_de_modalidad_diaria_con_5_almuerzos_es_90(): void
    {
        $this->reservasDeOctubre(2, self::JUAN, 5, Modalidad::Diaria);

        $fila = $this->filaDe($this->liquidar(), 2);

        $this->assertSame(5, $fila->cantidad);
        $this->assertSame(90.0, $fila->total);
    }

    /** HU-26 criterio 4: el total a descontar al titular suma lo suyo y lo de sus familiares. */
    public function test_la_liquidacion_consolida_el_total_del_titular_con_sus_familiares(): void
    {
        $this->reservasDeOctubre(2, self::JUAN, 5, Modalidad::Diaria);                                   // 90
        $this->pensionActivaDeOctubre($this->personaMaria, self::JUAN);
        $this->reservasDeOctubre($this->personaMaria, self::JUAN, 12, Modalidad::Pensionada);            // 180
        $this->pensionActivaDeOctubre($this->personaRosa, self::LUIS);
        $this->reservasDeOctubre($this->personaRosa, self::LUIS, 4, Modalidad::Pensionada);              // 150

        $resultado = $this->liquidar();

        $this->assertSame(270.0, $resultado->totalesPorEmpleado[self::JUAN]['total']);
        $this->assertSame('Juan Pérez', $resultado->totalesPorEmpleado[self::JUAN]['nombre']);
        $this->assertSame(150.0, $resultado->totalesPorEmpleado[self::LUIS]['total']);
        $this->assertSame(420.0, $resultado->totalGeneral);
    }

    /** HU-26 criterio 5: la reserva que no se canceló y no se retiró se cobra al titular. */
    public function test_la_liquidacion_cobra_la_reserva_a_la_que_no_asistio(): void
    {
        $this->reserva(2, self::JUAN, '2026-10-05', EstadoReserva::Entregada);
        $this->reserva(2, self::JUAN, '2026-10-06', EstadoReserva::Confirmada);   // no asistió y no canceló

        $fila = $this->filaDe($this->liquidar(), 2);

        $this->assertSame(2, $fila->cantidad);
        $this->assertSame(36.0, $fila->total);
    }

    /** HU-26 criterio 6: la reserva cancelada a tiempo no se cobra. */
    public function test_la_liquidacion_no_cobra_la_reserva_cancelada(): void
    {
        $this->reserva(2, self::JUAN, '2026-10-05', EstadoReserva::Entregada);
        $this->reserva(2, self::JUAN, '2026-10-06', EstadoReserva::Cancelada);

        $fila = $this->filaDe($this->liquidar(), 2);

        $this->assertSame(1, $fila->cantidad);
        $this->assertSame(18.0, $fila->total);
    }

    // ---------------------------------------------------------------- HU-27

    /** HU-27 criterio 1: con el mes generado y cerrado, la planilla trae las columnas COD, NOM, MOD, CANT y TOT. */
    public function test_la_planilla_del_mes_cerrado_trae_cod_nom_mod_cant_y_tot(): void
    {
        $this->reservasDeOctubre(2, self::JUAN, 5, Modalidad::Diaria);
        $this->pensionActivaDeOctubre($this->personaMaria, self::JUAN);
        $this->reservasDeOctubre($this->personaMaria, self::JUAN, 12, Modalidad::Pensionada);
        $this->liquidar();
        $octubre = new DateTimeImmutable('2026-10-01');
        (new CerrarLiquidacion($this->liquidaciones))->ejecutar($octubre);

        $archivo = (new ExportarPlanilla($this->liquidaciones, $this->usuarios, $this->personas, new GeneradorHojaCalculoEnMemoria()))
            ->ejecutar($octubre);

        $this->assertSame('planilla_2026-10.xlsx', $archivo->nombre);
        $hoja = json_decode($archivo->contenido, true);
        $this->assertSame(['COD', 'NOM', 'MOD', 'CANT', 'TOT'], $hoja['encabezados']);
        $this->assertSame([self::JUAN, 'Juan Pérez', 'Diaria', 5, 90], $hoja['filas'][0]);
        $this->assertSame([self::JUAN, 'Juan Pérez', 'Pensionada', 12, 180], $hoja['filas'][1]);
    }

    /** HU-27 criterio 2: sin la liquidación generada y cerrada no se exporta y se indica cerrar el mes. */
    public function test_la_planilla_no_se_exporta_si_el_mes_no_esta_cerrado(): void
    {
        $octubre = new DateTimeImmutable('2026-10-01');
        $exportar = new ExportarPlanilla($this->liquidaciones, $this->usuarios, $this->personas, new GeneradorHojaCalculoEnMemoria());

        $sinGenerar = $this->mensajeDeRechazo(fn () => $exportar->ejecutar($octubre));

        $this->reservasDeOctubre(2, self::JUAN, 5, Modalidad::Diaria);
        $this->liquidar();   // generada, pero todavía abierta
        $sinCerrar = $this->mensajeDeRechazo(fn () => $exportar->ejecutar($octubre));

        $this->assertStringContainsString('primero debe cerrarse el mes', mb_strtolower($sinGenerar));
        $this->assertStringContainsString('primero debe cerrarse el mes', mb_strtolower($sinCerrar));
    }

    /** HU-27: el archivo que se descarga es un .xlsx real (un zip con la hoja y los datos). */
    public function test_el_generador_crea_un_xlsx_valido(): void
    {
        $contenido = (new GeneradorXlsx())->generar('Planilla', ['COD', 'NOM'], [[2, 'Juan Pérez & Cía']]);
        $ruta = tempnam(sys_get_temp_dir(), 'prueba');
        file_put_contents($ruta, $contenido);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($ruta) === true);
        $hoja = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($ruta);

        $this->assertStringContainsString('Juan Pérez &amp; Cía', $hoja);
        $this->assertStringContainsString('<v>2</v>', $hoja);
    }
}