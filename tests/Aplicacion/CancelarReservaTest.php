<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\CancelarReserva;
use RumboAlCielo\Aplicacion\CasosDeUso\EliminarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Dominio\Entidades\Reserva;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\FamiliarRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\ReservaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-18 Cancelar una reserva y HU-07 criterio 2 (reservas futuras de un familiar eliminado). */
final class CancelarReservaTest extends TestCase
{
    private const EMPLEADO = 2;          // Juan Pérez, persona 2
    private const OTRO_EMPLEADO = 3;     // Luis Mamani, persona 3
    private const LUNES = '2026-11-16';
    private const MARTES = '2026-11-17';

    private ReservaRepositorioEnMemoria $reservas;
    private FamiliarRepositorioEnMemoria $familiares;
    private int $idMaria;
    private int $personaMaria;
    private CancelarReserva $cancelar;

    protected function setUp(): void
    {
        // Lunes 16/11/2026, 08:30: faltan 30 minutos para el corte de las 09:00.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:30'));

        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $usuarios = new UsuarioRepositorioEnMemoria();
        $personas = new PersonaRepositorioEnMemoria();
        $this->familiares = new FamiliarRepositorioEnMemoria();
        $this->reservas = new ReservaRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($usuarios, $personas, $roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);
        $registrarUsuario->ejecutar('Luis', 'Mamani', 'luis@correo.com', '71000000', 2);

        $registrarFamiliar = new RegistrarFamiliar($this->familiares, $personas, $usuarios, $roles);
        $this->idMaria = $registrarFamiliar->ejecutar(self::EMPLEADO, 'María', 'López', 'conyuge');
        $this->personaMaria = $this->familiares->buscarPorId($this->idMaria)->getIdPersona();

        $this->cancelar = new CancelarReserva($this->reservas, $personas);
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
    ): Reserva {
        $reserva = new Reserva($id, $idPersona, $idTitular, 1, new DateTimeImmutable($fecha), Modalidad::Diaria, 18.0, $estado, 'qr' . $id);
        $this->reservas->agregar($reserva);
        return $reserva;
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

    // ---------------------------------------------------------------- HU-18

    /** HU-18 criterio 1: antes de las 09:00 el empleado cancela su propia reserva. */
    public function test_cancela_la_reserva_propia_antes_de_las_0900(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::LUNES);

        $nombre = $this->cancelar->ejecutar(1, self::EMPLEADO);

        $this->assertSame('Juan Pérez', $nombre);
        $this->assertSame(EstadoReserva::Cancelada, $this->reservas->buscarPorId(1)->getEstado());
    }

    /** HU-18 criterio 1: también puede cancelar la reserva de un familiar suyo. */
    public function test_cancela_la_reserva_de_un_familiar(): void
    {
        $this->reserva(1, $this->personaMaria, self::EMPLEADO, self::LUNES);

        $nombre = $this->cancelar->ejecutar(1, self::EMPLEADO);

        $this->assertSame('María López', $nombre);
        $this->assertSame(EstadoReserva::Cancelada, $this->reservas->buscarPorId(1)->getEstado());
    }

    /** HU-18 criterio 2: desde las 09:00 no se puede cancelar y se informa que se cobrará. */
    public function test_a_las_0900_no_permite_cancelar_y_el_almuerzo_se_cobra(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::LUNES);
        Reloj::fijar(new DateTimeImmutable('2026-11-16 09:00'));

        $mensaje = $this->mensajeDeRechazo(fn () => $this->cancelar->ejecutar(1, self::EMPLEADO));

        $this->assertSame('No se puede cancelar: pasaron las 09:00 AM y el almuerzo se cobrará.', $mensaje);
        $this->assertSame(EstadoReserva::Confirmada, $this->reservas->buscarPorId(1)->getEstado());
        $this->assertTrue($this->reservas->buscarPorId(1)->esCobrable());
    }

    /** HU-18 criterio 2: la regla incluye al administrador. */
    public function test_el_administrador_tampoco_puede_cancelar_despues_de_las_0900(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::LUNES);
        Reloj::fijar(new DateTimeImmutable('2026-11-16 09:15'));

        $mensaje = $this->mensajeDeRechazo(fn () => $this->cancelar->ejecutar(1, null));

        $this->assertStringContainsString('el almuerzo se cobrará', $mensaje);
    }

    /** HU-18 criterio 1: una reserva de otro día se puede cancelar mientras no llegue su corte. */
    public function test_cancela_la_reserva_de_un_dia_futuro_aunque_hoy_ya_paso_el_corte(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::MARTES);
        Reloj::fijar(new DateTimeImmutable('2026-11-16 15:00'));

        $this->cancelar->ejecutar(1, self::EMPLEADO);

        $this->assertSame(EstadoReserva::Cancelada, $this->reservas->buscarPorId(1)->getEstado());
    }

    /** HU-18: un empleado no puede cancelar la reserva de otro empleado. */
    public function test_no_cancela_la_reserva_de_otro_empleado(): void
    {
        $this->reserva(1, 3, self::OTRO_EMPLEADO, self::LUNES);

        $mensaje = $this->mensajeDeRechazo(fn () => $this->cancelar->ejecutar(1, self::EMPLEADO));

        $this->assertSame('No se encontró la reserva.', $mensaje);
        $this->assertSame(EstadoReserva::Confirmada, $this->reservas->buscarPorId(1)->getEstado());
    }

    /** HU-18: una reserva ya cancelada o ya entregada no se vuelve a cancelar. */
    public function test_no_cancela_una_reserva_cancelada_ni_entregada(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::LUNES, EstadoReserva::Cancelada);
        $this->reserva(2, 2, self::EMPLEADO, self::MARTES, EstadoReserva::Entregada);

        $yaCancelada = $this->mensajeDeRechazo(fn () => $this->cancelar->ejecutar(1, self::EMPLEADO));
        $entregada = $this->mensajeDeRechazo(fn () => $this->cancelar->ejecutar(2, self::EMPLEADO));

        $this->assertSame('Solo se puede cancelar una reserva confirmada.', $yaCancelada);
        $this->assertSame('Solo se puede cancelar una reserva confirmada.', $entregada);
    }

    // ---------------------------------------------------------------- HU-07 criterio 2

    /** HU-07 criterio 2: al eliminar al familiar se anulan sus reservas futuras sin cobro. */
    public function test_al_eliminar_un_familiar_se_anulan_sus_reservas_futuras(): void
    {
        $this->reserva(1, $this->personaMaria, self::EMPLEADO, self::LUNES);   // hoy, antes del corte
        $this->reserva(2, $this->personaMaria, self::EMPLEADO, self::MARTES);  // mañana
        $this->reserva(3, 2, self::EMPLEADO, self::MARTES);                    // del empleado: no se toca

        (new EliminarFamiliar($this->familiares, $this->reservas))->ejecutar($this->idMaria, true);

        $this->assertSame(EstadoReserva::Cancelada, $this->reservas->buscarPorId(1)->getEstado());
        $this->assertSame(EstadoReserva::Cancelada, $this->reservas->buscarPorId(2)->getEstado());
        $this->assertFalse($this->reservas->buscarPorId(2)->esCobrable());
        $this->assertSame(EstadoReserva::Confirmada, $this->reservas->buscarPorId(3)->getEstado());
    }

    /** HU-07 criterio 2: el consumo anterior (entregado) se conserva para los reportes. */
    public function test_al_eliminar_un_familiar_conserva_su_consumo_anterior(): void
    {
        $this->reserva(1, $this->personaMaria, self::EMPLEADO, '2026-11-12', EstadoReserva::Entregada);
        $this->reserva(2, $this->personaMaria, self::EMPLEADO, '2026-11-13', EstadoReserva::Confirmada); // no asistió

        (new EliminarFamiliar($this->familiares, $this->reservas))->ejecutar($this->idMaria, true);

        $this->assertSame(EstadoReserva::Entregada, $this->reservas->buscarPorId(1)->getEstado());
        $this->assertSame(EstadoReserva::Confirmada, $this->reservas->buscarPorId(2)->getEstado());
    }

    /** HU-07 criterio 3: si el administrador cancela la confirmación, sus reservas se mantienen. */
    public function test_si_no_se_confirma_la_eliminacion_las_reservas_se_mantienen(): void
    {
        $this->reserva(1, $this->personaMaria, self::EMPLEADO, self::MARTES);

        (new EliminarFamiliar($this->familiares, $this->reservas))->ejecutar($this->idMaria, false);

        $this->assertSame(EstadoReserva::Confirmada, $this->reservas->buscarPorId(1)->getEstado());
    }
}