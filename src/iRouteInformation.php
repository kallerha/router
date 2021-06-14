<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

/**
 * Interface iRouteInformation
 * @package FluencePrototype\Router
 */
interface iRouteInformation
{

    /**
     * @return bool
     */
    public function isFile(): bool;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return int
     */
    public function getParametersLength(): int;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return string
     */
    public function getResource(): string;

    /**
     * @param array $routeInformationArray
     * @return RouteInformation|null
     */
    public static function createFromArray(array $routeInformationArray): ?RouteInformation;

}