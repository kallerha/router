<?php

declare(strict_types=1);

namespace FluencePrototype\Router;

use FluencePrototype\Cache\Cache;
use FluencePrototype\Filesystem\Filesystem;
use FluencePrototype\Http\HttpUrl;
use FluencePrototype\Http\Messages\iRequest;

/**
 * Class RouteMatcher
 * @package FluencePrototype\Router
 */
class RouteMatcher implements iRouteMatcher
{

    /**
     * @param array $routeCandidates
     * @param string $requestPath
     * @return array|null
     */
    private function evaluateRouteCandidates(string $requestPath, array $routeCandidates): ?array
    {
        $requestPathArray = explode(separator: '/', string: $requestPath);
        $requestPathArrayLength = count(value: $requestPathArray);
        $routeCandidatesLength = count(value: $routeCandidates);
        $isParameterArray = array_fill(start_index: 0, count: $requestPathArrayLength, value: true);
        $minRouteCandidateParametersLength = 10;

        for ($i = 0; $i < $routeCandidatesLength; $i++) {
            $routeCandidate = $routeCandidates[$i];
            $routeCandidatePath = $routeCandidate['path'];
            $routeCandidateParametersLength = $routeCandidate['parametersLength'];
            $routeCandidatePathArray = explode(separator: '/', string: $routeCandidatePath);
            $routeCandidatePathArrayLength = count($routeCandidatePathArray);

            for ($j = 0; $j < $routeCandidatePathArrayLength; $j++) {
                $requestPathItem = $requestPathArray[$j];
                $routeCandidatePathItem = $routeCandidatePathArray[$j];

                if ($requestPathItem === $routeCandidatePathItem && substr(string: $requestPathItem, offset: 0, length: 1) !== ':' && $isParameterArray[$j]) {
                    $isParameterArray[$j] = false;
                }

                if (substr($routeCandidatePathItem, 0, 1) === ':') {
                    if ($minRouteCandidateParametersLength > $routeCandidateParametersLength) {
                        $minRouteCandidateParametersLength = $routeCandidateParametersLength;
                    }

                    continue;
                }

                if ($requestPathItem !== $routeCandidatePathItem) {
                    unset($routeCandidates[$i]);
                    continue 2;
                }

                if ($j === $routeCandidatePathArrayLength - 1 && $routeCandidateParametersLength === 0) {
                    return $routeCandidate;
                }
            }
        }

        $routeCandidatesFiltered = array_filter(array: $routeCandidates, callback: function (array $routeCandidate) use ($minRouteCandidateParametersLength): bool {
            $routeCandidateParametersLength = $routeCandidate['parametersLength'];

            return $minRouteCandidateParametersLength === $routeCandidateParametersLength;
        });

        $routeCandidatesFilteredLength = count($routeCandidatesFiltered);

        for ($i = 0; $i < $routeCandidatesFilteredLength; $i++) {
            $routeCandidate = $routeCandidatesFiltered[$i];
            $routeCandidatePath = $routeCandidate['path'];
            $routeCandidatePathArray = explode(separator: '/', string: $routeCandidatePath);
            $routeCandidatePathArrayLength = count(value: $routeCandidatePathArray);

            for ($j = 0; $j < $routeCandidatePathArrayLength; $j++) {
                $routeCandidatePathItem = $routeCandidatePathArray[$j];
                $isParameterItem = $isParameterArray[$j];

                if (substr(string: $routeCandidatePathItem, offset: 0, length: 1) === ':' && !$isParameterItem) {
                    unset($routeCandidatesFiltered[$i]);
                }
            }
        }

        if (!empty($routeCandidatesFiltered)) {
            return array_pop(array: $routeCandidatesFiltered);
        }

        return null;
    }

    /**
     * @param array $pathArray
     * @param array $routeCache
     * @param array $routeCandidates
     * @return array
     */
    private function findRouteCandidates(array $pathArray, array $routeCache, array $routeCandidates = []): array
    {
        if (is_int(value: key(array: $routeCache))) {
            return $routeCache;
        }

        $pathArrayPopped = array_pop(array: $pathArray);

        if ($pathArrayPopped) {
            $firstLetter = substr(string: $pathArrayPopped, offset: 0, length: 1);

            if (isset($routeCache[':']) || isset($routeCache[$firstLetter])) {
                $newRouteCache = [];

                if (isset($routeCache[$firstLetter])) {
                    $newRouteCache = $routeCache[$firstLetter];
                }

                if (isset($routeCache[':'])) {
                    $newRouteCache = array_merge_recursive($newRouteCache, $routeCache[':']);
                }

                return $this->findRouteCandidates(pathArray: $pathArray, routeCache: $newRouteCache, routeCandidates: $routeCandidates);
            }
        }

        return [];
    }

    /**
     * @return array
     */
    private function getRouteCache(): array
    {
        $cache = new Cache();
        $filesystem = new  Filesystem();

        if (!$routeCache = $cache->fetch(key: 'routeCache')) {
            $routeCache = $cache->store(key: 'routeCache', value: require $filesystem->getDirectoryPath() . DIRECTORY_SEPARATOR . 'route.cache.php');
        }

        return $routeCache;
    }

    /**
     * @inheritDoc
     */
    public function matchRouteWithRequestPath(iRequest $request): ?array
    {
        $routeCache = $this->getRouteCache();
        $requestSubdomain = $request->getSubdomain();
        $requestPath = $request->getPath();

        if ($requestPath === '') {
            if (isset($routeCache[$requestSubdomain][0])) {
                return array_pop(array: $routeCache[$requestSubdomain][0]);
            }

            return null;
        }

        $isFile = true;

        if (substr($requestPath, offset: -1, length: 1) === '/') {
            $isFile = false;
            $requestPath = substr(string: $requestPath, offset: 0, length: -1);
        }

        $requestPathArray = explode(separator: '/', string: $requestPath);
        $requestPathArrayLength = count(value: $requestPathArray);

        if (isset($routeCache[$requestSubdomain][$requestPathArrayLength])) {
            $pathArrayReversed = array_reverse(array: $requestPathArray);
            $routeCandidates = $this->findRouteCandidates(pathArray: $pathArrayReversed, routeCache: $routeCache[$requestSubdomain][$requestPathArrayLength]);

            if (!empty($routeCandidates)) {
                if ($routeInformationArray = $this->evaluateRouteCandidates(requestPath: $requestPath, routeCandidates: $routeCandidates)) {
                    if ($routeInformationArray['isFile'] === $isFile) {
                        return $routeInformationArray;
                    }

                    $currentUrl = HttpUrl::createFromCurrentUrl();

                    header(header: 'HTTP/1.1 301 Moved Permanently');
                    header(header: 'Location: ' . $currentUrl . (!$routeInformationArray['isFile'] && $routeInformationArray['isFile'] !== '' ? '/' : ''));

                    exit;
                }
            }
        }

        return null;
    }

}