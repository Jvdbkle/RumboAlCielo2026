<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\CerrarSesion;
use RumboAlCielo\Aplicacion\CasosDeUso\VerificarSesion;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\SesionEnMemoria;

/** HU-05 Cerrar sesión. */
final class CerrarSesionTest extends TestCase
{
    private SesionEnMemoria $sesion;
    private CerrarSesion $cerrarSesion;
    private VerificarSesion $verificarSesion;

    protected function setUp(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:00'));

        $this->sesion = new SesionEnMemoria();
        $this->sesion->iniciar(1, 2, Reloj::ahora());

        $this->cerrarSesion = new CerrarSesion($this->sesion);
        $this->verificarSesion = new VerificarSesion($this->sesion);
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
    }

    /** HU-05 criterio 1: al presionar "Cerrar Sesión" la sesión finaliza. */
    public function test_al_cerrar_sesion_ya_no_hay_sesion_activa(): void
    {
        $this->assertTrue($this->verificarSesion->ejecutar());

        $this->cerrarSesion->ejecutar();

        $this->assertNull($this->sesion->idUsuario());
        $this->assertNull($this->sesion->idRol());
    }

    /** HU-05 criterio 2: después de cerrar la sesión no se accede a pantallas privadas. */
    public function test_despues_de_cerrar_sesion_no_se_accede_a_pantallas_privadas(): void
    {
        $this->cerrarSesion->ejecutar();

        $this->assertFalse($this->verificarSesion->ejecutar());
    }

    /** HU-05 criterio 2: la sesión expira tras 15 minutos sin usar el sistema. */
    public function test_la_sesion_expira_tras_quince_minutos_de_inactividad(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:16'));

        $this->assertFalse($this->verificarSesion->ejecutar());
        $this->assertNull($this->sesion->idUsuario(), 'La sesión expirada se destruye.');
    }

    /** Usar el sistema renueva el plazo: a los 14 minutos todavía hay sesión. */
    public function test_usar_el_sistema_renueva_el_plazo_de_inactividad(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:14'));
        $this->assertTrue($this->verificarSesion->ejecutar());

        // 14 minutos después de la última actividad (08:14) sigue vigente.
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:28'));
        $this->assertTrue($this->verificarSesion->ejecutar());
    }

    public function test_sin_haber_iniciado_sesion_no_se_accede(): void
    {
        $this->assertFalse((new VerificarSesion(new SesionEnMemoria()))->ejecutar());
    }
}