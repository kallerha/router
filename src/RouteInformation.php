<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

use ReflectionClass;

/**
 * Class RouteInformation
 * @package FluencePrototype\Router
 */
class RouteInformation implements iRouteInformation
{

    private bool $isFile;
    private string $name;
    private int $parametersLength;
    private string $path;
    private string $resource;

    /**
     * RouteInformation constructor.
     * @param bool $isFile
     * @param string $name
     * @param int $parametersLength
     * @param string $path
     * @param string $resource
     */
    public function __construct(bool $isFile, string $name, int $parametersLength, string $path, string $resource)
    {
        $this->isFile = $isFile;
        $this->name = $name;
        $this->parametersLength = $parametersLength;
        $this->path = $path;
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function isFile(): bool
    {
        return $this->isFile;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getParametersLength(): int
    {
        return $this->parametersLength;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @inheritDoc
     */
    public static function createFromArray(array $routeInformationArray): ?RouteInformation
    {
        return (new ReflectionClass(objectOrClass: self::class))->newInstanceArgs(args: $routeInformationArray);
    }

}