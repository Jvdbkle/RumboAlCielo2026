<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMisCodigosQR;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\ValidarEntrega;
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

/** HU-21 Ver el código QR de mi reserva y HU-22 Validar la entrega con código QR. */
final class CodigoQRTest extends TestCase
{
    private const EMPLEADO = 2;          // Juan Pérez, persona 2
    private const OTRO_EMPLEADO = 3;     // Luis Mamani, persona 3
    private const HOY = '2026-11-16';
    private const AYER = '2026-11-13';
    private const MANIANA = '2026-11-17';

    private ReservaRepositorioEnMemoria $reservas;
    private int $personaMaria;
    private ConsultarMisCodigosQR $misCodigos;
    private ValidarEntrega $validarEntrega;

    protected function setUp(): void
    {
        // Lunes 16/11/2026, 12:00: hora de retirar el almuerzo.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 12:00'));

        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $usuarios = new UsuarioRepositorioEnMemoria();
        $personas = new PersonaRepositorioEnMemoria();
        $familiares = new FamiliarRepositorioEnMemoria();
        $this->reservas = new ReservaRepositorioEnMemoria();

        $registrarUsuario = new RegistrarUsuario($usuarios, $personas, $roles);
        $registrarUsuario->ejecutar('Ana', 'Rojas', 'ana@correo.com', '60000000', 1);
        $registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);
        $registrarUsuario->ejecutar('Luis', 'Mamani', 'luis@correo.com', '71000000', 2);

        $idMaria = (new RegistrarFamiliar($familiares, $personas, $usuarios, $roles))
            ->ejecutar(self::EMPLEADO, 'María', 'López', 'conyuge');
        $this->personaMaria = $familiares->buscarPorId($idMaria)->getIdPersona();

        $this->misCodigos = new ConsultarMisCodigosQR($this->reservas, $personas);
        $this->validarEntrega = new ValidarEntrega($this->reservas, $personas);
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

    private function mensajeDeRechazo(callable $accion): string
    {
        try {
            $accion();
        } catch (ReglaDeNegocioException $error) {
            return $error->getMessage();
        }
        $this->fail('Se esperaba que el sistema rechazara la operación.');
    }

    // ---------------------------------------------------------------- HU-21

    /** HU-21 criterio 1: con una reserva confirmada para hoy se muestra su código QR único. */
    public function test_muestra_el_codigo_de_la_reserva_confirmada_de_hoy(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::HOY);

        $filas = $this->misCodigos->ejecutar(self::EMPLEADO);

        $this->assertSame(1, count($filas));
        $this->assertSame('Juan Pérez', $filas[0]->nombrePersona);
        $this->assertSame('codigo-1', $filas[0]->codigo);
    }

    /** HU-21 criterio 2: un código independiente por cada persona, para retirar en momentos distintos. */
    public function test_muestra_un_codigo_independiente_por_cada_familiar(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::HOY);
        $this->reserva(2, $this->personaMaria, self::EMPLEADO, self::HOY);

        $filas = $this->misCodigos->ejecutar(self::EMPLEADO);

        $this->assertSame(['Juan Pérez', 'María López'], array_map(fn ($f) => $f->nombrePersona, $filas));
        $this->assertSame(['codigo-1', 'codigo-2'], array_map(fn ($f) => $f->codigo, $filas));
    }

    /** HU-21 criterio 3: sin reserva confirmada para hoy no hay códigos y se informa. */
    public function test_sin_reserva_para_hoy_no_muestra_codigos(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::AYER);
        $this->reserva(2, 2, self::EMPLEADO, self::MANIANA);
        $this->reserva(3, $this->personaMaria, self::EMPLEADO, self::HOY, EstadoReserva::Cancelada);
        $this->reserva(4, 3, self::OTRO_EMPLEADO, self::HOY);   // de otro empleado

        $filas = $this->misCodigos->ejecutar(self::EMPLEADO);

        $this->assertSame([], $filas);
        $this->assertSame('No cuentas con una reserva para hoy.', ConsultarMisCodigosQR::SIN_RESERVA_HOY);
    }

    // ---------------------------------------------------------------- HU-22

    /** HU-22 criterio 1: un código válido para hoy confirma, muestra el nombre y marca la reserva entregada. */
    public function test_un_codigo_valido_registra_la_entrega_y_muestra_el_nombre(): void
    {
        $this->reserva(1, $this->personaMaria, self::EMPLEADO, self::HOY);

        $resultado = $this->validarEntrega->ejecutar('codigo-1');

        $this->assertSame('María López', $resultado->nombrePersona);
        $this->assertSame('12:00', $resultado->horaEntrega->format('H:i'));
        $this->assertSame(EstadoReserva::Entregada, $this->reservas->buscarPorId(1)->getEstado());
    }

    /** HU-22 criterio 2: un código ya canjeado se rechaza y muestra la hora de la entrega anterior. */
    public function test_un_codigo_ya_canjeado_se_rechaza_con_la_hora_de_la_entrega(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::HOY);
        $this->validarEntrega->ejecutar('codigo-1');
        Reloj::fijar(new DateTimeImmutable('2026-11-16 12:30'));

        $mensaje = $this->mensajeDeRechazo(fn () => $this->validarEntrega->ejecutar('codigo-1'));

        $this->assertSame('El código ya fue canjeado a las 12:00.', $mensaje);
        $this->assertSame('12:00', $this->reservas->buscarPorId(1)->getHoraEntrega()->format('H:i'));
    }

    /** HU-22 criterio 3: un código de otro día no se acepta y no registra la entrega. */
    public function test_un_codigo_de_otra_fecha_se_rechaza(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::MANIANA);

        $mensaje = $this->mensajeDeRechazo(fn () => $this->validarEntrega->ejecutar('codigo-1'));

        $this->assertSame('El código QR no corresponde a la fecha de hoy.', $mensaje);
        $this->assertSame(EstadoReserva::Confirmada, $this->reservas->buscarPorId(1)->getEstado());
    }

    /** HU-22 criterio 3: un código inexistente o vacío no es válido. */
    public function test_un_codigo_inexistente_o_vacio_se_rechaza(): void
    {
        $inexistente = $this->mensajeDeRechazo(fn () => $this->validarEntrega->ejecutar('no-existe'));
        $vacio = $this->mensajeDeRechazo(fn () => $this->validarEntrega->ejecutar('   '));

        $this->assertSame('El código QR no es válido.', $inexistente);
        $this->assertSame('El código QR no es válido.', $vacio);
    }

    /** HU-22 criterio 3: la reserva cancelada no da derecho a retirar el almuerzo. */
    public function test_una_reserva_cancelada_no_se_entrega(): void
    {
        $this->reserva(1, 2, self::EMPLEADO, self::HOY, EstadoReserva::Cancelada);

        $mensaje = $this->mensajeDeRechazo(fn () => $this->validarEntrega->ejecutar('codigo-1'));

        $this->assertSame('La reserva está cancelada.', $mensaje);
        $this->assertSame(EstadoReserva::Cancelada, $this->reservas->buscarPorId(1)->getEstado());
    }
}