<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMisPensiones;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFamiliares;
use RumboAlCielo\Aplicacion\CasosDeUso\ResultadoSolicitudPension;
use RumboAlCielo\Aplicacion\CasosDeUso\SolicitarPension;
use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-08 y HU-11: pantallas del empleado para solicitar la pensión mensual y consultar sus
 * pensiones. Reciben los formularios y llaman a los casos de uso. Sin reglas de negocio.
 */
final class PensionEmpleadoControlador extends ControladorBase
{
    private const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function __construct(
        private Guardia $guardia,
        private Sesion $sesion,
        private ConsultarUsuario $consultarUsuario,
        private ListarFamiliares $listarFamiliares,
        private ConsultarMisPensiones $consultarPensiones,
        private SolicitarPension $solicitarPension,
    ) {
    }

    /** HU-11: estado de las pensiones propias y de los familiares. */
    public function listar(): void
    {
        $this->guardia->exigirEmpleado();
        $this->render('pensiones_empleado', [
            'filas' => $this->consultarPensiones->ejecutar($this->idEmpleado()),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    /** HU-08 criterio 1: formulario con las personas que pueden tener pensión. */
    public function mostrarSolicitud(): void
    {
        $this->guardia->exigirEmpleado();
        $this->mostrarFormulario(true, [], '');
    }

    public function solicitar(): void
    {
        $this->guardia->exigirEmpleado();
        $this->exigirTokenValido();

        $paraMi = isset($_POST['paraMi']);
        $ids = isset($_POST['familiares']) && is_array($_POST['familiares'])
            ? array_values(array_unique(array_map('intval', $_POST['familiares'])))
            : [];

        try {
            $resultado = $this->solicitarPension->ejecutar($this->idEmpleado(), $paraMi, $ids);
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario($paraMi, $ids, $error->getMessage());
            return;
        }

        $this->avisar($resultado->registradas === [] ? 'error' : 'ok', $this->mensajeDe($resultado));
        $this->redirigir('/empleado/pensiones');
    }

    private function idEmpleado(): int
    {
        return (int) $this->sesion->idUsuario();
    }

    /** @param int[] $marcados Familiares elegidos. */
    private function mostrarFormulario(bool $paraMi, array $marcados, string $error): void
    {
        $empleado = $this->consultarUsuario->ejecutar($this->idEmpleado());
        $this->render('pension_solicitud', [
            'mesTexto' => $this->mesTexto(Reloj::ahora()->modify('first day of next month')),
            'nombreEmpleado' => $empleado->nombres . ' ' . $empleado->apellidos,
            'familiaresFilas' => $this->listarFamiliares->ejecutar($this->idEmpleado()),
            'paraMi' => $paraMi,
            'marcados' => $marcados,
            'error' => $error,
        ]);
    }

    private function mensajeDe(ResultadoSolicitudPension $resultado): string
    {
        $mes = $this->mesTexto($resultado->mes);
        $existentes = implode(', ', array_map(fn (array $p) => $p[0] . ' (' . strtolower($p[1]) . ')', $resultado->yaExistentes));

        if ($resultado->registradas === []) {
            return 'No se duplicó la solicitud. Ya tienen pensión o solicitud para ' . $mes . ': ' . $existentes . '.';
        }

        $mensaje = 'Solicitud de pensión enviada para ' . $mes . ': ' . implode(', ', $resultado->registradas)
            . '. Queda pendiente de confirmación del administrador.';
        if ($existentes !== '') {
            $mensaje .= ' Ya tenían pensión o solicitud: ' . $existentes . '.';
        }
        return $mensaje;
    }

    private function mesTexto(DateTimeImmutable $mes): string
    {
        return self::MESES[(int) $mes->format('n')] . ' de ' . $mes->format('Y');
    }
}