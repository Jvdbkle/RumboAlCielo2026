<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\UsuarioRepositorio;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-01 Inicio de sesión. */
final class IniciarSesion
{
    public const MENSAJE_CREDENCIALES = 'Usuario o Contraseña incorrecta';

    public function __construct(
        private UsuarioRepositorio $usuarios,
        private RolRepositorio $roles,
    ) {
    }

    public function ejecutar(string $correo, string $contrasenia): ResultadoInicioSesion
    {
        $usuario = $this->usuarios->buscarPorCorreo($correo);
        if ($usuario === null) {
            throw new ReglaDeNegocioException(self::MENSAJE_CREDENCIALES);
        }

        // HU-03 criterio 2
        if (!$usuario->estaHabilitado()) {
            throw new ReglaDeNegocioException('Usuario deshabilitado');
        }

        // HU-01 criterio 4
        if ($usuario->estaBloqueado()) {
            throw new ReglaDeNegocioException('La cuenta está bloqueada por 5 minutos');
        }

        $correcto = $usuario->iniciarSesion($correo, $contrasenia);
        // Se guarda siempre: los intentos fallidos y el bloqueo deben conservarse.
        $this->usuarios->guardar($usuario);

        // HU-01 criterio 2: no se dice si falló el correo o la contraseña.
        if (!$correcto) {
            throw new ReglaDeNegocioException(self::MENSAJE_CREDENCIALES);
        }

        $rol = $this->roles->buscarPorId($usuario->getIdRol());
        if ($rol === null) {
            throw new ReglaDeNegocioException('El usuario no tiene un rol asignado');
        }

        // HU-01 criterios 1 y 3
        return new ResultadoInicioSesion($usuario, $rol, $usuario->esPrimerIngreso());
    }
}