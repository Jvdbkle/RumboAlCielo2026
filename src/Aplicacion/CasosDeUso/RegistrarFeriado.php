<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Dominio\Entidades\Feriado;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

/**
 * HU-15 criterios 1 y 2: el administrador registra una fecha y su motivo como día sin
 * atención. Una fecha inválida o ya registrada no se guarda y se indica el motivo.
 */
final class RegistrarFeriado
{
    public function __construct(private FeriadoRepositorio $feriados)
    {
    }

    /** @param string $fecha Fecha en formato AAAA-MM-DD. Devuelve el id del feriado creado. */
    public function ejecutar(string $fecha, string $motivo): int
    {
        $dia = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha));
        if ($dia === false || $dia->format('Y-m-d') !== trim($fecha)) {
            throw new ReglaDeNegocioException('La fecha ingresada no es válida.');
        }

        $feriado = new Feriado(0, $dia, preg_replace('/\s+/u', ' ', trim($motivo)) ?? '');
        $feriado->registrar();

        if ($this->feriados->buscarPorFecha($dia) !== null) {
            throw new ReglaDeNegocioException('Esa fecha ya está registrada como día sin atención.');
        }

        return $this->feriados->guardar($feriado);
    }
}