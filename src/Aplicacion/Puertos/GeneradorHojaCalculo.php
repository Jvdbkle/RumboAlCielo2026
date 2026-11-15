<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Puertos;

/** Puerto para armar un archivo de Excel (.xlsx); lo implementa la infraestructura. */
interface GeneradorHojaCalculo
{
    /**
     * @param string[] $encabezados
     * @param list<list<string|int|float>> $filas
     * @return string El contenido binario del archivo .xlsx.
     */
    public function generar(string $nombreHoja, array $encabezados, array $filas): string;
}