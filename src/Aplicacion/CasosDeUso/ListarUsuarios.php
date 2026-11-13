<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;

/**
 * HU-03 criterio 3: el administrador consulta la lista de usuarios registrados
 * con su estado (habilitado o deshabilitado).
 */
final class ListarUsuarios
{
    public function __construct(
        private UsuarioRepositorio $usuarios,
        private PersonaRepositorio $personas,
        private RolRepositorio $roles,
    ) {
    }

    /** @return FilaUsuario[] */
    public function ejecutar(): array
    {
        $filas = [];
        foreach ($this->usuarios->listarTodos() as $usuario) {
            $persona = $this->personas->buscarPorId($usuario->getIdPersona());
            $rol = $this->roles->buscarPorId($usuario->getIdRol());
            $filas[] = new FilaUsuario(
                $usuario->getIdUsuario(),
                $persona === null ? '' : $persona->obtenerNombreCompleto(),
                $usuario->getCorreo(),
                $rol === null ? '' : $rol->getNombreRol(),
                $usuario->getEstado()->value,
            );
        }
        return $filas;
    }
}