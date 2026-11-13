<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-01 criterio 3: el usuario cambia su contraseña (obligatorio en su primer ingreso). */
final class CambiarContrasenia
{
    public function __construct(private UsuarioRepositorio $usuarios)
    {
    }

    public function ejecutar(int $idUsuario, string $nuevaContrasenia): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        if ($usuario === null) {
            throw new ReglaDeNegocioException('No se encontró al usuario.');
        }

        $usuario->cambiarContrasenia($nuevaContrasenia);
        $this->usuarios->guardar($usuario);
    }
}