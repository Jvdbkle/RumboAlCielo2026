<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\DatosFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\DatosUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\EliminarFamiliar;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarFamiliares;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFamiliar;
use RumboAlCielo\Dominio\Enumeraciones\Parentesco;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-04 y HU-07: pantallas del administrador para gestionar los familiares de un empleado.
 * Reciben los formularios y llaman a los casos de uso. Sin reglas de negocio.
 */
final class FamiliarControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private ConsultarUsuario $consultarUsuario,
        private ListarFamiliares $listarFamiliares,
        private RegistrarFamiliar $registrarFamiliar,
        private ConsultarFamiliar $consultarFamiliar,
        private EditarFamiliar $editarFamiliar,
        private EliminarFamiliar $eliminarFamiliar,
    ) {
    }

    /** HU-04 criterio 2: familiares agregados a un empleado. */
    public function listar(): void
    {
        $this->guardia->exigirAdministrador();
        $empleado = $this->empleadoOSalir((int) ($_GET['empleado'] ?? 0));

        $this->render('familiares_lista', [
            'empleado' => $empleado,
            'filas' => $this->listarFamiliares->ejecutar($empleado->idUsuario),
            'aviso' => $this->tomarAviso(),
        ]);
    }

    /** HU-04 criterio 1: formulario de registro. */
    public function mostrarNuevo(): void
    {
        $this->guardia->exigirAdministrador();
        $empleado = $this->empleadoOSalir((int) ($_GET['empleado'] ?? 0));
        $this->mostrarFormulario('Registrar familiar', '/administrador/familiares/nuevo', $empleado->idUsuario, null, [], '');
    }

    public function registrar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $idEmpleado = (int) ($_POST['idEmpleado'] ?? 0);
        $valores = $this->valoresDelFormulario();

        try {
            $this->registrarFamiliar->ejecutar($idEmpleado, $valores['nombres'], $valores['apellidos'], $valores['parentesco']);
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario('Registrar familiar', '/administrador/familiares/nuevo', $idEmpleado, null, $valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'Familiar registrado.');
        $this->redirigir('/administrador/familiares?empleado=' . $idEmpleado);
    }

    /** HU-04 criterio 3: formulario de edición con los datos actuales. */
    public function mostrarEdicion(): void
    {
        $this->guardia->exigirAdministrador();
        $datos = $this->familiarOSalir((int) ($_GET['id'] ?? 0));

        $this->mostrarFormulario('Editar familiar', '/administrador/familiares/editar', $datos->idEmpleado, $datos->idFamiliar, [
            'nombres' => $datos->nombres,
            'apellidos' => $datos->apellidos,
            'parentesco' => $datos->parentesco,
        ], '');
    }

    public function editar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $datos = $this->familiarOSalir((int) ($_POST['idFamiliar'] ?? 0));
        $valores = $this->valoresDelFormulario();

        try {
            $this->editarFamiliar->ejecutar($datos->idFamiliar, $valores['nombres'], $valores['apellidos'], $valores['parentesco']);
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario('Editar familiar', '/administrador/familiares/editar', $datos->idEmpleado, $datos->idFamiliar, $valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'Los datos del familiar fueron actualizados.');
        $this->redirigir('/administrador/familiares?empleado=' . $datos->idEmpleado);
    }

    /** HU-07 criterio 1: pide confirmar antes de eliminar. */
    public function mostrarConfirmacion(): void
    {
        $this->guardia->exigirAdministrador();
        $datos = $this->familiarOSalir((int) ($_GET['id'] ?? 0));
        $this->render('familiar_eliminar', ['familiar' => $datos]);
    }

    /** HU-07 criterios 1 y 3: solo se elimina si el administrador confirma. */
    public function eliminar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $datos = $this->familiarOSalir((int) ($_POST['idFamiliar'] ?? 0));

        try {
            $eliminado = $this->eliminarFamiliar->ejecutar($datos->idFamiliar, ($_POST['confirmado'] ?? '') === '1');
            $this->avisar('ok', $eliminado ? 'El familiar fue eliminado.' : 'No se eliminó al familiar.');
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/familiares?empleado=' . $datos->idEmpleado);
    }

    private function empleadoOSalir(int $idEmpleado): DatosUsuario
    {
        try {
            return $this->consultarUsuario->ejecutar($idEmpleado);
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
            $this->redirigir('/administrador/usuarios');
        }
    }

    private function familiarOSalir(int $idFamiliar): DatosFamiliar
    {
        try {
            return $this->consultarFamiliar->ejecutar($idFamiliar);
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
            $this->redirigir('/administrador/usuarios');
        }
    }

    /** @return array{nombres: string, apellidos: string, parentesco: string} */
    private function valoresDelFormulario(): array
    {
        return [
            'nombres' => (string) ($_POST['nombres'] ?? ''),
            'apellidos' => (string) ($_POST['apellidos'] ?? ''),
            'parentesco' => (string) ($_POST['parentesco'] ?? ''),
        ];
    }

    /** @param array<string, string> $valores */
    private function mostrarFormulario(string $titulo, string $accion, int $idEmpleado, ?int $idFamiliar, array $valores, string $error): void
    {
        $this->render('familiar_formulario', [
            'titulo' => $titulo,
            'accion' => $accion,
            'idEmpleado' => $idEmpleado,
            'idFamiliar' => $idFamiliar,
            'valores' => $valores + ['nombres' => '', 'apellidos' => '', 'parentesco' => ''],
            'parentescos' => Parentesco::cases(),
            'error' => $error,
        ]);
    }
}