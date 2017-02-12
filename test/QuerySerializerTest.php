<?php

namespace ZfrLightspeedRetailTest;

use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\QuerySerializer;

/**
 * @author Daniel Gimenes
 */
final class QuerySerializerTest extends TestCase
{
    public function testSerializesQueryStringWithoutEncoding()
    {
        $querySerializer = new QuerySerializer();

        $this->assertSame(
            'load_relations=["Customer"]',
            $querySerializer->aggregate(['load_relations' => '["Customer"]'])
        );
    }
}
