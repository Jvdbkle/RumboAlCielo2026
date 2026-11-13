<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Aplicacion;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Aplicacion\CasosDeUso\EditarPlato;
use RumboAlCielo\Aplicacion\CasosDeUso\RegistrarPlato;
use RumboAlCielo\Aplicacion\Puertos\ArchivoSubido;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Entidades\Plato;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;
use RumboAlCielo\Tests\Dobles\AlmacenFotosEnMemoria;
use RumboAlCielo\Tests\Dobles\MenuRepositorioEnMemoria;

/** HU-12 Gestión de platos del día (criterios 1 a 5). */
final class GestionPlatosTest extends TestCase
{
    private const MARTES = '2026-11-17';

    private MenuRepositorioEnMemoria $menus;
    private AlmacenFotosEnMemoria $fotos;
    private RegistrarPlato $registrarPlato;
    private EditarPlato $editarPlato;

    protected function setUp(): void
    {
        $this->menus = new MenuRepositorioEnMemoria();
        $this->fotos = new AlmacenFotosEnMemoria();
        $this->registrarPlato = new RegistrarPlato($this->menus, $this->fotos);
        $this->editarPlato = new EditarPlato($this->menus, $this->fotos);
    }

    /** HU-12 criterio 1: se almacenan los componentes y las fotos de ese día. */
    public function test_registra_los_componentes_y_las_fotos_del_dia(): void
    {
        $this->registrar(self::MARTES);

        $plato = $this->platoDel(self::MARTES);
        $this->assertSame('Sopa de maní', $plato->getSopa());
        $this->assertSame('Pique macho', $plato->getPlatoPrincipal());
        $this->assertSame('Ensalada mixta', $plato->getEnsaladaGuarnicion());
        $this->assertSame('Gelatina', $plato->getPostre());
        $this->assertSame('Refresco de linaza', $plato->getRefresco());
        $this->assertSame('foto-1.jpg', $plato->getFotoSopa());
        $this->assertSame('foto-2.png', $plato->getFotoPlatoPrincipal());
    }

    /** HU-12 criterio 2: una foto que no es JPG ni PNG se rechaza y se conserva la anterior. */
    public function test_una_foto_que_no_es_jpg_ni_png_se_rechaza_y_se_conserva_la_anterior(): void
    {
        $this->registrar(self::MARTES);
        $fotoAnterior = $this->platoDel(self::MARTES)->getFotoSopa();

        $mensaje = $this->mensajeDeError(fn () => $this->editarPlato->ejecutar(
            self::MARTES, 'Sopa nueva', 'Pique macho', 'Ensalada mixta', 'Gelatina', 'Refresco de linaza',
            new ArchivoSubido('sopa.gif', 1000, 'image/gif', '/tmp/x')
        ));

        $this->assertSame('La foto debe ser JPG o PNG.', $mensaje);
        $this->assertSame($fotoAnterior, $this->platoDel(self::MARTES)->getFotoSopa());
        $this->assertSame('Sopa de maní', $this->platoDel(self::MARTES)->getSopa());
    }

    /** HU-12 criterio 3: se guardan los cambios y se registra el aviso para los empleados con reserva. */
    public function test_al_modificar_un_plato_se_guardan_los_cambios_y_se_avisa_del_cambio(): void
    {
        $this->registrar(self::MARTES);

        $this->editarPlato->ejecutar(
            self::MARTES, 'Sopa de fideo', 'Pique macho', 'Ensalada mixta', 'Gelatina', 'Refresco de linaza'
        );

        $plato = $this->platoDel(self::MARTES);
        $this->assertSame('Sopa de fideo', $plato->getSopa());
        $this->assertSame('foto-1.jpg', $plato->getFotoSopa(), 'Sin foto nueva se conserva la actual.');
        $eventos = $plato->extraerEventos();
        $this->assertSame('plato.modificado', $eventos[0]['evento']);
        $this->assertSame(self::MARTES, $eventos[0]['datos']['fecha']);
    }

    /** HU-12 criterio 4: un componente de más de 100 caracteres se rechaza e indica el máximo. */
    public function test_rechaza_un_componente_de_mas_de_100_caracteres(): void
    {
        $mensaje = $this->mensajeDeError(fn () => $this->registrar(self::MARTES, str_repeat('a', 101)));

        $this->assertSame('Cada componente del plato admite como máximo 100 caracteres.', $mensaje);
        $this->assertSame(null, $this->menus->buscarPorFechaInicio(new DateTimeImmutable('2026-11-16')));
    }

    /** HU-12 criterio 5: una foto de más de 5 MB o que no es una imagen real se rechaza. */
    public function test_rechaza_una_foto_pesada_o_que_no_es_una_imagen_real(): void
    {
        $pesada = new ArchivoSubido('sopa.png', 5 * 1024 * 1024 + 1, 'image/png', '/tmp/x');
        $falsa = new ArchivoSubido('virus.jpg', 1000, 'text/x-php', '/tmp/x'); // se llama .jpg pero es código

        $mensajePesada = $this->mensajeDeError(fn () => $this->registrar(self::MARTES, 'Sopa de maní', $pesada));
        $mensajeFalsa = $this->mensajeDeError(fn () => $this->registrar(self::MARTES, 'Sopa de maní', $falsa));

        $this->assertSame('La foto no puede pesar más de 5 MB.', $mensajePesada);
        $this->assertSame('La foto debe ser JPG o PNG.', $mensajeFalsa);
        $this->assertSame([], $this->fotos->guardadas(), 'No se guarda ninguna foto si algo falla.');
    }

    /** HU-12 criterio 1 y HU-13 criterio 2: los platos solo se registran de lunes a jueves, uno por día. */
    public function test_solo_se_registran_platos_de_lunes_a_jueves_y_uno_por_dia(): void
    {
        $viernes = $this->mensajeDeError(fn () => $this->registrar('2026-11-20'));
        $this->assertSame('Solo se pueden registrar platos de lunes a jueves.', $viernes);

        $this->registrar(self::MARTES);
        $repetido = $this->mensajeDeError(fn () => $this->registrar(self::MARTES));
        $this->assertSame('Ya hay un plato registrado para ese día.', $repetido);
    }

    /** Los platos de una misma semana quedan dentro del mismo menú semanal. */
    public function test_los_platos_de_la_misma_semana_quedan_en_el_mismo_menu(): void
    {
        $this->registrar('2026-11-16');
        $this->registrar('2026-11-18');

        $menu = $this->menus->buscarPorFechaInicio(new DateTimeImmutable('2026-11-16'));
        $this->assertSame(2, count($menu->getPlatos()));
        $this->assertSame($menu->getIdMenu(), $this->platoDel('2026-11-18')->getIdMenu());
    }

    private function registrar(string $fecha, string $sopa = 'Sopa de maní', ?ArchivoSubido $fotoSopa = null): void
    {
        $this->registrarPlato->ejecutar(
            $fecha,
            $sopa,
            'Pique macho',
            'Ensalada mixta',
            'Gelatina',
            'Refresco de linaza',
            $fotoSopa ?? new ArchivoSubido('sopa.jpg', 1000, 'image/jpeg', '/tmp/x'),
            new ArchivoSubido('plato.png', 1000, 'image/png', '/tmp/x'),
        );
    }

    private function platoDel(string $fecha): Plato
    {
        $dia = new DateTimeImmutable($fecha);
        return $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($dia))->consultarDia($dia);
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