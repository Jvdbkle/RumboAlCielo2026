<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Entidades\Feriado;

interface FeriadoRepositorio
{
    /** El feriado o día sin atención de esa fecha, o null si no hay. */
    public function buscarPorFecha(DateTimeImmutable $fecha): ?Feriado;

    /** @return Feriado[] Todos los feriados registrados, ordenados por fecha. */
    public function listarTodos(): array;

    /** Guarda un feriado nuevo (id 0). Devuelve su id. */
    public function guardar(Feriado $feriado): int;
}