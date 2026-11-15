<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\CasosDeUso\CancelarReserva;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarCalendarioReservas;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMisCodigosQR;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\DiaDelCalendario;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFamiliares;
use RumboAlCielo\Aplicacion\CasosDeUso\ResultadoReserva;
use RumboAlCielo\Aplicacion\CasosDeUso\ReservarAlmuerzo;
use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-16 a HU-21: pantallas del empleado para reservar, cancelar, ver el calendario y mostrar
 * sus códigos QR. Reciben los formularios y llaman a los casos de uso. Sin reglas de negocio.
 */
final class ReservaEmpleadoControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private Sesion $sesion,
        private ConsultarUsuario $consultarUsuario,
        private ListarFamiliares $listarFamiliares,
        private ConsultarCalendarioReservas $consultarCalendario,
        private ReservarAlmuerzo $reservarAlmuerzo,
        private CancelarReserva $cancelarReserva,
        private ConsultarMisCodigosQR $consultarCodigos,
    ) {
    }

    /** HU-19: calendario del mes con el estado de cada día. */
    public function calendario(): void
    {
        $this->guardia->exigirEmpleado();
        $mes = $this->mesDe($_GET['mes'] ?? null);
        $this->render('reservas_calendario', [
            'dias' => $this->consultarCalendario->ejecutar($this->idEmpleado(), $mes),
            'mes' => $mes,
            'anterior' => $mes->modify('-1 month'),
            'siguiente' => $mes->modify('+1 month'),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    /** HU-16 y HU-17: formulario para elegir quiénes almuerzan ese día. */
    public function mostrarReserva(): void
    {
        $this->guardia->exigirEmpleado();
        $fecha = $this->fechaDe($_GET['fecha'] ?? null);
        if ($fecha === null) {
            $this->avisar('error', 'La fecha indicada no es válida.');
            $this->redirigir('/empleado/reservas');
        }
        $this->mostrarFormulario($fecha, true, [], '');
    }

    public function reservar(): void
    {
        $this->guardia->exigirEmpleado();
        $this->exigirTokenValido();

        $fecha = $this->fechaDe($_POST['fecha'] ?? null);
        if ($fecha === null) {
            $this->avisar('error', 'La fecha indicada no es válida.');
            $this->redirigir('/empleado/reservas');
        }
        $paraMi = isset($_POST['paraMi']);
        $ids = isset($_POST['familiares']) && is_array($_POST['familiares'])
            ? array_values(array_unique(array_map('intval', $_POST['familiares'])))
            : [];

        try {
            $resultado = $this->reservarAlmuerzo->ejecutar($this->idEmpleado(), $paraMi, $ids, $fecha);
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario($fecha, $paraMi, $ids, $error->getMessage());
            return;
        }

        $this->avisar($resultado->registradas === [] ? 'error' : 'ok', $this->mensajeDe($resultado));
        $this->redirigir('/empleado/reservas?mes=' . $fecha->format('Y-m'));
    }

    /** HU-18: confirmación antes de cancelar una reserva propia o de un familiar. */
    public function mostrarCancelacion(): void
    {
        $this->guardia->exigirEmpleado();
        $mes = $this->mesDe($_GET['mes'] ?? null);
        $idReserva = (int) ($_GET['id'] ?? 0);

        foreach ($this->consultarCalendario->ejecutar($this->idEmpleado(), $mes) as $dia) {
            foreach ($dia->reservas as $reserva) {
                if ($reserva->idReserva === $idReserva) {
                    $this->render('reserva_cancelar', [
                        'reserva' => $reserva,
                        'fechaTexto' => $this->fechaTexto($dia->fecha),
                        'mes' => $mes,
                    ]);
                    return;
                }
            }
        }
        $this->avisar('error', 'No se encontró la reserva.');
        $this->redirigir('/empleado/reservas?mes=' . $mes->format('Y-m'));
    }

    public function cancelar(): void
    {
        $this->guardia->exigirEmpleado();
        $this->exigirTokenValido();
        $mes = $this->mesDe($_POST['mes'] ?? null);

        try {
            $nombre = $this->cancelarReserva->ejecutar((int) ($_POST['idReserva'] ?? 0), $this->idEmpleado());
            $this->avisar('ok', 'Se canceló la reserva de ' . $nombre . '. No se le cobrará ese almuerzo.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/empleado/reservas?mes=' . $mes->format('Y-m'));
    }

    /** HU-21: códigos QR de hoy, uno por persona. */
    public function codigosQr(): void
    {
        $this->guardia->exigirEmpleado();
        $this->render('codigos_qr', [
            'filas' => $this->consultarCodigos->ejecutar($this->idEmpleado()),
            'hoyTexto' => $this->fechaTexto(Reloj::ahora()),
        ]);
    }

    private function idEmpleado(): int
    {
        return (int) $this->sesion->idUsuario();
    }

    /** @param int[] $marcados Familiares elegidos. */
    private function mostrarFormulario(DateTimeImmutable $fecha, bool $paraMi, array $marcados, string $error): void
    {
        $empleado = $this->consultarUsuario->ejecutar($this->idEmpleado());
        $dia = $this->diaDelCalendario($fecha);
        $this->render('reserva_nueva', [
            'fecha' => $fecha,
            'fechaTexto' => $this->fechaTexto($fecha),
            'abierto' => $dia !== null && $dia->abierto,
            'nombreEmpleado' => $empleado->nombres . ' ' . $empleado->apellidos,
            'familiaresFilas' => $this->listarFamiliares->ejecutar($this->idEmpleado()),
            'paraMi' => $paraMi,
            'marcados' => $marcados,
            'error' => $error,
        ]);
    }

    private function diaDelCalendario(DateTimeImmutable $fecha): ?DiaDelCalendario
    {
        foreach ($this->consultarCalendario->ejecutar($this->idEmpleado(), $fecha) as $dia) {
            if ($dia->fecha->format('Y-m-d') === $fecha->format('Y-m-d')) {
                return $dia;
            }
        }
        return null;
    }

    /** HU-20 criterio 1: confirmación con la fecha, la persona y el importe. */
    private function mensajeDe(ResultadoReserva $resultado): string
    {
        $omitidas = implode(' ', array_map(fn (array $o) => $o[0] . ': ' . $o[1], $resultado->omitidas));
        if ($resultado->registradas === []) {
            return 'No se registró ninguna reserva. ' . $omitidas;
        }

        $personas = implode(', ', array_map(
            fn (array $r) => $r[0] . ' (' . number_format($r[1], 0) . ' Bs)',
            $resultado->registradas
        ));
        $mensaje = 'Reserva confirmada para el ' . $this->fechaTexto($resultado->fecha) . ': ' . $personas
            . '. Total: ' . number_format($resultado->total(), 0) . ' Bs.';
        return $omitidas === '' ? $mensaje : $mensaje . ' No se reservó para: ' . $omitidas;
    }

    private function fechaTexto(DateTimeImmutable $fecha): string
    {
        $nombre = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'][(int) $fecha->format('N')];
        return $nombre . ' ' . $fecha->format('d/m/Y');
    }

    private function mesDe(mixed $valor): DateTimeImmutable
    {
        $mes = is_string($valor) && preg_match('/^\d{4}-\d{2}$/', $valor) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m', $valor)
            : false;
        return $mes === false ? Reloj::ahora()->modify('first day of this month')->setTime(0, 0) : $mes;
    }

    private function fechaDe(mixed $valor): ?DateTimeImmutable
    {
        if (!is_string($valor)) {
            return null;
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $fecha !== false && $fecha->format('Y-m-d') === $valor ? $fecha : null;
    }
}