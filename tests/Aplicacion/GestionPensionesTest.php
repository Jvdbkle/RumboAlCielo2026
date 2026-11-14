<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\ArmarFilasPension;
use RumboAlCielo\Aplicacion\CasosDeUso\ConfirmarPension;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMisPensiones;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarPensiones;
use RumboAlCielo\Aplicacion\CasosDeUso\RechazarPension;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\RevocarPension;
use RumboAlCielo\Aplicacion\CasosDeUso\SolicitarPension;
use RumboAlCielo\Dominio\Entidades\Pension;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\FamiliarRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PensionRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\ReservaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-08 Solicitar pensión, HU-09 Confirmar o rechazar, HU-10 Revocar y HU-11 Consultar mis pensiones. */
final class GestionPensionesTest extends TestCase
{
    private const EMPLEADO = 2;          // Juan Pérez, persona 2
    private const OTRO_EMPLEADO = 3;     // Luis Mamani
    private const PERSONA_EMPLEADO = 2;
    private const DICIEMBRE = '2026-12-01';

    private PensionRepositorioEnMemoria $pensiones;
    private ReservaRepositorioEnMemoria $reservas;
    private FamiliarRepositorioEnMemoria $familiares;
    private int $idMaria;      // familiar de Juan
    private int $idPedro;      // familiar de Juan
    private int $idAjeno;      // familiar de Luis
    private SolicitarPension $solicitar;
    private ConfirmarPension $confirmar;
    private RechazarPension $rechazar;
    private RevocarPension $revocar;
    private ConsultarMisPensiones $misPensiones;
    private ListarPensiones $listarPensiones;

    protected function setUp(): void
    {
        // Viernes 13/11/2026: el mes siguiente (diciembre) aún no inició.
        Reloj::fijar(new DateTimeImmutable('2026-11-13 10:00'));

        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $usuarios = new UsuarioRepositorioEnMemoria();
        $personas = new PersonaRepositorioEnMemoria();
        $this->familiares = new FamiliarRepositorioEnMemoria();
        $this->pensiones = new PensionRepositorioEnMemoria();
        $this->reservas = new ReservaRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($usuarios, $personas, $roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);
        $registrarUsuario->ejecutar('Luis', 'Mamani', 'luis@correo.com', '71000000', 2);

        $registrarFamiliar = new RegistrarFamiliar($this->familiares, $personas, $usuarios, $roles);
        $this->idMaria = $registrarFamiliar->ejecutar(self::EMPLEADO, 'María', 'López', 'conyuge');
        $this->idPedro = $registrarFamiliar->ejecutar(self::EMPLEADO, 'Pedro', 'Pérez', 'hijo');
        $this->idAjeno = $registrarFamiliar->ejecutar(self::OTRO_EMPLEADO, 'Rosa', 'Quispe', 'hija');

        $armarFilas = new ArmarFilasPension($personas, $usuarios, $this->reservas);
        $this->solicitar = new SolicitarPension($this->pensiones, $this->familiares, $usuarios, $personas);
        $this->confirmar = new ConfirmarPension($this->pensiones);
        $this->rechazar = new RechazarPension($this->pensiones);
        $this->revocar = new RevocarPension($this->pensiones, $this->reservas);
        $this->misPensiones = new ConsultarMisPensiones($this->pensiones, $armarFilas);
        $this->listarPensiones = new ListarPensiones($this->pensiones, $armarFilas);
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
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

    /** Deja una pensión ya guardada para una persona y un mes, con el estado indicado. */
    private function pensionExistente(int $idPersona, string $mes, EstadoPension $estado): int
    {
        return $this->pensiones->guardar(
            new Pension(0, $idPersona, self::EMPLEADO, new DateTimeImmutable($mes), Pension::TARIFA, $estado)
        );
    }

    private function reserva(int $id, string $fecha, EstadoReserva $estado, Modalidad $modalidad = Modalidad::Pensionada): Reserva
    {
        return new Reserva($id, self::PERSONA_EMPLEADO, self::EMPLEADO, 1, new DateTimeImmutable($fecha), $modalidad, 15.0, $estado, 'qr' . $id);
    }

    // ---------------------------------------------------------------- HU-08

    /** HU-08 criterio 1: una solicitud pendiente por cada persona elegida, con el empleado como titular. */
    public function test_solicita_la_pension_para_el_empleado_y_un_familiar(): void
    {
        $resultado = $this->solicitar->ejecutar(self::EMPLEADO, true, [$this->idMaria]);

        $this->assertSame(['Juan Pérez', 'María López'], $resultado->registradas);
        $this->assertSame([], $resultado->yaExistentes);
        $filas = $this->misPensiones->ejecutar(self::EMPLEADO);
        $this->assertSame(2, count($filas));
        foreach ($filas as $fila) {
            $this->assertSame(EstadoPension::Pendiente, $fila->estado);
            $this->assertSame('Juan Pérez', $fila->nombreTitular);
            $this->assertSame(self::DICIEMBRE, $fila->mes->format('Y-m-d'));
        }
    }

    /** HU-08 criterio 2: si el mes ya inició, la solicitud se rechaza y no se guarda nada. */
    public function test_rechaza_la_solicitud_de_un_mes_que_ya_inicio(): void
    {
        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->solicitar->ejecutar(self::EMPLEADO, true, [], new DateTimeImmutable('2026-11-20'))
        );

        $this->assertStringContainsString('ya inició', $mensaje);
        $this->assertSame([], $this->misPensiones->ejecutar(self::EMPLEADO));
    }

    /** HU-08 criterio 3: no se duplica la solicitud y se informa el estado de la existente. */
    public function test_no_duplica_la_solicitud_e_informa_el_estado_de_la_existente(): void
    {
        $this->solicitar->ejecutar(self::EMPLEADO, true, []);

        $resultado = $this->solicitar->ejecutar(self::EMPLEADO, true, [$this->idMaria]);

        $this->assertSame(['María López'], $resultado->registradas);
        $this->assertSame([['Juan Pérez', 'Pendiente']], $resultado->yaExistentes);
        $this->assertSame(2, count($this->misPensiones->ejecutar(self::EMPLEADO)));
    }

    public function test_exige_elegir_al_menos_una_persona(): void
    {
        $mensaje = $this->mensajeDeRechazo(fn () => $this->solicitar->ejecutar(self::EMPLEADO, false, []));

        $this->assertStringContainsString('al menos una persona', $mensaje);
    }

    /** Seguridad: no se puede pedir la pensión de un familiar de otro empleado, y no se guarda ninguna. */
    public function test_rechaza_un_familiar_de_otro_empleado_y_no_guarda_nada(): void
    {
        $mensaje = $this->mensajeDeRechazo(
            fn () => $this->solicitar->ejecutar(self::EMPLEADO, true, [$this->idAjeno])
        );

        $this->assertStringContainsString('no es válido', $mensaje);
        $this->assertSame([], $this->misPensiones->ejecutar(self::EMPLEADO));
    }

    public function test_una_solicitud_rechazada_puede_pedirse_de_nuevo(): void
    {
        $this->solicitar->ejecutar(self::EMPLEADO, true, []);
        $this->rechazar->ejecutar(1);

        $resultado = $this->solicitar->ejecutar(self::EMPLEADO, true, []);

        $this->assertSame(['Juan Pérez'], $resultado->registradas);
        $this->assertSame(EstadoPension::Pendiente, $this->pensiones->buscarPorId(1)->getEstado());
        $this->assertSame(1, count($this->misPensiones->ejecutar(self::EMPLEADO)));
    }

    // ---------------------------------------------------------------- HU-09

    /** HU-09 criterio 1: al confirmar, la pensión queda activa con 15 Bs y el empleado como titular. */
    public function test_confirma_una_solicitud_pendiente(): void
    {
        $this->solicitar->ejecutar(self::EMPLEADO, true, []);

        $this->confirmar->ejecutar(1);

        $pension = $this->pensiones->buscarPorId(1);
        $this->assertSame(EstadoPension::Activa, $pension->getEstado());
        $this->assertSame(15.0, $pension->getTarifa());
        $this->assertSame(self::EMPLEADO, $pension->getIdUsuario());
    }

    /** HU-09 criterio 2: al rechazar, la solicitud queda rechazada y el empleado puede verlo. */
    public function test_rechaza_una_solicitud_y_el_empleado_ve_que_fue_rechazada(): void
    {
        $this->solicitar->ejecutar(self::EMPLEADO, false, [$this->idPedro]);

        $this->rechazar->ejecutar(1);

        $filas = $this->misPensiones->ejecutar(self::EMPLEADO);
        $this->assertSame('Pedro Pérez', $filas[0]->nombrePersona);
        $this->assertSame(EstadoPension::Rechazada, $filas[0]->estado);
    }

    /** HU-09 criterio 3: si el mes de la solicitud ya inició, no se puede confirmar. */
    public function test_no_confirma_una_solicitud_de_un_mes_que_ya_inicio(): void
    {
        $id = $this->pensionExistente(self::PERSONA_EMPLEADO, '2026-11-01', EstadoPension::Pendiente);

        $mensaje = $this->mensajeDeRechazo(fn () => $this->confirmar->ejecutar($id));

        $this->assertStringContainsString('plazo para la pensión de ese mes está cerrado', $mensaje);
        $this->assertSame(EstadoPension::Pendiente, $this->pensiones->buscarPorId($id)->getEstado());
    }

    public function test_solo_se_confirma_una_solicitud_pendiente(): void
    {
        $id = $this->pensionExistente(self::PERSONA_EMPLEADO, self::DICIEMBRE, EstadoPension::Activa);

        $mensaje = $this->mensajeDeRechazo(fn () => $this->confirmar->ejecutar($id));

        $this->assertStringContainsString('no está pendiente', $mensaje);
    }

    /** El administrador ve las solicitudes del mes actual en adelante, no las de meses pasados. */
    public function test_el_administrador_ve_las_solicitudes_desde_el_mes_actual(): void
    {
        $this->pensionExistente(self::PERSONA_EMPLEADO, '2026-10-01', EstadoPension::Activa);
        $this->solicitar->ejecutar(self::EMPLEADO, true, []);

        $filas = $this->listarPensiones->ejecutar();

        $this->assertSame(1, count($filas));
        $this->assertSame(self::DICIEMBRE, $filas[0]->mes->format('Y-m-d'));
    }

    // ---------------------------------------------------------------- HU-10

    /** HU-10 criterio 1: sin almuerzos entregados se revoca y las reservas quedan como normales a 18 Bs. */
    public function test_revoca_una_pension_sin_consumos_y_las_reservas_pasan_a_18_bs(): void
    {
        $id = $this->pensionExistente(self::PERSONA_EMPLEADO, self::DICIEMBRE, EstadoPension::Activa);
        $this->reservas->agregar($this->reserva(1, '2026-12-01', EstadoReserva::Confirmada));
        $this->reservas->agregar($this->reserva(2, '2026-12-02', EstadoReserva::Confirmada));

        $this->revocar->ejecutar($id);

        $this->assertNull($this->pensiones->buscarPorId($id));
        foreach ($this->reservas->todas() as $reserva) {
            $this->assertSame(Modalidad::Diaria, $reserva->getModalidad());
            $this->assertSame(18.0, $reserva->getTarifa());
            $this->assertSame(EstadoReserva::Confirmada, $reserva->getEstado());
        }
    }

    /** HU-10 criterio 2: con un almuerzo ya consumido en el mes, no se puede revocar. */
    public function test_no_revoca_si_ya_hay_un_almuerzo_entregado(): void
    {
        $id = $this->pensionExistente(self::PERSONA_EMPLEADO, self::DICIEMBRE, EstadoPension::Activa);
        $this->reservas->agregar($this->reserva(1, '2026-12-01', EstadoReserva::Entregada));
        $this->reservas->agregar($this->reserva(2, '2026-12-02', EstadoReserva::Confirmada));

        $mensaje = $this->mensajeDeRechazo(fn () => $this->revocar->ejecutar($id));

        $this->assertStringContainsString('ya se registró un consumo', $mensaje);
        $this->assertSame(EstadoPension::Activa, $this->pensiones->buscarPorId($id)->getEstado());
        $this->assertSame(Modalidad::Pensionada, $this->reservas->todas()[1]->getModalidad());
    }

    public function test_solo_se_revoca_una_pension_activa(): void
    {
        $id = $this->pensionExistente(self::PERSONA_EMPLEADO, self::DICIEMBRE, EstadoPension::Pendiente);

        $mensaje = $this->mensajeDeRechazo(fn () => $this->revocar->ejecutar($id));

        $this->assertStringContainsString('activa', $mensaje);
        $this->assertSame(EstadoPension::Pendiente, $this->pensiones->buscarPorId($id)->getEstado());
    }

    // ---------------------------------------------------------------- HU-11

    /** HU-11 criterio 1: por persona se ve el mes, el estado y los almuerzos reservados y recogidos. */
    public function test_muestra_por_persona_el_estado_y_los_almuerzos_reservados_y_recogidos(): void
    {
        $this->pensionExistente(self::PERSONA_EMPLEADO, self::DICIEMBRE, EstadoPension::Activa);
        $this->solicitar->ejecutar(self::EMPLEADO, false, [$this->idMaria]);
        $this->reservas->agregar($this->reserva(1, '2026-12-01', EstadoReserva::Entregada));
        $this->reservas->agregar($this->reserva(2, '2026-12-02', EstadoReserva::Confirmada));
        $this->reservas->agregar($this->reserva(3, '2026-12-03', EstadoReserva::Confirmada));
        $this->reservas->agregar($this->reserva(4, '2026-12-08', EstadoReserva::Cancelada));

        $filas = $this->misPensiones->ejecutar(self::EMPLEADO);

        $this->assertSame(2, count($filas));
        $juan = $filas[0];
        $this->assertSame('Juan Pérez', $juan->nombrePersona);
        $this->assertSame(EstadoPension::Activa, $juan->estado);
        $this->assertSame(3, $juan->reservados);
        $this->assertSame(1, $juan->recogidos);
        $maria = $filas[1];
        $this->assertSame('María López', $maria->nombrePersona);
        $this->assertSame(EstadoPension::Pendiente, $maria->estado);
        $this->assertSame(0, $maria->reservados);
    }

    /** HU-11 criterio 2: sin pensiones registradas, la lista llega vacía para mostrar el mensaje. */
    public function test_sin_pensiones_la_consulta_llega_vacia(): void
    {
        $this->assertSame([], $this->misPensiones->ejecutar(self::EMPLEADO));
    }

    /** Cada empleado ve solo las pensiones de las que es titular. */
    public function test_cada_empleado_ve_solo_sus_pensiones(): void
    {
        $this->solicitar->ejecutar(self::EMPLEADO, true, []);

        $this->assertSame([], $this->misPensiones->ejecutar(self::OTRO_EMPLEADO));
    }
}