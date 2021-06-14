<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

use Exception;
use Throwable;

/**
 * Class InvalidRouteNameException
 * @package FluencePrototype\Router
 */
class InvalidRouteNameException extends Exception
{

    /**
     * InvalidRouteNameException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}