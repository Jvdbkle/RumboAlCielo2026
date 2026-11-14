<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\ConfirmarPension;
use RumboAlCielo\Aplicacion\CasosDeUso\FilaPension;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarPensiones;
use RumboAlCielo\Aplicacion\CasosDeUso\RechazarPension;
use RumboAlCielo\Aplicacion\CasosDeUso\RevocarPension;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-09 y HU-10: pantallas del administrador para confirmar, rechazar y revocar pensiones.
 * Reciben los formularios y llaman a los casos de uso. Sin reglas de negocio.
 */
final class PensionAdminControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private ListarPensiones $listarPensiones,
        private ConfirmarPension $confirmarPension,
        private RechazarPension $rechazarPension,
        private RevocarPension $revocarPension,
    ) {
    }

    public function listar(): void
    {
        $this->guardia->exigirAdministrador();
        $filas = $this->listarPensiones->ejecutar();

        $this->render('pensiones_admin', [
            'pendientes' => array_values(array_filter($filas, fn (FilaPension $f) => $f->estado === EstadoPension::Pendiente)),
            'activas' => array_values(array_filter($filas, fn (FilaPension $f) => $f->estado === EstadoPension::Activa)),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    /** HU-09 criterios 1 y 3. */
    public function confirmar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();

        try {
            $this->confirmarPension->ejecutar((int) ($_POST['idPension'] ?? 0));
            $this->avisar('ok', 'La solicitud fue confirmada. La pensión quedó activa con la tarifa de 15 Bs por almuerzo.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/pensiones');
    }

    /** HU-09 criterio 2. */
    public function rechazar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();

        try {
            $this->rechazarPension->ejecutar((int) ($_POST['idPension'] ?? 0));
            $this->avisar('ok', 'La solicitud fue rechazada. El empleado podrá verlo en sus pensiones.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/pensiones');
    }

    /** HU-10 criterio 1: pide confirmar antes de revocar. */
    public function mostrarConfirmacion(): void
    {
        $this->guardia->exigirAdministrador();
        $fila = $this->filaOSalir((int) ($_GET['id'] ?? 0));
        $this->render('pension_revocar', ['fila' => $fila]);
    }

    /** HU-10 criterios 1 y 2: solo se revoca si el administrador confirma. */
    public function revocar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();

        if (($_POST['confirmado'] ?? '') !== '1') {
            $this->avisar('error', 'No se revocó la pensión.');
            $this->redirigir('/administrador/pensiones');
        }

        try {
            $this->revocarPension->ejecutar((int) ($_POST['idPension'] ?? 0));
            $this->avisar('ok', 'La pensión fue revocada. Las reservas del mes quedan como reservas normales a 18 Bs.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/pensiones');
    }

    private function filaOSalir(int $idPension): FilaPension
    {
        foreach ($this->listarPensiones->ejecutar() as $fila) {
            if ($fila->idPension === $idPension) {
                return $fila;
            }
        }
        $this->avisar('error', 'La pensión no existe.');
        $this->redirigir('/administrador/pensiones');
    }
}