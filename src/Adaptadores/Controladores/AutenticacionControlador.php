<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Controladores;

use RumboAlCielo\Aplicacion\CasosDeUso\CambiarContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\CerrarSesion;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Aplicacion\Puertos\Sesion;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;

/** HU-01 y HU-05: reciben el formulario y llaman al caso de uso. Sin reglas de negocio. */
final class AutenticacionControlador extends ControladorBase
{
    public function __construct(
        private IniciarSesion $iniciarSesion,
        private CambiarContrasenia $cambiarContrasenia,
        private CerrarSesion $cerrarSesion,
        private Sesion $sesion,
        private Guardia $guardia,
    ) {
    }

    public function mostrarLogin(): void
    {
        $this->render('login', ['error' => '', 'correo' => '']);
    }

       public function iniciarSesion(): void
    {
        $this->exigirTokenValido();

        $correo = trim((string) ($_POST['correo'] ?? ''));
        $contrasenia = (string) ($_POST['contrasenia'] ?? '');

        try {
            $resultado = $this->iniciarSesion->ejecutar($correo, $contrasenia);
        } catch (ReglaDeNegocioException $error) {
            $this->render('login', ['error' => $error->getMessage(), 'correo' => $correo]);
            return;
        }

        $this->sesion->iniciar(
            $resultado->usuario->getIdUsuario(),
            $resultado->rol->getIdRol(),
            Reloj::ahora(),
        );

        // HU-01 criterio 3: en el primer ingreso se pide cambiar la contraseña.
        if ($resultado->requiereCambioContrasenia) {
            $this->redirigir('/cambiar-contrasenia');
        }

        // HU-01 criterio 1: cada rol va a su pantalla.
        $this->redirigir($this->guardia->rutaDelPanel());
    }

    public function mostrarCambioContrasenia(): void
    {
        $this->guardia->exigirSesion();
        $this->render('cambiar_contrasenia', ['error' => '']);
    }

       public function cambiarContrasenia(): void
    {
        $this->guardia->exigirSesion();
        $this->exigirTokenValido();

        try {
            $this->cambiarContrasenia->ejecutar((int) $this->sesion->idUsuario(), (string) ($_POST['nueva'] ?? ''));
        } catch (ReglaDeNegocioException $error) {
            $this->render('cambiar_contrasenia', ['error' => $error->getMessage()]);
            return;
        }

        $this->redirigir($this->guardia->rutaDelPanel());
    }

    /** HU-05 criterio 1: finaliza la sesión y redirige a la pantalla de inicio de sesión. */
        public function cerrarSesion(): void
    {
        $this->guardia->exigirSesion();
        $this->exigirTokenValido();

        $this->cerrarSesion->ejecutar();
        $this->redirigir('/login');
    }
}