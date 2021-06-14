<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

/**
 * Interface iRoute
 * @package FluencePrototype\Router
 */
interface iRoute
{

    /**
     * @param string $controller
     * @return array
     */
    public function toRouteArray(string $controller): array;

}