<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

use FluencePrototype\Http\Messages\iRequest;

/**
 * Interface iRouteMatcher
 * @package FluencePrototype\Router
 */
interface iRouteMatcher
{

    /**
     * @param iRequest $request
     * @return array|null
     */
    public function matchRouteWithRequestPath(iRequest $request): ?array;

}