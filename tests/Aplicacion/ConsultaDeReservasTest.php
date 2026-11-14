<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarCalendarioReservas;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarReservasAdmin;
use RumboAlCielo\Aplicacion\CasosDeUso\PublicarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFeriado;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarPlato;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\AlmacenFotosEnMemoria;
use RumboAlCielo\Tests\Dobles\FamiliarRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\FeriadoRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\MenuRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\ReservaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-19 Consultar mis reservas en el calendario y HU-28 Consultar reservas (administrador). */
final class ConsultaDeReservasTest extends TestCase
{
    private const EMPLEADO = 2;          // Juan Pérez, persona 2
    private const OTRO_EMPLEADO = 3;     // Luis Mamani, persona 3

    private ReservaRepositorioEnMemoria $reservas;
    private FeriadoRepositorioEnMemoria $feriados;
    private int $personaMaria;
    private int $personaRosa;
    private ConsultarCalendarioReservas $calendario;
    private ConsultarReservasAdmin $consultaAdmin;

    protected function setUp(): void
    {
        // Lunes 16/11/2026, 08:30.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:30'));

        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $usuarios = new UsuarioRepositorioEnMemoria();
        $personas = new PersonaRepositorioEnMemoria();
        $familiares = new FamiliarRepositorioEnMemoria();
        $this->reservas = new ReservaRepositorioEnMemoria();
        $this->feriados = new FeriadoRepositorioEnMemoria();
        $menus = new MenuRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($usuarios, $personas, $roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);
        $registrarUsuario->ejecutar('Luis', 'Mamani', 'luis@correo.com', '71000000', 2);

        $registrarFamiliar = new RegistrarFamiliar($familiares, $personas, $usuarios, $roles);
        $idMaria = $registrarFamiliar->ejecutar(self::EMPLEADO, 'María', 'López', 'conyuge');
        $idRosa = $registrarFamiliar->ejecutar(self::OTRO_EMPLEADO, 'Rosa', 'Quispe', 'hija');
        $this->personaMaria = $familiares->buscarPorId($idMaria)->getIdPersona();
        $this->personaRosa = $familiares->buscarPorId($idRosa)->getIdPersona();

        // Menú publicado solo para la semana del 16 al 19 de noviembre.
        $registrarPlato = new RegistrarPlato($menus, new AlmacenFotosEnMemoria());
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
        (new PublicarMenu($menus, $this->feriados))->ejecutar('2026-11-16');
        (new RegistrarFeriado($this->feriados))->ejecutar('2026-11-19', 'Día de la Ciudad');

        $this->calendario = new ConsultarCalendarioReservas($this->reservas, $personas, $this->feriados, $menus);
        $this->consultaAdmin = new ConsultarReservasAdmin($this->reservas, $personas, $usuarios);
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
    }

    private function reserva(
        int $id,
        int $idPersona,
        int $idTitular,
        string $fecha,
        EstadoReserva $estado = EstadoReserva::Confirmada,
    ): void {
        $this->reservas->agregar(
            new Reserva($id, $idPersona, $idTitular, 1, new DateTimeImmutable($fecha), Modalidad::Diaria, 18.0, $estado, 'codigo-' . $id)
        );
    }

    /** @param \RumboAlCielo\Aplicacion\CasosDeUso\DiaDelCalendario[] $dias */
    private function dia(array $dias, string $fecha): \RumboAlCielo\Aplicacion\CasosDeUso\DiaDelCalendario
    {
        foreach ($dias as $dia) {
            if ($dia->fecha->format('Y-m-d') === $fecha) {
                return $dia;
            }
        }
        $this->fail("El calendario no tiene el día $fecha.");
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

    // ---------------------------------------------------------------- HU-19

    /** HU-19 criterio 1: el mes muestra solo lunes a jueves, con su estado: libre, confirmada, entregada o feriado. */
    public function test_el_calendario_muestra_de_lunes_a_jueves_con_el_estado_de_cada_dia(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, '2026-11-16');
        $this->reserva(2, $this->personaMaria, self::EMPLEADO, '2026-11-12', EstadoReserva::Entregada);

        $dias = $this->calendario->ejecutar(self::EMPLEADO);

        $this->assertSame(17, count($dias));   // noviembre 2026 tiene 17 días de lunes a jueves
        foreach ($dias as $dia) {
            $this->assertTrue((int) $dia->fecha->format('N') <= 4);
        }
        $this->assertSame('2026-11-02', $dias[0]->fecha->format('Y-m-d'));

        $confirmada = $this->dia($dias, '2026-11-16');
        $this->assertSame(EstadoReserva::Confirmada, $confirmada->reservas[0]->estado);
        $this->assertSame('Juan Pérez', $confirmada->reservas[0]->nombrePersona);
        $this->assertSame(EstadoReserva::Entregada, $this->dia($dias, '2026-11-12')->reservas[0]->estado);
        $this->assertTrue($this->dia($dias, '2026-11-17')->esLibre());
        $feriado = $this->dia($dias, '2026-11-19');
        $this->assertTrue($feriado->esFeriado());
        $this->assertSame('Día de la Ciudad', $feriado->motivoFeriado);
    }

    /** HU-19 criterio 2: solo se ven las reservas propias y las de los familiares, no las de otro empleado. */
    public function test_el_calendario_no_muestra_las_reservas_de_otros_empleados(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, '2026-11-16');
        $this->reserva(2, $this->personaMaria, self::EMPLEADO, '2026-11-16');
        $this->reserva(3, 3, self::OTRO_EMPLEADO, '2026-11-17');
        $this->reserva(4, $this->personaRosa, self::OTRO_EMPLEADO, '2026-11-17');

        $dias = $this->calendario->ejecutar(self::EMPLEADO);

        $nombres = array_map(fn ($r) => $r->nombrePersona, $this->dia($dias, '2026-11-16')->reservas);
        $this->assertSame(['Juan Pérez', 'María López'], $nombres);
        $this->assertSame([], $this->dia($dias, '2026-11-17')->reservas);
    }

    /** HU-19 criterio 1: una reserva cancelada deja el día libre. */
    public function test_una_reserva_cancelada_deja_el_dia_libre(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, '2026-11-17', EstadoReserva::Cancelada);

        $dias = $this->calendario->ejecutar(self::EMPLEADO);

        $this->assertTrue($this->dia($dias, '2026-11-17')->esLibre());
    }

    /** HU-19: cada día indica si todavía se puede reservar (menú publicado, sin feriado y antes del corte). */
    public function test_el_calendario_indica_en_que_dias_se_puede_reservar(): void
    {
        $dias = $this->calendario->ejecutar(self::EMPLEADO);

        $this->assertTrue($this->dia($dias, '2026-11-16')->abierto);    // hoy, 08:30
        $this->assertTrue($this->dia($dias, '2026-11-17')->abierto);    // mañana con menú
        $this->assertFalse($this->dia($dias, '2026-11-19')->abierto);   // feriado
        $this->assertFalse($this->dia($dias, '2026-11-23')->abierto);   // semana sin menú
        $this->assertFalse($this->dia($dias, '2026-11-12')->abierto);   // día pasado
    }

    /** HU-19: el empleado puede cancelar solo las reservas que aún no llegaron al corte. */
    public function test_el_calendario_marca_que_reservas_se_pueden_cancelar(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, '2026-11-16');
        $this->reserva(2, 2, self::EMPLEADO, '2026-11-12', EstadoReserva::Entregada);

        $dias = $this->calendario->ejecutar(self::EMPLEADO);

        $this->assertTrue($this->dia($dias, '2026-11-16')->reservas[0]->puedeCancelarse);
        $this->assertFalse($this->dia($dias, '2026-11-12')->reservas[0]->puedeCancelarse);
    }

    // ---------------------------------------------------------------- HU-28

    /** HU-28 criterio 1: las reservas del período con persona, titular y estado. */
    public function test_el_administrador_ve_las_reservas_de_un_periodo(): void
    {
        $this->reserva(1, $this->personaMaria, self::EMPLEADO, '2026-11-16');
        $this->reserva(2, 3, self::OTRO_EMPLEADO, '2026-11-17', EstadoReserva::Entregada);
        $this->reserva(3, 2, self::EMPLEADO, '2026-11-18');
        $this->reserva(4, 2, self::EMPLEADO, '2026-11-17', EstadoReserva::Cancelada);   // no se muestra

        $filas = $this->consultaAdmin->ejecutar(new DateTimeImmutable('2026-11-16'), new DateTimeImmutable('2026-11-17'));

        $this->assertSame(2, count($filas));
        $this->assertSame('María López', $filas[0]->nombrePersona);
        $this->assertSame('Juan Pérez', $filas[0]->nombreTitular);
        $this->assertSame(EstadoReserva::Confirmada, $filas[0]->estado);
        $this->assertSame('Luis Mamani', $filas[1]->nombrePersona);
        $this->assertSame(EstadoReserva::Entregada, $filas[1]->estado);
    }

    /** HU-28 criterio 1: sin fecha final se consulta solo ese día. */
    public function test_la_consulta_de_una_sola_fecha_solo_trae_ese_dia(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, '2026-11-16');
        $this->reserva(2, 2, self::EMPLEADO, '2026-11-17');

        $filas = $this->consultaAdmin->ejecutar(new DateTimeImmutable('2026-11-17'));

        $this->assertSame(1, count($filas));
        $this->assertSame('2026-11-17', $filas[0]->fecha->format('Y-m-d'));
    }

    /** HU-28 criterio 2: al filtrar por un empleado solo salen sus reservas y las de sus familiares. */
    public function test_al_filtrar_por_empleado_solo_salen_sus_reservas_y_las_de_sus_familiares(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, '2026-11-16');
        $this->reserva(2, $this->personaMaria, self::EMPLEADO, '2026-11-16');
        $this->reserva(3, 3, self::OTRO_EMPLEADO, '2026-11-16');

        $filas = $this->consultaAdmin->ejecutar(
            new DateTimeImmutable('2026-11-16'),
            new DateTimeImmutable('2026-11-16'),
            self::EMPLEADO,
        );

        $this->assertSame(['Juan Pérez', 'María López'], array_map(fn ($f) => $f->nombrePersona, $filas));
    }

    /** HU-28 criterio 3: sin reservas en la fecha o el filtro, la lista queda vacía y se informa. */
    public function test_sin_reservas_la_consulta_informa_que_no_se_encontraron(): void
    {
        $this->reserva(1, 3, self::OTRO_EMPLEADO, '2026-11-16');

        $filas = $this->consultaAdmin->ejecutar(new DateTimeImmutable('2026-11-16'), null, self::EMPLEADO);

        $this->assertSame([], $filas);
        $this->assertSame(
            'No se encontraron reservas para la fecha o el filtro elegido.',
            ConsultarReservasAdmin::SIN_RESULTADOS
        );
    }

    /** HU-28: un rango con la fecha final anterior a la inicial se rechaza. */
    public function test_rechaza_un_rango_con_la_fecha_final_anterior_a_la_inicial(): void
    {
        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->consultaAdmin->ejecutar(new DateTimeImmutable('2026-11-17'), new DateTimeImmutable('2026-11-16'))
        );

        $this->assertSame('La fecha final no puede ser anterior a la fecha inicial.', $mensaje);
    }
}