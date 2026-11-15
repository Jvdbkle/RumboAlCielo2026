<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarReservasAdmin;
use RumboAlCielo\Aplicacion\CasosDeUso\FilaUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarUsuarios;
use RumboAlCielo\Aplicacion\CasosDeUso\ValidarEntrega;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/**
 * HU-28 y HU-22: pantallas del administrador para consultar reservas y validar el código QR
 * en el mostrador. Reciben los datos y llaman a los casos de uso. Sin reglas de negocio.
 */
final class ReservaAdminControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private ConsultarReservasAdmin $consultarReservas,
        private ListarUsuarios $listarUsuarios,
        private ValidarEntrega $validarEntrega,
    ) {
    }

    /** HU-28: reservas de una fecha o un rango, con filtro por empleado. */
    public function consultar(): void
    {
        $this->guardia->exigirAdministrador();

        $desde = $this->fecha($_GET['desde'] ?? null) ?? Reloj::ahora()->setTime(0, 0);
        $hasta = $this->fecha($_GET['hasta'] ?? null);
        $idEmpleado = (int) ($_GET['empleado'] ?? 0) ?: null;

        $filas = [];
        $error = '';
        try {
            $filas = $this->consultarReservas->ejecutar($desde, $hasta, $idEmpleado);
        } catch (ReglaDeNegocioException $excepcion) {
            $error = $excepcion->getMessage();
        }

        $this->render('reservas_admin', [
            'filas' => $filas,
            'desde' => $desde,
            'hasta' => $hasta,
            'idEmpleado' => $idEmpleado,
            'empleados' => array_values(array_filter(
                $this->listarUsuarios->ejecutar(),
                fn (FilaUsuario $f) => $f->rol !== 'Administrador'
            )),
            'error' => $error,
            'sinResultados' => $error === '' && $filas === [] ? ConsultarReservasAdmin::SIN_RESULTADOS : '',
        ]);
    }

    /** HU-22: pantalla del mostrador. */
    public function mostrador(): void
    {
        $this->guardia->exigirAdministrador();
        $this->render('mostrador');
    }

    /** HU-22: valida un código y responde en JSON para mostrar la alerta visual y sonora. */
    public function validar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();

        header('Content-Type: application/json; charset=utf-8');
        try {
            $resultado = $this->validarEntrega->ejecutar((string) ($_POST['codigo'] ?? ''));
            echo json_encode([
                'ok' => true,
                'nombre' => $resultado->nombrePersona,
                'hora' => $resultado->horaEntrega->format('H:i'),
                'mensaje' => 'Entrega registrada',
            ], JSON_UNESCAPED_UNICODE);
        } catch (ReglaDeNegocioException $error) {
            echo json_encode(['ok' => false, 'mensaje' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function fecha(mixed $texto): ?DateTimeImmutable
    {
        $texto = trim((string) $texto);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        return $fecha !== false && $fecha->format('Y-m-d') === $texto ? $fecha : null;
    }
}