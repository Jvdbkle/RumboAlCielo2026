<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\VerificarSesion;
use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;

/**
 * Guardia de las pantallas privadas (HU-05 criterio 2).
 * Antes de mostrar una pantalla privada confirma que hay sesión vigente y que el
 * rol del usuario le corresponde. No contiene reglas de negocio: la regla de la
 * expiración vive en el caso de uso VerificarSesion.
 */
final class Guardia extends ControladorBase
{
    public function __construct(
        private VerificarSesion $verificarSesion,
        private Sesion $sesion,
        private RolRepositorio $roles,
    ) {
    }

    /** Sin sesión vigente se redirige al login, sin mostrar información privada. */
    public function exigirSesion(): void
    {
        if (!$this->verificarSesion->ejecutar()) {
            $this->redirigir('/login');
        }
    }

    public function exigirAdministrador(): void
    {
        $this->exigirSesion();
        if (!$this->esAdministrador()) {
            $this->redirigir($this->rutaDelPanel());
        }
    }

    public function exigirEmpleado(): void
    {
        $this->exigirSesion();
        if ($this->esAdministrador()) {
            $this->redirigir($this->rutaDelPanel());
        }
    }

    /** HU-01 criterio 1: cada rol tiene su pantalla de inicio. */
    public function rutaDelPanel(): string
    {
        return $this->esAdministrador() ? '/administrador' : '/empleado';
    }

    private function esAdministrador(): bool
    {
        $idRol = $this->sesion->idRol();
        $rol = $idRol === null ? null : $this->roles->buscarPorId($idRol);
        return $rol !== null && $rol->esAdministrador();
    }
}