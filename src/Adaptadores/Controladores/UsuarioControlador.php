<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\DeshabilitarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\ListarUsuarios;
use RumboAlCielo\Aplicacion\CasosDeUso\ReestablecerContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\ReincorporarUsuario;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-02, HU-03 y HU-06: pantallas de gestión de usuarios del administrador.
 * Reciben los formularios y llaman a los casos de uso. Sin reglas de negocio.
 */
final class UsuarioControlador extends ControladorBase
{
    public function __construct(
        private Guardia $guardia,
        private Sesion $sesion,
        private RolRepositorio $roles,
        private ListarUsuarios $listarUsuarios,
        private RegistrarUsuario $registrarUsuario,
        private ConsultarUsuario $consultarUsuario,
        private EditarUsuario $editarUsuario,
        private DeshabilitarUsuario $deshabilitarUsuario,
        private ReincorporarUsuario $reincorporarUsuario,
        private ReestablecerContrasenia $reestablecerContrasenia,
    ) {
    }

    /** HU-03 criterio 3: lista de usuarios con su estado. */
    public function listar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->render('usuarios_lista', [
            'filas' => $this->listarUsuarios->ejecutar(),
            'aviso' => $this->tomarAviso(),
            'idActual' => $this->sesion->idUsuario(),
        ]);
    }

    /** HU-03 criterio 1: formulario de registro. */
    public function mostrarNuevo(): void
    {
        $this->guardia->exigirAdministrador();
        $this->mostrarFormulario('Registrar usuario', '/administrador/usuarios/nuevo', null, [], '');
    }

    public function registrar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $valores = $this->valoresDelFormulario();

        try {
            $this->registrarUsuario->ejecutar(
                $valores['nombres'],
                $valores['apellidos'],
                $valores['correo'],
                $valores['telefono'],
                (int) $valores['idRol'],
            );
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario('Registrar usuario', '/administrador/usuarios/nuevo', null, $valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'Usuario registrado. Su primera contraseña es su teléfono.');
        $this->redirigir('/administrador/usuarios');
    }

    /** HU-03 criterio 4: formulario de edición con los datos actuales. */
    public function mostrarEdicion(): void
    {
        $this->guardia->exigirAdministrador();
        $idUsuario = (int) ($_GET['id'] ?? 0);

        try {
            $datos = $this->consultarUsuario->ejecutar($idUsuario);
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
            $this->redirigir('/administrador/usuarios');
        }

        $this->mostrarFormulario('Editar usuario', '/administrador/usuarios/editar', $idUsuario, [
            'nombres' => $datos->nombres,
            'apellidos' => $datos->apellidos,
            'correo' => $datos->correo,
            'telefono' => $datos->telefono,
            'idRol' => (string) $datos->idRol,
        ], '');
    }

    public function editar(): void
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();
        $idUsuario = (int) ($_POST['idUsuario'] ?? 0);
        $valores = $this->valoresDelFormulario();

        try {
            $this->editarUsuario->ejecutar(
                $idUsuario,
                $valores['nombres'],
                $valores['apellidos'],
                $valores['correo'],
                $valores['telefono'],
            );
        } catch (ReglaDeNegocioException $error) {
            $this->mostrarFormulario('Editar usuario', '/administrador/usuarios/editar', $idUsuario, $valores, $error->getMessage());
            return;
        }

        $this->avisar('ok', 'Los datos del usuario fueron actualizados.');
        $this->redirigir('/administrador/usuarios');
    }

    /** HU-03 criterio 2. */
    public function deshabilitar(): void
    {
        $this->ejecutarAccion(fn (int $id) => $this->deshabilitarUsuario->ejecutar($id), 'El usuario fue deshabilitado.');
    }

    /** HU-06 criterios 1 y 3. */
    public function reincorporar(): void
    {
        $this->ejecutarAccion(fn (int $id) => $this->reincorporarUsuario->ejecutar($id), 'El usuario fue reincorporado.');
    }

    /** HU-02 criterio 1: el teléfono vuelve a ser la contraseña. */
    public function restablecer(): void
    {
        $this->ejecutarAccion(
            fn (int $id) => $this->reestablecerContrasenia->ejecutar((int) $this->sesion->idUsuario(), $id),
            'La contraseña volvió a ser el teléfono del usuario. Deberá cambiarla al ingresar.'
        );
    }

    /** @param callable(int): void $accion */
    private function ejecutarAccion(callable $accion, string $mensajeOk): never
    {
        $this->guardia->exigirAdministrador();
        $this->exigirTokenValido();

        try {
            $accion((int) ($_POST['idUsuario'] ?? 0));
            $this->avisar('ok', $mensajeOk);
        } catch (ReglaDeNegocioException $error) {
            $this->avisar('error', $error->getMessage());
        }
        $this->redirigir('/administrador/usuarios');
    }

    /** @return array{nombres: string, apellidos: string, correo: string, telefono: string, idRol: string} */
    private function valoresDelFormulario(): array
    {
        return [
            'nombres' => (string) ($_POST['nombres'] ?? ''),
            'apellidos' => (string) ($_POST['apellidos'] ?? ''),
            'correo' => (string) ($_POST['correo'] ?? ''),
            'telefono' => (string) ($_POST['telefono'] ?? ''),
            'idRol' => (string) ($_POST['idRol'] ?? ''),
        ];
    }

    /** @param array<string, string> $valores */
    private function mostrarFormulario(string $titulo, string $accion, ?int $idUsuario, array $valores, string $error): void
    {
        $this->render('usuario_formulario', [
            'titulo' => $titulo,
            'accion' => $accion,
            'idUsuario' => $idUsuario,
            'valores' => $valores + ['nombres' => '', 'apellidos' => '', 'correo' => '', 'telefono' => '', 'idRol' => ''],
            'roles' => $this->roles->listarTodos(),
            'error' => $error,
        ]);
    }
}