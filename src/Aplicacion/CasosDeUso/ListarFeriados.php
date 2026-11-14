<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Repositorios\FeriadoRepositorio;
use RumboAlCielo\Dominio\Entidades\Feriado;

/**
 * HU-15 (apoyo al criterio 2): muestra al administrador los días sin atención ya
 * registrados, para que sepa qué fechas existen antes de registrar una nueva.
 */
final class ListarFeriados
{
    public function __construct(private FeriadoRepositorio $feriados)
    {
    }

    /** @return Feriado[] Ordenados por fecha. */
    public function ejecutar(): array
    {
        return $this->feriados->listarTodos();
    }
}