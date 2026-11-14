<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\EliminarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFamiliares;
use RumboAlCielo\Aplicacion\CasosDeUso\PublicarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFeriado;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarPlato;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\ReservarAlmuerzo;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\AlmacenFotosEnMemoria;
use RumboAlCielo\Tests\Dobles\FamiliarRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\FeriadoRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\MenuRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PensionRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\ReservaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-16 Reservar mi almuerzo, HU-17 Reservar para mis familiares y HU-20 Confirmación de reserva. */
final class ReservarAlmuerzoTest extends TestCase
{
    private const EMPLEADO = 2;          // Juan Pérez, persona 2
    private const OTRO_EMPLEADO = 3;     // Luis Mamani
    private const PERSONA_EMPLEADO = 2;
    private const LUNES = '2026-11-16';
    private const MARTES = '2026-11-17';
    private const MIERCOLES = '2026-11-18';
    private const VIERNES = '2026-11-20';

    private ReservaRepositorioEnMemoria $reservas;
    private PensionRepositorioEnMemoria $pensiones;
    private FamiliarRepositorioEnMemoria $familiares;
    private FeriadoRepositorioEnMemoria $feriados;
    private MenuRepositorioEnMemoria $menus;
    private PersonaRepositorioEnMemoria $personas;
    private int $idMaria;      // familiar de Juan
    private int $idPedro;      // familiar de Juan
    private int $idAjeno;      // familiar de Luis
    private ReservarAlmuerzo $reservar;

    protected function setUp(): void
    {
        // Lunes 16/11/2026, 08:30: faltan 30 minutos para el corte de las 09:00.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:30'));

        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $usuarios = new UsuarioRepositorioEnMemoria();
        $this->personas = new PersonaRepositorioEnMemoria();
        $this->familiares = new FamiliarRepositorioEnMemoria();
        $this->pensiones = new PensionRepositorioEnMemoria();
        $this->reservas = new ReservaRepositorioEnMemoria();
        $this->feriados = new FeriadoRepositorioEnMemoria();
        $this->menus = new MenuRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($usuarios, $this->personas, $roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);
        $registrarUsuario->ejecutar('Luis', 'Mamani', 'luis@correo.com', '71000000', 2);

        $registrarFamiliar = new RegistrarFamiliar($this->familiares, $this->personas, $usuarios, $roles);
        $this->idMaria = $registrarFamiliar->ejecutar(self::EMPLEADO, 'María', 'López', 'conyuge');
        $this->idPedro = $registrarFamiliar->ejecutar(self::EMPLEADO, 'Pedro', 'Pérez', 'hijo');
        $this->idAjeno = $registrarFamiliar->ejecutar(self::OTRO_EMPLEADO, 'Rosa', 'Quispe', 'hija');

        // Menú publicado de lunes a jueves de esa semana.
        $registrarPlato = new RegistrarPlato($this->menus, new AlmacenFotosEnMemoria());
        foreach ([self::LUNES, self::MARTES, self::MIERCOLES, '2026-11-19'] as $dia) {
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
        (new PublicarMenu($this->menus, $this->feriados))->ejecutar(self::LUNES);

        $this->reservar = new ReservarAlmuerzo(
            $this->reservas,
            $this->pensiones,
            $this->familiares,
            $usuarios,
            $this->personas,
            $this->menus,
            $this->feriados,
        );
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
    }

    private function dia(string $fecha): DateTimeImmutable
    {
        return new DateTimeImmutable($fecha);
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

    private function pension(int $idPersona, EstadoPension $estado): void
    {
        $this->pensiones->guardar(
            new Pension(0, $idPersona, self::EMPLEADO, new DateTimeImmutable('2026-11-01'), Pension::TARIFA, $estado)
        );
    }

    // ---------------------------------------------------------------- HU-16

    /** HU-16 criterio 1: antes de las 09:00, sin pensión, se registra la reserva a 18 Bs. */
    public function test_reserva_el_almuerzo_del_dia_a_18_bs_antes_de_las_0900(): void
    {
        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $this->assertSame([['Juan Pérez', 18.0]], $resultado->registradas);
        $guardadas = $this->reservas->todas();
        $this->assertSame(1, count($guardadas));
        $this->assertSame(EstadoReserva::Confirmada, $guardadas[0]->getEstado());
        $this->assertSame(Modalidad::Diaria, $guardadas[0]->getModalidad());
        $this->assertSame(18.0, $guardadas[0]->getTarifa());
        $this->assertSame(32, strlen($guardadas[0]->getCodigoQR()));
    }

    /** HU-16 criterio 2: desde las 09:00 en punto el horario de reservas cerró. */
    public function test_a_las_0900_en_punto_rechaza_la_reserva_del_mismo_dia(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 09:00'));

        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES))
        );

        $this->assertStringContainsString('El horario de reservas cerró', $mensaje);
        $this->assertSame(0, count($this->reservas->todas()));
    }

    /** HU-16 criterio 2: a las 08:59 todavía se puede reservar. */
    public function test_a_las_0859_todavia_permite_reservar(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:59'));

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $this->assertSame(1, count($resultado->registradas));
    }

    /** HU-16 criterio 3: pasadas las 09:00 se puede reservar para el día siguiente, a 18 Bs. */
    public function test_pasadas_las_0900_permite_reservar_para_el_dia_siguiente(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 10:00'));

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::MARTES));

        $this->assertSame([['Juan Pérez', 18.0]], $resultado->registradas);
        $this->assertSame('2026-11-17', $this->reservas->todas()[0]->getFecha()->format('Y-m-d'));
    }

    /** HU-16 criterio 4: con pensión activa en el mes la reserva se registra a 15 Bs. */
    public function test_con_pension_activa_la_reserva_cuesta_15_bs(): void
    {
        $this->pension(self::PERSONA_EMPLEADO, EstadoPension::Activa);

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $this->assertSame([['Juan Pérez', 15.0]], $resultado->registradas);
        $this->assertSame(Modalidad::Pensionada, $this->reservas->todas()[0]->getModalidad());
    }

    /** HU-16 criterio 5: viernes, feriado o día sin menú publicado no tienen atención. */
    public function test_rechaza_viernes_feriados_y_dias_sin_menu_publicado(): void
    {
        $viernes = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::VIERNES))
        );
        $this->assertSame('Para ese día no hay atención ni menú disponible.', $viernes);

        (new RegistrarFeriado($this->feriados))->ejecutar(self::MIERCOLES, 'Día del paro');
        $feriado = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::MIERCOLES))
        );
        $this->assertSame('Para ese día no hay atención ni menú disponible.', $feriado);

        $sinMenu = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia('2026-11-24'))
        );
        $this->assertSame('Para ese día no hay atención ni menú disponible.', $sinMenu);
        $this->assertSame(0, count($this->reservas->todas()));
    }

    /** HU-16 criterio 6: una persona no puede tener dos almuerzos el mismo día. */
    public function test_no_duplica_la_reserva_de_una_persona_en_el_mismo_dia(): void
    {
        $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $this->assertSame([], $resultado->registradas);
        $this->assertSame([['Juan Pérez', 'Ya tiene una reserva para ese día.']], $resultado->omitidas);
        $this->assertSame(1, count($this->reservas->todas()));
    }

    /** HU-16 criterio 6: tras cancelar, la persona puede reservar de nuevo ese día (la cancelada no cuenta). */
    public function test_despues_de_cancelar_se_puede_reservar_otra_vez(): void
    {
        $this->reservas->agregar(new Reserva(
            1,
            self::PERSONA_EMPLEADO,
            self::EMPLEADO,
            1,
            $this->dia(self::LUNES),
            Modalidad::Diaria,
            18.0,
            EstadoReserva::Cancelada,
            'qr1',
        ));

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $this->assertSame(1, count($resultado->registradas));
        $this->assertSame(2, count($this->reservas->todas()));
    }

    /** HU-16 criterio 7: con una solicitud de pensión pendiente no se registra la reserva ocasional. */
    public function test_con_pension_pendiente_no_registra_la_reserva_ocasional(): void
    {
        $this->pension(self::PERSONA_EMPLEADO, EstadoPension::Pendiente);

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [], $this->dia(self::LUNES));

        $this->assertSame([], $resultado->registradas);
        $this->assertSame([['Juan Pérez', 'Tiene una solicitud de pensión pendiente en ese mes.']], $resultado->omitidas);
        $this->assertSame(0, count($this->reservas->todas()));
    }

    // ---------------------------------------------------------------- HU-17

    /** HU-17 criterio 1: una reserva por cada persona, a cargo del empleado titular, con su tarifa. */
    public function test_reserva_para_el_empleado_y_sus_familiares_con_el_empleado_como_titular(): void
    {
        $this->pension(2, EstadoPension::Activa);                       // Juan tiene pensión
        $personaMaria = $this->familiares->buscarPorId($this->idMaria)->getIdPersona();
        $this->pension($personaMaria, EstadoPension::Activa);           // María también

        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [$this->idMaria, $this->idPedro], $this->dia(self::LUNES));

        $this->assertSame(
            [['Juan Pérez', 15.0], ['María López', 15.0], ['Pedro Pérez', 18.0]],
            $resultado->registradas
        );
        $this->assertSame(48.0, $resultado->total());
        foreach ($this->reservas->todas() as $reserva) {
            $this->assertSame(self::EMPLEADO, $reserva->getIdUsuario());
        }
    }

    /** HU-17 criterio 2: al elegir a la persona solo aparecen el empleado y sus familiares activos. */
    public function test_solo_se_ofrecen_los_familiares_activos_del_propio_empleado(): void
    {
        (new EliminarFamiliar($this->familiares, $this->reservas))->ejecutar($this->idPedro, true);

        $filas = (new ListarFamiliares($this->familiares, $this->personas))->ejecutar(self::EMPLEADO);

        $this->assertSame(['María López'], array_map(fn ($fila) => $fila->nombreCompleto, $filas));
    }

    /** HU-17 criterio 3: no se puede reservar para alguien que no es familiar registrado del empleado. */
    public function test_rechaza_reservar_para_una_persona_que_no_es_su_familiar(): void
    {
        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [$this->idAjeno], $this->dia(self::LUNES))
        );

        $this->assertSame('Solo puedes reservar para ti y para tus familiares registrados.', $mensaje);
        $this->assertSame(0, count($this->reservas->todas()));
    }

    /** HU-17 criterio 3: un familiar eliminado tampoco se puede reservar. */
    public function test_rechaza_reservar_para_un_familiar_eliminado(): void
    {
        (new EliminarFamiliar($this->familiares, $this->reservas))->ejecutar($this->idPedro, true);

        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [$this->idPedro], $this->dia(self::LUNES))
        );

        $this->assertSame('Solo puedes reservar para ti y para tus familiares registrados.', $mensaje);
    }

    // ---------------------------------------------------------------- HU-20

    /** HU-20 criterio 1: la confirmación trae la fecha, la persona y el importe. */
    public function test_la_confirmacion_trae_fecha_persona_e_importe(): void
    {
        $resultado = $this->reservar->ejecutar(self::EMPLEADO, false, [$this->idMaria], $this->dia(self::LUNES));

        $this->assertSame('2026-11-16', $resultado->fecha->format('Y-m-d'));
        $this->assertSame([['María López', 18.0]], $resultado->registradas);
        $this->assertSame(18.0, $resultado->total());
    }

    /** HU-20 criterio 2: si la reserva no se puede guardar no hay confirmación ni se registra nada. */
    public function test_si_la_reserva_falla_no_se_guarda_ninguna(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 09:30'));

        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->reservar->ejecutar(self::EMPLEADO, true, [$this->idMaria, $this->idPedro], $this->dia(self::LUNES))
        );

        $this->assertStringContainsString('El horario de reservas cerró', $mensaje);
        $this->assertSame(0, count($this->reservas->todas()));
    }

    /** HU-20 criterio 3: por cada reserva nueva se avisa al administrador. */
    public function test_avisa_al_administrador_de_cada_reserva_nueva(): void
    {
        $resultado = $this->reservar->ejecutar(self::EMPLEADO, true, [$this->idMaria], $this->dia(self::LUNES));

        $this->assertSame(2, count($resultado->eventos));
        $this->assertSame('reserva.registrada', $resultado->eventos[0]['evento']);
        $this->assertSame('2026-11-16', $resultado->eventos[0]['datos']['fecha']);
        $this->assertTrue($resultado->eventos[0]['datos']['idReserva'] > 0);
    }
}