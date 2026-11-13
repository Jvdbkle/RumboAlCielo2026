<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-02: solo el administrador reestablece la contraseña de un usuario (criterio 2);
 * el teléfono vuelve a ser su contraseña (criterio 1) y deberá cambiarla al ingresar.
 */
final class ReestablecerContrasenia
{
    public function __construct(
        private UsuarioRepositorio $usuarios,
        private RolRepositorio $roles,
    ) {
    }

    public function ejecutar(int $idSolicitante, int $idUsuario): void
    {
        $solicitante = $this->usuarios->buscarPorId($idSolicitante);
        $rol = $solicitante === null ? null : $this->roles->buscarPorId($solicitante->getIdRol());
        if ($rol === null || !$rol->esAdministrador()) {
            throw new ReglaDeNegocioException('Solo el administrador puede reestablecer contraseñas.');
        }

        $usuario = $this->usuarios->buscarPorId($idUsuario);
        if ($usuario === null) {
            throw new ReglaDeNegocioException('No se encontró al usuario.');
        }

        $usuario->reestablecerContrasenia();
        $this->usuarios->guardar($usuario);
    }
}