<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-06 criterios 1 y 3: el administrador reincorpora a un empleado dado de baja.
 * El usuario no se borra, así que conserva su historial (criterio 2). Si ya está
 * habilitado, la entidad rechaza la acción e informa el motivo (criterio 3).
 */
final class ReincorporarUsuario
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

        $usuario->habilitar();
        $this->usuarios->guardar($usuario);
    }
}