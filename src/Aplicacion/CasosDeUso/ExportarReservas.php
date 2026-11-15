<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Puertos\GeneradorHojaCalculo;
use RumboAlCielo\Aplicacion\Repositorios\MenuRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\PersonaRepositorio;
use RumboAlCielo\Aplicacion\Repositorios\ReservaRepositorio;
use RumboAlCielo\Dominio\Entidades\Menu;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-25: el administrador exporta las reservas de un rango de fechas a CSV o Excel, con la
 * fecha, el usuario, el menú y el estado de cada una. Sin rango se le pide completarlo; sin
 * reservas en el rango se le informa y no se genera ningún archivo.
 */
final class ExportarReservas
{
    public const FORMATO_CSV = 'csv';
    public const FORMATO_EXCEL = 'xlsx';
    private const ENCABEZADOS = ['Fecha', 'Usuario', 'Menú', 'Estado'];

    public function __construct(
        private ReservaRepositorio $reservas,
        private PersonaRepositorio $personas,
        private MenuRepositorio $menus,
        private GeneradorHojaCalculo $hojas,
    ) {
    }

    public function ejecutar(?DateTimeImmutable $desde, ?DateTimeImmutable $hasta, string $formato): ArchivoExportado
    {
        // Criterio 3: sin rango completo no se continúa.
        if ($desde === null || $hasta === null) {
            throw new ReglaDeNegocioException('Completa el rango de fechas (desde y hasta) antes de exportar.');
        }
        if ($hasta < $desde) {
            throw new ReglaDeNegocioException('La fecha final no puede ser anterior a la fecha inicial.');
        }
        if (!in_array($formato, [self::FORMATO_CSV, self::FORMATO_EXCEL], true)) {
            throw new ReglaDeNegocioException('Elige el formato del archivo: CSV o Excel.');
        }

        $reservas = $this->reservas->listarEntre($desde->setTime(0, 0), $hasta->setTime(0, 0));
        // Criterio 2: sin datos se informa y no se genera el archivo.
        if ($reservas === []) {
            throw new ReglaDeNegocioException('No hay reservas en el rango seleccionado, por eso no se generó ningún archivo.');
        }

        $filas = [];
        $nombres = [];
        foreach ($reservas as $reserva) {
            $filas[] = [
                $reserva->getFecha()->format('d/m/Y'),
                $nombres[$reserva->getIdPersona()] ??= $this->nombre($reserva->getIdPersona()),
                $this->descripcionDelMenu($reserva->getFecha()),
                $reserva->getEstado()->value,
            ];
        }

        $base = 'reservas_' . $desde->format('Y-m-d') . '_a_' . $hasta->format('Y-m-d');
        if ($formato === self::FORMATO_EXCEL) {
            return new ArchivoExportado(
                $base . '.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                $this->hojas->generar('Reservas', self::ENCABEZADOS, $filas),
            );
        }
        return new ArchivoExportado($base . '.csv', 'text/csv; charset=utf-8', $this->aCsv($filas));
    }

    /** @param list<list<string>> $filas */
    private function aCsv(array $filas): string
    {
        // BOM y ";" para que Excel en español abra bien los acentos y las columnas.
        $lineas = [implode(';', self::ENCABEZADOS)];
        foreach ($filas as $fila) {
            $lineas[] = implode(';', array_map(fn (string $c) => '"' . str_replace('"', '""', $c) . '"', $fila));
        }
        return "\xEF\xBB\xBF" . implode("\r\n", $lineas) . "\r\n";
    }

    private function descripcionDelMenu(DateTimeImmutable $fecha): string
    {
        $menu = $this->menus->buscarPorFechaInicio(Menu::lunesDeLaSemana($fecha));
        $plato = $menu?->consultarDia($fecha);
        return $plato === null ? 'Sin menú' : $plato->getSopa() . ' / ' . $plato->getPlatoPrincipal();
    }

    private function nombre(int $idPersona): string
    {
        $persona = $this->personas->buscarPorId($idPersona);
        return $persona === null ? '' : $persona->obtenerNombreCompleto();
    }
}