<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\PublicarMenu;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarFeriado;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarPlato;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Dominio\Reloj;
use RumboAlCielo\Tests\Dobles\AlmacenFotosEnMemoria;
use RumboAlCielo\Tests\Dobles\FeriadoRepositorioEnMemoria;
use RumboAlCielo\Tests\Dobles\MenuRepositorioEnMemoria;
use RumboAlCielo\Aplicacion\CasosDeUso\ConsultarSemanaDelMenu;


/** HU-13 Armar y publicar el menú semanal y HU-14 Consultar el menú de la semana. */
final class MenuSemanalTest extends TestCase
{
    private const LUNES = '2026-11-16';

    private MenuRepositorioEnMemoria $menus;
    private FeriadoRepositorioEnMemoria $feriados;
    private RegistrarPlato $registrarPlato;
    private PublicarMenu $publicarMenu;
    private ConsultarMenu $consultarMenu;

    protected function setUp(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 08:00'));

        $this->menus = new MenuRepositorioEnMemoria();
        $this->feriados = new FeriadoRepositorioEnMemoria();
        $this->registrarPlato = new RegistrarPlato($this->menus, new AlmacenFotosEnMemoria());
        $this->publicarMenu = new PublicarMenu($this->menus, $this->feriados);
        $this->consultarMenu = new ConsultarMenu($this->menus);
    }

    protected function tearDown(): void
    {
        Reloj::fijar(null);
    }

    /** HU-13 criterio 1: el administrador asigna los platos de lunes a jueves y publica la semana completa. */
    public function test_publica_la_semana_completa_y_los_empleados_la_pueden_consultar(): void
    {
        foreach (['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19'] as $fecha) {
            $this->registrar($fecha, 'Sopa del ' . $fecha);
        }

        $this->publicarMenu->ejecutar(self::LUNES);

        $resultado = $this->consultarMenu->ejecutar();
        $this->assertTrue($resultado->publicado);
        $this->assertSame(4, count($resultado->dias));
    }

    /** HU-13 criterio 2: el menú solo permite días de lunes a jueves. */
    public function test_no_permite_viernes_sabado_ni_domingo(): void
    {
        foreach (['2026-11-20', '2026-11-21', '2026-11-22'] as $finDeSemana) {
            $mensaje = $this->mensajeDeError(fn () => $this->registrar($finDeSemana));
            $this->assertSame('Solo se pueden registrar platos de lunes a jueves.', $mensaje);
        }
    }

    /** HU-13 criterio 3: con un feriado en la semana no se publica el menú para esa fecha. */
    public function test_no_publica_menu_para_una_fecha_feriada(): void
    {
        $this->registrar('2026-11-17');
        $this->registrar('2026-11-19');
        (new RegistrarFeriado($this->feriados))->ejecutar('2026-11-19', 'Día de la Patria');

        $mensaje = $this->mensajeDeError(fn () => $this->publicarMenu->ejecutar(self::LUNES));

        $this->assertStringContainsString('19/11/2026', $mensaje);
        $this->assertStringContainsString('Día de la Patria', $mensaje);
        $this->assertFalse($this->consultarMenu->ejecutar()->publicado);
    }

    /** Un menú sin platos asignados no se puede publicar. */
    public function test_no_publica_una_semana_sin_platos(): void
    {
        $mensaje = $this->mensajeDeError(fn () => $this->publicarMenu->ejecutar(self::LUNES));

        $this->assertSame('No hay platos registrados para esa semana.', $mensaje);
    }

    /** HU-14 criterio 1: el empleado ve por cada día los componentes y las fotos. */
    public function test_el_empleado_ve_cada_dia_con_sus_componentes_y_fotos(): void
    {
        $this->registrar('2026-11-17', 'Sopa de maní');
        $this->registrar('2026-11-16', 'Sopa de fideo');
        $this->publicarMenu->ejecutar(self::LUNES);

        $dias = $this->consultarMenu->ejecutar()->dias;

        $this->assertSame('2026-11-16', $dias[0]->fecha->format('Y-m-d'), 'Los días salen ordenados.');
        $this->assertSame('Sopa de fideo', $dias[0]->sopa);
        $this->assertSame('Pique macho', $dias[0]->platoPrincipal);
        $this->assertSame('Ensalada mixta', $dias[0]->ensaladaGuarnicion);
        $this->assertSame('Gelatina', $dias[0]->postre);
        $this->assertSame('Refresco de linaza', $dias[0]->refresco);
        $this->assertSame('foto-3.jpg', $dias[0]->fotoSopa);
        $this->assertSame('foto-4.png', $dias[0]->fotoPlatoPrincipal);
    }

    /** HU-14 criterio 2: desde el lunes a las 09:01 sin menú publicado se avisa que está en proceso. */
    public function test_el_lunes_desde_las_0901_sin_menu_publicado_se_avisa_que_esta_en_proceso(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 09:01'));

        $resultado = $this->consultarMenu->ejecutar();

        $this->assertFalse($resultado->publicado);
        $this->assertSame('El menú de la semana está en proceso de carga.', $resultado->aviso);
    }

    /** HU-14 criterio 2 (límite): hasta las 09:00 todavía no se muestra el aviso. */
    public function test_hasta_las_0900_del_lunes_no_se_muestra_el_aviso(): void
    {
        Reloj::fijar(new DateTimeImmutable('2026-11-16 09:00'));

        $this->assertSame('', $this->consultarMenu->ejecutar()->aviso);
    }

        /** HU-13 criterio 1: el administrador ve los cuatro días de la semana con su plato y si está publicada. */
    public function test_el_administrador_ve_los_cuatro_dias_con_su_plato_y_los_feriados(): void
    {
        $this->registrar('2026-11-17', 'Sopa de maní');
        (new RegistrarFeriado($this->feriados))->ejecutar('2026-11-19', 'Día de la Patria');

        $semana = (new ConsultarSemanaDelMenu($this->menus, $this->feriados))->ejecutar(new DateTimeImmutable('2026-11-18'));

        $this->assertSame('2026-11-16', $semana->lunes->format('Y-m-d'));
        $this->assertSame(4, count($semana->dias));
        $this->assertSame(null, $semana->dias[0]->plato, 'El lunes todavía no tiene plato.');
        $this->assertSame('Sopa de maní', $semana->dias[1]->plato->sopa);
        $this->assertSame('Día de la Patria', $semana->dias[3]->motivoFeriado);
        $this->assertTrue($semana->tienePlatos());
        $this->assertFalse($semana->publicado);
    }

    /** HU-13 criterio 1: una semana sin platos muestra los cuatro días vacíos y no se puede publicar. */
    public function test_una_semana_sin_platos_muestra_los_cuatro_dias_vacios(): void
    {
        $semana = (new ConsultarSemanaDelMenu($this->menus, $this->feriados))->ejecutar();

        $this->assertSame(4, count($semana->dias));
        $this->assertFalse($semana->tienePlatos());
        $this->assertFalse($semana->publicado);
    }
    /** Un menú con platos pero sin publicar no se muestra al empleado. */
    public function test_un_menu_sin_publicar_no_muestra_sus_platos(): void
    {
        $this->registrar('2026-11-17');
        Reloj::fijar(new DateTimeImmutable('2026-11-17 10:00'));

        $resultado = $this->consultarMenu->ejecutar();

        $this->assertFalse($resultado->publicado);
        $this->assertSame([], $resultado->dias);
        $this->assertSame('El menú de la semana está en proceso de carga.', $resultado->aviso);
    }

    private function registrar(string $fecha, string $sopa = 'Sopa de maní'): void
    {
        $this->registrarPlato->ejecutar(
            $fecha,
            $sopa,
            'Pique macho',
            'Ensalada mixta',
            'Gelatina',
            'Refresco de linaza',
            new ArchivoSubido('sopa.jpg', 1000, 'image/jpeg', '/tmp/x'),
            new ArchivoSubido('plato.png', 1000, 'image/png', '/tmp/x'),
        );
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