<?php

namespace ZfrLightspeedRetail;

use GuzzleHttp\Command\Guzzle\QuerySerializer\QuerySerializerInterface;
use function GuzzleHttp\Psr7\build_query;

/**
 * @author Daniel Gimenes
 */
final class QuerySerializer implements QuerySerializerInterface
{
    /**
     * Aggregate query params and transform them into a string
     *
     * @param  array $queryParams
     *
     * @return string
     */
    public function aggregate(array $queryParams): string
    {
        // Set encoding to false to not encode
        return build_query($queryParams, false);
    }
}
