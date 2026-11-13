<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\CambiarContrasenia;
use RumboAlCielo\Aplicacion\CasosDeUso\IniciarSesion;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Entidades\Usuario;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-01 criterio 3: en el primer ingreso se pide cambiar la contraseña. */
final class CambiarContraseniaTest extends TestCase
{
    private const CORREO = 'juan@correo.com';
    private const TELEFONO = '70000000';

    private UsuarioRepositorioEnMemoria $usuarios;
    private CambiarContrasenia $cambiarContrasenia;
    private IniciarSesion $iniciarSesion;

    protected function setUp(): void
    {
        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $this->usuarios = new UsuarioRepositorioEnMemoria();
        $this->usuarios->guardar(Usuario::registrarNuevo(1, 1, self::CORREO, self::TELEFONO, 2));

        $this->cambiarContrasenia = new CambiarContrasenia($this->usuarios);
        $this->iniciarSesion = new IniciarSesion($this->usuarios, $roles);
    }

    public function test_despues_del_cambio_se_ingresa_con_la_contrasenia_nueva_y_ya_no_con_el_telefono(): void
    {
        $this->cambiarContrasenia->ejecutar(1, 'MiClave#2026');

        $resultado = $this->iniciarSesion->ejecutar(self::CORREO, 'MiClave#2026');
        $this->assertFalse($resultado->requiereCambioContrasenia);

        $this->assertSame('Usuario o Contraseña incorrecta', $this->mensajeDeError(
            fn () => $this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO)
        ));
    }

    public function test_no_se_acepta_una_contrasenia_vacia(): void
    {
        $mensaje = $this->mensajeDeError(fn () => $this->cambiarContrasenia->ejecutar(1, '   '));

        $this->assertSame('La nueva contraseña no puede estar vacía.', $mensaje);
        // Sigue pudiendo ingresar con el teléfono: la contraseña no cambió.
        $this->assertTrue($this->iniciarSesion->ejecutar(self::CORREO, self::TELEFONO)->requiereCambioContrasenia);
    }

    public function test_un_usuario_inexistente_es_rechazado(): void
    {
        $mensaje = $this->mensajeDeError(fn () => $this->cambiarContrasenia->ejecutar(99, 'clave'));

        $this->assertSame('No se encontró al usuario.', $mensaje);
    }

    private function mensajeDeError(callable $accion): string
    {
        try {
            $accion();
        } catch (ReglaDeNegocioException $e) {
            return $e->getMessage();
        }
        $this->fail('Se esperaba que el sistema rechazara la operación.');
    }
        /** HU-01 criterio 5: una contraseña débil se rechaza y se indican los requisitos. */
    public function test_una_contrasenia_debil_es_rechazada_con_los_requisitos(): void
    {
        $debiles = [
            'Ab#1',            // menos de 8 caracteres
            'clave#2026x',     // sin mayúscula
            'CLAVE#2026X',     // sin minúscula
            'Clave#abcdef',    // sin número
            'Clave2026abc',    // sin símbolo especial
            self::TELEFONO,    // igual al teléfono
        ];

        foreach ($debiles as $debil) {
            $mensaje = $this->mensajeDeError(fn () => $this->cambiarContrasenia->ejecutar(1, $debil));
            $this->assertSame(
                'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo especial, y ser distinta de tu teléfono.',
                $mensaje,
                "Se esperaba rechazar: $debil"
            );
        }
    }
}