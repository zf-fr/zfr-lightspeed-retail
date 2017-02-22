<?php

namespace ZfrLightspeedRetail;

/**
 * @author Daniel Gimenes
 */
final class Filter
{
    /**
     * @param array $collection
     *
     * @return array
     */
    public static function normalizeCollection(array $collection): array
    {
        // Already in collection format
        if (empty($collection) || isset($collection[0])) {
            return $collection;
        }

        // When a collection contains a single item in Lightspeed,
        // they return the item directly instead of an array containing a single item.
        // So this filter wraps the item in an array to make sure that collections are always arrays of items.
        return [$collection];
    }
}
