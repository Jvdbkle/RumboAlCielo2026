<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Validacion\Validador;

/**
 * HU-03 criterio 4: el administrador modifica los datos de un usuario y el sistema
 * los actualiza. Se aplican las mismas reglas de formato y de correo único que al registrar.
 */
final class EditarUsuario
{
    public function __construct(
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
    ) {
    }

    public function ejecutar(int $idUsuario, string $nombres, string $apellidos, string $correo, string $telefono): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        $persona = $usuario === null ? null : $this->personas->buscarPorId($usuario->getIdPersona());
        if ($usuario === null || $persona === null) {
            throw new ReglaDeNegocioException('No se encontró al usuario.');
        }

        // Se valida todo antes de cambiar nada: si un dato es inválido no se modifica ninguno.
        Validador::nombre($nombres, 'nombres');
        Validador::nombre($apellidos, 'apellidos');
        $correo = Validador::correo($correo);
        Validador::telefono($telefono);

        // El correo no puede pertenecer a otro usuario (HU-03 criterio 5).
        $otro = $this->usuarios->buscarPorCorreo($correo);
        if ($otro !== null && $otro->getIdUsuario() !== $usuario->getIdUsuario()) {
            throw new ReglaDeNegocioException('El correo ya está registrado.');
        }

        $persona->setNombres($nombres);
        $persona->setApellidos($apellidos);
        $usuario->setCorreo($correo);
        $usuario->setTelefono($telefono);

        $this->personas->guardar($persona);
        $this->usuarios->guardar($usuario);
    }
}