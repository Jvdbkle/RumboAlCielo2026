<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\CambiarContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/** HU-01 Inicio de sesión y cambio de contraseña: reciben el formulario y llaman al caso de uso. Sin reglas de negocio. */
final class AutenticacionControlador extends ControladorBase
{
    public function __construct(
        private IniciarSesion $iniciarSesion,
        private CambiarContrasenia $cambiarContrasenia,
    ) {
    }

    public function mostrarLogin(): void
    {
        $this->render('login', ['error' => '', 'correo' => '']);
    }

    public function iniciarSesion(): void
    {
        $correo = trim((string) ($_POST['correo'] ?? ''));
        $contrasenia = (string) ($_POST['contrasenia'] ?? '');

        try {
            $resultado = $this->iniciarSesion->ejecutar($correo, $contrasenia);
        } catch (ReglaDeNegocioException $error) {
            $this->render('login', ['error' => $error->getMessage(), 'correo' => $correo]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['idUsuario'] = $resultado->usuario->getIdUsuario();
        $_SESSION['rol'] = $resultado->rol->getNombreRol();

        // HU-01 criterio 3: en el primer ingreso se pide cambiar la contraseña.
        if ($resultado->requiereCambioContrasenia) {
            $this->redirigir('/cambiar-contrasenia');
        }

        // HU-01 criterio 1: cada rol va a su pantalla.
        $this->redirigirAlPanel();
    }

    public function mostrarCambioContrasenia(): void
    {
        $this->exigirSesion();
        $this->render('cambiar_contrasenia', ['error' => '']);
    }

    public function cambiarContrasenia(): void
    {
        $this->exigirSesion();

        try {
            $this->cambiarContrasenia->ejecutar((int) $_SESSION['idUsuario'], (string) ($_POST['nueva'] ?? ''));
        } catch (ReglaDeNegocioException $error) {
            $this->render('cambiar_contrasenia', ['error' => $error->getMessage()]);
            return;
        }

        $this->redirigirAlPanel();
    }

    private function exigirSesion(): void
    {
        if (!isset($_SESSION['idUsuario'])) {
            $this->redirigir('/login');
        }
    }

    private function redirigirAlPanel(): never
    {
        $this->redirigir($_SESSION['rol'] === Rol::ADMINISTRADOR ? '/administrador' : '/empleado');
    }
}