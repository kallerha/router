<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

use Attribute;
use FluencePrototype\Auth\AcceptRoles;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

/**
 * Class Route
 * @package FluencePrototype\Router
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route implements iRoute
{

    private string $name;
    private string $subdomain;
    private string $path;
    private bool $isFile;

    /**
     * Route constructor.
     * @param string $name
     * @param string $subdomain
     * @param string $path
     * @param bool $isFile
     * @throws InvalidRouteNameException|InvalidRoutePathException
     */
    public function __construct(string $name, string $subdomain, string $path, bool $isFile = false)
    {
        if ($name === '') {
            throw new InvalidRouteNameException();
        }

        if (str_starts_with(haystack: $path, needle: '/') || substr($path, offset: -1, length: 1) === '/') {
            throw new InvalidRoutePathException();
        }

        $this->name = $name;
        $this->subdomain = $subdomain;
        $this->path = $path;
        $this->isFile = $isFile;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function isFile(): bool
    {
        return $this->isFile;
    }

    private function getRoles(string $controller): array
    {
        try {
            $controllerClass = new ReflectionClass(objectOrClass: $controller);
            $controllerClassAttributes = $controllerClass->getAttributes(name: AcceptRoles::class);

            if (!empty($controllerClassAttributes)) {
                /** @var ReflectionAttribute $acceptRolesAttribute */
                $acceptRolesAttribute = array_pop(array: $controllerClassAttributes);
                $acceptRolesAttributeParameters = $acceptRolesAttribute->getArguments();
                $roles = array_pop(array: $acceptRolesAttributeParameters);

                return $roles;
            }
        } catch (ReflectionException) {
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function toRouteArray(string $controller): array
    {
        if ($this->path) {
            $pathArray = explode(separator: '/', string: $this->path);
            $pathArrayLength = count(value: $pathArray);

            $firstLetters = array_map(callback: function (string $path): string {
                return substr(string: $path, offset: 0, length: 1);
            }, array: $pathArray);

            $parametersCount = count(value: array_filter(array: $firstLetters, callback: function ($firstLetter) {
                return $firstLetter === ':';
            }));

            $arrayMerged = array_merge([$this->subdomain], [$pathArrayLength], $firstLetters);

            $arrayMerged[] = [
                'isFile' => $this->isFile,
                'name' => $this->name,
                'parametersLength' => $parametersCount,
                'path' => $this->path,
                'resource' => $controller,
                'roles' => $this->getRoles(controller: $controller)
            ];

            return $arrayMerged;
        }

        return [$this->subdomain, 0, [
            'isFile' => false,
            'name' => $this->name,
            'parametersLength' => 0,
            'path' => $this->path,
            'resource' => $controller,
            'roles' => $this->getRoles(controller: $controller)
        ]];
    }

}