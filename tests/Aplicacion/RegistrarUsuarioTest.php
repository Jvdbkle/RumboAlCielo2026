<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarUsuario;
use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Tests\Dobles\PersonaRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\RolRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\UsuarioRepositorioEnMemoria;

/** HU-03 Gestión de Usuarios: registro (criterios 1, 5, 6, 7, 8 y 9). */
final class RegistrarUsuarioTest extends TestCase
{
    private UsuarioRepositorioEnMemoria $usuarios;
    private RegistrarUsuario $registrarUsuario;

    protected function setUp(): void
    {
        $roles = new RolRepositorioEnMemoria();
        $roles->agregar(new Rol(1, Rol::ADMINISTRADOR));
        $roles->agregar(new Rol(2, Rol::EMPLEADO));

        $this->usuarios = new UsuarioRepositorioEnMemoria();
        $this->registrarUsuario = new RegistrarUsuario($this->usuarios, new PersonaRepositorioEnMemoria(), $roles);
    }

    /** HU-03 criterio 1: el correo es el login y el teléfono es la primera contraseña. */
    public function test_registra_un_usuario_con_el_correo_como_login_y_el_telefono_como_contrasenia(): void
    {
        $id = $this->registrarUsuario->ejecutar('Juan', 'Pérez', 'Juan@Correo.com', '70000000', 2);

        $usuario = $this->usuarios->buscarPorId($id);
        $this->assertSame('juan@correo.com', $usuario->getCorreo());
        $this->assertTrue(password_verify('70000000', $usuario->getContrasenia()));
        $this->assertTrue($usuario->esPrimerIngreso());
        $this->assertTrue($usuario->estaHabilitado());
    }

    /** HU-03 criterio 5: un correo que ya pertenece a otro usuario es rechazado. */
    public function test_rechaza_un_correo_que_ya_esta_registrado(): void
    {
        $this->registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', '70000000', 2);

        $mensaje = $this->mensajeDeError(
            fn () => $this->registrarUsuario->ejecutar('Ana', 'Rojas', 'JUAN@correo.com', '71111111', 2)
        );

        $this->assertSame('El correo ya está registrado.', $mensaje);
    }

    /** HU-03 criterio 6: si falta un dato obligatorio se indica cuál y no se guarda. */
    public function test_indica_el_dato_faltante_y_no_guarda_el_registro(): void
    {
        $mensaje = $this->mensajeDeError(
            fn () => $this->registrarUsuario->ejecutar('Juan', 'Pérez', '', '70000000', 2)
        );

        $this->assertSame('Falta el dato obligatorio: correo.', $mensaje);
        $this->assertSame(null, $this->usuarios->buscarPorId(1));
    }

    /** HU-03 criterio 7: nombres y apellidos solo admiten letras y espacios (2 a 100). */
    public function test_rechaza_nombres_con_numeros_o_simbolos(): void
    {
        foreach (['Juan3', 'J', 'Juan@', str_repeat('a', 101)] as $invalido) {
            $mensaje = $this->mensajeDeError(
                fn () => $this->registrarUsuario->ejecutar($invalido, 'Pérez', 'juan@correo.com', '70000000', 2)
            );
            $this->assertSame('El campo nombres solo admite letras y espacios, de 2 a 100 caracteres.', $mensaje);
        }
    }

    /** HU-03 criterio 8: el correo debe tener formato válido. */
    public function test_rechaza_un_correo_con_formato_invalido(): void
    {
        foreach (['juan', 'juan@', 'juan correo@x.com', str_repeat('a', 150) . '@x.com'] as $invalido) {
            $mensaje = $this->mensajeDeError(
                fn () => $this->registrarUsuario->ejecutar('Juan', 'Pérez', $invalido, '70000000', 2)
            );
            $this->assertSame('El correo no tiene un formato válido (máximo 150 caracteres).', $mensaje);
        }
    }

    /** HU-03 criterio 9: el teléfono debe tener exactamente 8 dígitos y solo números. */
    public function test_rechaza_un_telefono_que_no_son_8_digitos(): void
    {
        foreach (['7000000', '700000000', '7000000a', '7000 000'] as $invalido) {
            $mensaje = $this->mensajeDeError(
                fn () => $this->registrarUsuario->ejecutar('Juan', 'Pérez', 'juan@correo.com', $invalido, 2)
            );
            $this->assertSame('El teléfono debe tener exactamente 8 dígitos y solo números.', $mensaje);
        }
    }

    private function mensajeDeError(callable $accion): string
    {
        try {
            $accion();
        } catch (ReglaDeNegocioException $error) {
            return $error->getMessage();
        }
        $this->fail('Se esperaba que el sistema rechazara la operación.');
    }
}