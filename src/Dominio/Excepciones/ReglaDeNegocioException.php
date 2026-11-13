<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Excepciones;

use DomainException;

/** Se lanza cuando una operación viola una regla del backlog. El mensaje es apto para mostrarse al usuario. */
final class ReglaDeNegocioException extends DomainException
{
}