<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-03 criterio 2: el administrador deshabilita a un empleado que ya no trabaja en la
 * institución; al intentar ingresar verá "Usuario deshabilitado" (lo resuelve IniciarSesion).
 */
final class DeshabilitarUsuario
{
    public function __construct(private UsuarioRepositorio $usuarios)
    {
    }

    public function ejecutar(int $idUsuario): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        if ($usuario === null) {
            throw new ReglaDeNegocioException('No se encontró al usuario.');
        }

        $usuario->deshabilitar();
        $this->usuarios->guardar($usuario);
    }
}