<?php

namespace ZfrLightspeedRetailTest;

use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\Filter;

/**
 * @author Daniel Gimenes
 */
final class FilterTest extends TestCase
{
    public function testNormalizesEmptyCollection()
    {
        $this->assertSame([], Filter::normalizeCollection([]));
    }

    public function testNormalizesCollectionWithASingleItem()
    {
        $this->assertSame([
            ['foo' => 'bar'],
        ], Filter::normalizeCollection([
            'foo' => 'bar',
        ]));
    }

    public function testNormalizesCollectionWithMultipleItems()
    {
        $this->assertSame([
            ['foo' => 'bar'],
            ['baz' => 'bat'],
        ], Filter::normalizeCollection([
            ['foo' => 'bar'],
            ['baz' => 'bat'],
        ]));
    }
}
