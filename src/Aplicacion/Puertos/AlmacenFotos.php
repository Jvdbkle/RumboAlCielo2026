<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Puertos;


interface AlmacenFotos
{

    public function guardar(ArchivoSubido $archivo): string;
}